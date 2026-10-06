<?php

namespace App\Models;

use App\Models\Concerns\BelongsToWorkspace;
use Database\Factories\ClientFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $workspace_id
 * @property string $type
 * @property string $name
 * @property string|null $company
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $notes
 * @property Carbon|null $archived_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['type', 'name', 'company', 'email', 'phone', 'notes'])]
class Client extends Model
{
    use BelongsToWorkspace;

    /** @use HasFactory<ClientFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['archived_at' => 'datetime'];
    }

    /** @return HasMany<ClientContact, $this> */
    public function contacts(): HasMany
    {
        return $this->hasMany(ClientContact::class);
    }

    /** @return HasMany<Project, $this> */
    public function projects(): HasMany
    {
        return $this->hasMany(Project::class);
    }

    public function primaryContact(): ?ClientContact
    {
        return $this->contacts()->orderByDesc('is_primary')->orderBy('id')->first();
    }

    /**
     * Best recipient for a change request: primary contact, then client email.
     *
     * @return array{name: string, email: string|null}
     */
    public function recipient(): array
    {
        $contact = $this->primaryContact();

        return $contact
            ? ['name' => $contact->name, 'email' => $contact->email]
            : ['name' => $this->name, 'email' => $this->email];
    }

    public function displayName(): string
    {
        return $this->company ? "{$this->name} ({$this->company})" : $this->name;
    }
}
