<?php
// app/Models/Hold.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Hold extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
        'expires_at',
        'used',
        'released',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used' => 'boolean',
        'released' => 'boolean',
        'quantity' => 'integer',
    ];

    /**
     * Check if hold is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if hold is valid for use
     */
    public function isValid(): bool
    {
        return !$this->used && !$this->released && !$this->isExpired();
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function order(): HasOne
    {
        return $this->hasOne(Order::class);
    }

    /**
     * Scope for expired holds
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->where('used', false)
            ->where('released', false);
    }

    /**
     * Scope for valid holds
     */
    public function scopeValid($query)
    {
        return $query->where('expires_at', '>', now())
            ->where('used', false)
            ->where('released', false);
    }
}
