<?php
// app/Models/PaymentWebhook.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentWebhook extends Model
{
    use HasFactory;

    protected $fillable = [
        'idempotency_key',
        'order_id',
        'status',
        'processed',
        'payload',
    ];

    protected $casts = [
        'processed' => 'boolean',
        'payload' => 'array',
    ];

    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if webhook is successful
     */
    public function isSuccess(): bool
    {
        return $this->status === self::STATUS_SUCCESS;
    }

    /**
     * Mark as processed
     */
    public function markAsProcessed(): void
    {
        $this->update(['processed' => true]);
    }
}
