<?php

namespace ERPClient\Models;

use ERPClient\Enums\OrderStatus;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    protected $guarded = [];

    protected $casts = [
        'items'  => 'array',
        'status' => OrderStatus::class,
        'discount_percent' => 'integer',
    ];

    public function getFullNameAttribute(): string
    {
        return "{$this->name}";
    }

    public function getAddressAttribute(): string
    {
        return "{$this->street}";
    }

    public function getItemsListAttribute(): string
    {
        return collect($this->items)->map(function ($item) {
            $product = $item['product'] ?? [];

            return $product['name'] ?? 'Produs';
        })->implode(', ');
    }

    public function hasPricing(): bool
    {
        $items = $this->items ?? [];

        foreach ($items as $item) {
            if (isset($item['price']) && (float) $item['price'] > 0) {
                return true;
            }
        }

        return false;
    }

    public function getTotalPriceAttribute(): float
    {
        $items = $this->items ?? [];
        $total = 0;

        foreach ($items as $item) {
            if (! isset($item['price'])) {
                continue;
            }

            $total += (float) $item['price'] * (int) ($item['quantity'] ?? 0);
        }

        return $total;
    }

    public function getDiscountAmountAttribute(): float
    {
        $percent = (int) ($this->discount_percent ?? 0);

        if ($percent <= 0) {
            return 0;
        }

        return ($this->total_price * $percent) / 100;
    }

    public function getDiscountedTotalPriceAttribute(): float
    {
        return max(0, $this->total_price - $this->discount_amount);
    }

    public function getPricingCurrencyAttribute(): ?string
    {
        $items = $this->items ?? [];

        foreach ($items as $item) {
            if (! empty($item['currency'])) {
                return $item['currency'];
            }
        }

        return null;
    }
}
