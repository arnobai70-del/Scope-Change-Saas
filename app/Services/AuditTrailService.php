<?php

namespace App\Services;

use App\Enums\ActorType;
use App\Models\AuditEvent;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * Writes append-only, hash-chained audit events. Each event hash covers the
 * event payload plus the previous event hash for the same entity, so later
 * tampering with a row breaks the chain.
 */
class AuditTrailService
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        Model $entity,
        string $event,
        ActorType $actorType = ActorType::System,
        User|string|null $actor = null,
        array $metadata = [],
        ?int $workspaceId = null,
    ): AuditEvent {
        $entityType = $entity->getMorphClass();
        $entityId = (int) $entity->getKey();
        $workspaceId ??= $entity instanceof Workspace ? $entity->getKey() : $entity->getAttribute('workspace_id');

        $previous = AuditEvent::query()
            ->where('entity_type', $entityType)
            ->where('entity_id', $entityId)
            ->latest('id')
            ->value('hash');

        $occurredAt = Carbon::now('UTC');
        [$actorId, $actorLabel] = $this->describeActor($actor);

        $payload = [
            'workspace_id' => $workspaceId,
            'actor_type' => $actorType->value,
            'actor_id' => $actorId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'event' => $event,
            'metadata' => self::canonical($metadata),
            'occurred_at' => $occurredAt->format('Y-m-d\TH:i:s.uP'),
            'previous_hash' => $previous,
        ];

        return AuditEvent::query()->create([
            'workspace_id' => $workspaceId,
            'actor_type' => $actorType->value,
            'actor_id' => $actorId,
            'actor_label' => $actorLabel,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'event' => $event,
            'metadata_json' => $metadata,
            'previous_hash' => $previous,
            'hash' => hash('sha256', (string) json_encode($payload)),
            'occurred_at' => $occurredAt,
        ]);
    }

    /**
     * Verify the hash chain for one entity is intact.
     */
    public function verifyChain(Model $entity): bool
    {
        $previous = null;

        $events = AuditEvent::query()
            ->where('entity_type', $entity->getMorphClass())
            ->where('entity_id', $entity->getKey())
            ->orderBy('id')
            ->get();

        foreach ($events as $event) {
            if ($event->previous_hash !== $previous) {
                return false;
            }

            $payload = [
                'workspace_id' => $event->workspace_id,
                'actor_type' => $event->actor_type,
                'actor_id' => $event->actor_id,
                'entity_type' => $event->entity_type,
                'entity_id' => $event->entity_id,
                'event' => $event->event,
                'metadata' => self::canonical($event->metadata_json ?? []),
                'occurred_at' => $event->occurred_at->copy()->utc()->format('Y-m-d\TH:i:s.uP'),
                'previous_hash' => $event->previous_hash,
            ];

            if (! hash_equals($event->hash, hash('sha256', (string) json_encode($payload)))) {
                return false;
            }

            $previous = $event->hash;
        }

        return true;
    }

    /**
     * @return array{0: string|null, 1: string|null}
     */
    private function describeActor(User|string|null $actor): array
    {
        if ($actor instanceof User) {
            return [(string) $actor->id, $actor->name];
        }

        return [null, $actor];
    }

    /**
     * Key-sorted copy of the metadata. JSON columns (MySQL) do not keep key
     * order, so hashing must not depend on it.
     *
     * @param  array<mixed>  $value
     * @return array<mixed>
     */
    private static function canonical(array $value): array
    {
        if (! array_is_list($value)) {
            ksort($value);
        }

        return array_map(fn ($item) => is_array($item) ? self::canonical($item) : $item, $value);
    }
}
