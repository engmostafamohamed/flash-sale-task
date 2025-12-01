<?php
// app/Repositories/HoldRepository.php

namespace App\Repositories;

use App\Models\Hold;
use App\Repositories\Contracts\HoldRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class HoldRepository implements HoldRepositoryInterface
{
    /**
     * Create a new hold
     */
    public function create(int $productId, int $quantity, int $expiryMinutes = 2): Hold
    {
        $hold = Hold::create([
            'product_id' => $productId,
            'quantity' => $quantity,
            'expires_at' => now()->addMinutes($expiryMinutes),
        ]);

        Log::info("Hold created", [
            'hold_id' => $hold->id,
            'product_id' => $productId,
            'quantity' => $quantity,
            'expires_at' => $hold->expires_at
        ]);

        return $hold;
    }

    /**
     * Find hold by ID
     */
    public function find(int $id): ?Hold
    {
        return Hold::find($id);
    }

    /**
     * Find hold with pessimistic lock
     */
    public function findWithLock(int $id): ?Hold
    {
        return Hold::lockForUpdate()->find($id);
    }

    /**
     * Mark hold as used
     */
    public function markAsUsed(int $holdId): bool
    {
        $result = Hold::where('id', $holdId)->update(['used' => true]);

        if ($result) {
            Log::info("Hold marked as used", ['hold_id' => $holdId]);
        }

        return $result > 0;
    }

    /**
     * Mark hold as released
     */
    public function markAsReleased(int $holdId): bool
    {
        $result = Hold::where('id', $holdId)->update(['released' => true]);

        if ($result) {
            Log::info("Hold marked as released", ['hold_id' => $holdId]);
        }

        return $result > 0;
    }

    /**
     * Get all expired holds that haven't been released
     */
    public function getExpiredHolds(): Collection
    {
        return Hold::expired()->get();
    }
}
