<?php

namespace App\Models;

use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $workspace_id
 * @property string $provider
 * @property string $provider_transaction_id
 * @property string|null $provider_subscription_id
 * @property string $status
 * @property int $total_minor
 * @property string $currency
 * @property string|null $invoice_number
 * @property Carbon|null $billed_at
 */
class Transaction extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['billed_at' => 'datetime', 'total_minor' => 'integer'];
    }

    public function total(): ?Money
    {
        return Money::isSupportedCurrency($this->currency) ? new Money($this->total_minor, $this->currency) : null;
    }
}
