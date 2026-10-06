<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Support\Money;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $change_request_id
 * @property int $revision_id
 * @property PaymentStatus $status
 * @property int $amount_minor
 * @property string $currency
 * @property string $method_type
 * @property string|null $external_url
 * @property string|null $reference
 * @property Carbon|null $marked_sent_at
 * @property Carbon|null $confirmed_at
 * @property int|null $confirmed_by
 */
#[Fillable(['revision_id', 'status', 'amount_minor', 'currency', 'method_type', 'external_url', 'reference'])]
class PaymentRecord extends Model
{
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount_minor' => 'integer',
            'marked_sent_at' => 'datetime',
            'confirmed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<ChangeRequest, $this> */
    public function changeRequest(): BelongsTo
    {
        return $this->belongsTo(ChangeRequest::class);
    }

    public function amount(): Money
    {
        return new Money($this->amount_minor, $this->currency);
    }
}
