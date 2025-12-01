<?php
// app/Services/HoldService.php

namespace App\Services;

use App\Repositories\Contracts\HoldRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HoldService
{
    public function __construct(
        private HoldRepositoryInterface $holdRepository,
        private ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Create a hold for a product
     */
    public function createHold(int $productId, int $quantity): array
    {
        return DB::transaction(function () use ($productId, $quantity) {
            // Lock the product row to prevent race conditions
            $product = $this->productRepository->findWithLock($productId);

            if (!$product) {
                throw new Exception('Product not found');
            }

            // Check if sufficient stock is available
            if (!$product->hasAvailableStock($quantity)) {
                Log::warning("Insufficient stock for hold", [
                    'product_id' => $productId,
                    'requested' => $quantity,
                    'available' => $product->available_stock
                ]);

                throw new Exception('Insufficient stock available');
            }

            // Create the hold
            $hold = $this->holdRepository->create($productId, $quantity,2);

            // Reserve the stock
            $this->productRepository->incrementReserved($productId, $quantity);

            Log::info("Hold created successfully", [
                'hold_id' => $hold->id,
                'product_id' => $productId,
                'quantity' => $quantity
            ]);

            return [
                'hold_id' => $hold->id,
                'expires_at' => $hold->expires_at->toIso8601String(),
                'quantity' => $hold->quantity,
            ];
        });
    }

    /**
     * Validate if a hold is usable
     */
    public function validateHold(int $holdId): bool
    {
        $hold = $this->holdRepository->find($holdId);

        if (!$hold) {
            Log::warning("Hold not found", ['hold_id' => $holdId]);
            return false;
        }

        if (!$hold->isValid()) {
            Log::warning("Hold is invalid", [
                'hold_id' => $holdId,
                'used' => $hold->used,
                'released' => $hold->released,
                'expired' => $hold->isExpired()
            ]);
            return false;
        }

        return true;
    }

    /**
     * Release expired holds
     */
    public function releaseExpiredHolds(): int
    {
        $releasedCount = 0;

        $expiredHolds = $this->holdRepository->getExpiredHolds();

        foreach ($expiredHolds as $hold) {
            try {
                DB::transaction(function () use ($hold) {
                    // Lock the product
                    $product = $this->productRepository->findWithLock($hold->product_id);

                    if ($product) {
                        // Release the reserved stock
                        $this->productRepository->decrementReserved(
                            $hold->product_id,
                            $hold->quantity
                        );
                    }

                    // Mark hold as released
                    $this->holdRepository->markAsReleased($hold->id);

                    Log::info("Expired hold released", [
                        'hold_id' => $hold->id,
                        'product_id' => $hold->product_id,
                        'quantity' => $hold->quantity
                    ]);
                });

                $releasedCount++;
            } catch (Exception $e) {
                Log::error("Failed to release expired hold", [
                    'hold_id' => $hold->id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        if ($releasedCount > 0) {
            Log::info("Expired holds released", ['count' => $releasedCount]);
        }

        return $releasedCount;
    }
}
