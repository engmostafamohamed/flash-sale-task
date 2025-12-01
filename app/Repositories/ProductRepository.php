<?php
// app/Repositories/ProductRepository.php

namespace App\Repositories;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductRepository implements ProductRepositoryInterface
{
    /**
     * Find product by ID (with caching)
     */
    public function find(int $id): ?Product
    {
        return Cache::remember(
            "product:{$id}",
            now()->addSeconds(5),
            fn() => Product::find($id)
        );
    }

    /**
     * Find product with pessimistic lock
     */
    public function findWithLock(int $id): ?Product
    {
        return Product::lockForUpdate()->find($id);
    }

    /**
     * Get available stock for a product
     */
    public function getAvailableStock(int $productId): int
    {
        $product = $this->find($productId);

        if (!$product) {
            return 0;
        }

        return $product->available_stock;
    }

    /**
     * Increment reserved quantity
     */
    public function incrementReserved(int $productId, int $quantity): bool
    {
        $result = Product::where('id', $productId)
            ->increment('reserved', $quantity);

        if ($result) {
            Cache::forget("product:{$productId}");
            Log::info("Product reserved incremented", [
                'product_id' => $productId,
                'quantity' => $quantity
            ]);
        }

        return $result > 0;
    }

    /**
     * Decrement reserved quantity
     */
    public function decrementReserved(int $productId, int $quantity): bool
    {
        $result = Product::where('id', $productId)
            ->where('reserved', '>=', $quantity)
            ->decrement('reserved', $quantity);

        if ($result) {
            Cache::forget("product:{$productId}");
            Log::info("Product reserved decremented", [
                'product_id' => $productId,
                'quantity' => $quantity
            ]);
        }

        return $result > 0;
    }

    /**
     * Decrement stock (for completed orders)
     */
    public function decrementStock(int $productId, int $quantity): bool
    {
        $result = Product::where('id', $productId)
            ->where('stock', '>=', $quantity)
            ->decrement('stock', $quantity);

        if ($result) {
            Cache::forget("product:{$productId}");
            Log::info("Product stock decremented", [
                'product_id' => $productId,
                'quantity' => $quantity
            ]);
        }

        return $result > 0;
    }
}
