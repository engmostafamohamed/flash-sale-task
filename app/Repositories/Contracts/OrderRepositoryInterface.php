<?php

namespace App\Repositories\Contracts;

use App\Models\Order;

interface OrderRepositoryInterface
{
    public function create(int $productId, int $holdId, int $quantity, float $total): Order;

    public function find(int $id): ?Order;

    public function findWithLock(int $id): ?Order;

    public function updateStatus(int $orderId, string $status): bool;
}
