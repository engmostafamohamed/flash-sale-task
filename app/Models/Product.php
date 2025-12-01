<?php
// app/Models/Product.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'price',
        'stock',
        'reserved',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
        'reserved' => 'integer',
    ];

    /**
     * Get available stock (total - reserved)
     */
    public function getAvailableStockAttribute(): int
    {
        return max(0, $this->stock - $this->reserved);
    }

    /**
     * Check if quantity is available
     */
    public function hasAvailableStock(int $quantity): bool
    {
        return $this->available_stock >= $quantity;
    }

    public function holds(): HasMany
    {
        return $this->hasMany(Hold::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}
