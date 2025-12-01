<?php
// app/Models/Order.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'hold_id',
        'quantity',
        'total',
        'status',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'total' => 'decimal:2',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_PAID = 'paid';
    const STATUS_CANCELLED = 'cancelled';

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function hold(): BelongsTo
    {
        return $this->belongsTo(Hold::class);
    }

    public function paymentWebhooks(): HasMany
    {
        return $this->hasMany(PaymentWebhook::class);
    }

    /**
     * Mark order as paid
     */
    public function markAsPaid(): void
    {
        $this->update(['status' => self::STATUS_PAID]);
    }

    /**
     * Mark order as cancelled
     */
    public function markAsCancelled(): void
    {
        $this->update(['status' => self::STATUS_CANCELLED]);
    }

    /**
     * Check if order is pending
     */
    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
