<?php

namespace App\Repositories;

use App\Models\Order;
use App\Repositories\Contracts\OrderRepositoryInterface;
use Illuminate\Support\Facades\Log;

class OrderRepository implements OrderRepositoryInterface
{
    /**
     * Create a new order
     */
    public function create(int $productId, int $holdId, int $quantity, float $total): Order
    {
        $order = Order::create([
            'product_id' => $productId,
            'hold_id' => $holdId,
            'quantity' => $quantity,
            'total' => $total,
            'status' => Order::STATUS_PENDING,
        ]);

        Log::info("Order created", [
            'order_id' => $order->id,
            'product_id' => $productId,
            'hold_id' => $holdId,
            'quantity' => $quantity,
            'total' => $total
        ]);

        return $order;
    }

    /**
     * Find order by ID
     */
    public function find(int $id): ?Order
    {
        return Order::find($id);
    }

    /**
     * Find order with pessimistic lock
     */
    public function findWithLock(int $id): ?Order
    {
        return Order::lockForUpdate()->find($id);
    }

    /**
     * Update order status
     */
    public function updateStatus(int $orderId, string $status): bool
    {
        $result = Order::where('id', $orderId)->update(['status' => $status]);

        if ($result) {
            Log::info("Order status updated", [
                'order_id' => $orderId,
                'status' => $status
            ]);
        }

        return $result > 0;
    }
}
