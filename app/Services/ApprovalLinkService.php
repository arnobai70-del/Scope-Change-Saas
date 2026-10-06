<?php

namespace App\Services;

use App\Models\ApprovalLink;
use App\Models\ChangeRequest;
use App\Models\ChangeRequestRevision;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * Issues and resolves client approval links. Raw tokens are only ever held
 * in memory and in the outgoing email: the database stores a SHA-256 hash.
 */
class ApprovalLinkService
{
    /**
     * @return array{link: ApprovalLink, token: string, url: string}
     */
    public function issue(ChangeRequest $changeRequest, ChangeRequestRevision $revision, ?Carbon $expiresAt): array
    {
        $this->revokeAll($changeRequest);

        $token = Str::random(48);

        /** @var ApprovalLink $link */
        $link = $changeRequest->approvalLinks()->create([
            'revision_id' => $revision->id,
            'token_hash' => self::hash($token),
            'expires_at' => $expiresAt,
            'access_policy' => 'link',
        ]);

        return ['link' => $link, 'token' => $token, 'url' => $this->url($changeRequest, $token)];
    }

    public function revokeAll(ChangeRequest $changeRequest): void
    {
        $changeRequest->approvalLinks()->whereNull('revoked_at')->update(['revoked_at' => now()]);
    }

    /**
     * Resolve a link by public id + raw token. Returns null for unknown
     * tokens; callers must still check isUsable() before allowing actions.
     */
    public function resolve(string $publicId, string $token): ?ApprovalLink
    {
        if (strlen($token) !== 48) {
            return null;
        }

        $changeRequest = ChangeRequest::query()->where('public_id', $publicId)->first();

        if ($changeRequest === null) {
            return null;
        }

        $link = ApprovalLink::query()
            ->where('change_request_id', $changeRequest->id)
            ->where('token_hash', self::hash($token))
            ->first();

        $link?->setRelation('changeRequest', $changeRequest);

        return $link;
    }

    public function url(ChangeRequest $changeRequest, string $token): string
    {
        return route('client.show', ['publicId' => $changeRequest->public_id, 'token' => $token]);
    }

    public static function hash(string $token): string
    {
        return hash('sha256', $token);
    }
}
