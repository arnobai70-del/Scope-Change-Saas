<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $subscription_id
 * @property string $provider_price_id
 * @property string|null $provider_product_id
 * @property int $quantity
 * @property string|null $status
 */
class SubscriptionItem extends Model
{
    protected $guarded = ['id'];
}
