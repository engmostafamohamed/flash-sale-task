<?php
// app/Services/ProductService.php

namespace App\Services;

use App\Models\Product;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\Log;

class ProductService
{
    public function __construct(
        private ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Get product details with available stock
     */
    public function getProductDetails(int $productId): ?array
    {
        $product = $this->productRepository->find($productId);

        if (!$product) {
            Log::warning("Product not found", ['product_id' => $productId]);
            return null;
        }

        return [
            'id' => $product->id,
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'available_stock' => $product->available_stock,
            'total_stock' => $product->stock,
        ];
    }

    /**
     * Check if product has sufficient stock
     */
    public function hasAvailableStock(int $productId, int $quantity): bool
    {
        $availableStock = $this->productRepository->getAvailableStock($productId);

        Log::info("Checking stock availability", [
            'product_id' => $productId,
            'requested' => $quantity,
            'available' => $availableStock
        ]);

        return $availableStock >= $quantity;
    }
}
