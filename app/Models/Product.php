<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'description',
        'price',
        'discount_percentage',
        'discount_start_date',
        'discount_end_date',
        'stock_quantity',
        'sku',
        'image_url',
        'is_active',
    ];

    protected $appends = [
        'has_active_discount',
        'discounted_price',
        'discount_amount',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'discount_percentage' => 'decimal:2',
            'discount_start_date' => 'datetime',
            'discount_end_date' => 'datetime',
            'stock_quantity' => 'integer',
            'is_active' => 'boolean',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function cartItems(): HasMany
    {
        return $this->hasMany(CartItem::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('stock_quantity', '>', 0);
    }

    public function scopeLowStock(Builder $query, int $threshold = 5): Builder
    {
        return $query->where('stock_quantity', '<=', $threshold)
                     ->where('stock_quantity', '>', 0);
    }

    public function scopeOutOfStock(Builder $query): Builder
    {
        return $query->where('stock_quantity', '=', 0);
    }

    public function getIsLowStockAttribute(): bool
    {
        return $this->stock_quantity <= config('inventory.low_stock_threshold', 5);
    }

    public function getIsOutOfStockAttribute(): bool
    {
        return $this->stock_quantity === 0;
    }

    protected function setStockQuantityAttribute(int|null $value): void
    {
        $this->attributes['stock_quantity'] = max(0, $value ?? 0);
    }

    public function hasActiveDiscount(): bool
    {
        if (!$this->discount_percentage || $this->discount_percentage <= 0) {
            return false;
        }

        $now = now();
        $startDate = $this->discount_start_date;
        $endDate = $this->discount_end_date;

        if ($startDate && $now->lt($startDate)) {
            return false;
        }

        if ($endDate && $now->gt($endDate)) {
            return false;
        }

        return true;
    }

    public function getHasActiveDiscountAttribute(): bool
    {
        return $this->hasActiveDiscount();
    }

    public function getDiscountedPriceAttribute(): float
    {
        if (!$this->hasActiveDiscount()) {
            return (float) $this->price;
        }

        $discountAmount = (float) $this->price * ((float) $this->discount_percentage / 100);
        return (float) $this->price - $discountAmount;
    }

    public function getDiscountAmountAttribute(): float
    {
        if (!$this->hasActiveDiscount()) {
            return 0.0;
        }

        return (float) $this->price - $this->discounted_price;
    }

    public function scopeWithActiveDiscount(Builder $query): Builder
    {
        $now = now();
        return $query->whereNotNull('discount_percentage')
                    ->where('discount_percentage', '>', 0)
                    ->where(function ($q) use ($now) {
                        $q->whereNull('discount_start_date')
                          ->orWhere('discount_start_date', '<=', $now);
                    })
                    ->where(function ($q) use ($now) {
                        $q->whereNull('discount_end_date')
                          ->orWhere('discount_end_date', '>=', $now);
                    });
    }
}
