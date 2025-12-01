<?php

namespace App\Repositories\Contracts;

use App\Models\Product;

interface ProductRepositoryInterface
{
    public function find(int $id): ?Product;

    public function findWithLock(int $id): ?Product;

    public function getAvailableStock(int $productId): int;

    public function incrementReserved(int $productId, int $quantity): bool;

    public function decrementReserved(int $productId, int $quantity): bool;

    public function decrementStock(int $productId, int $quantity): bool;
}
