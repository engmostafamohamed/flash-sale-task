<?php
// app/Services/OrderService.php

namespace App\Services;

use App\Models\Order;
use App\Repositories\Contracts\HoldRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrderService
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private HoldRepositoryInterface $holdRepository,
        private ProductRepositoryInterface $productRepository
    ) {}

    /**
     * Create an order from a hold
     */
    public function createOrder(int $holdId): array
    {
        return DB::transaction(function () use ($holdId) {
            // Lock the hold to prevent concurrent usage
            $hold = $this->holdRepository->findWithLock($holdId);

            if (!$hold) {
                throw new Exception('Hold not found');
            }

            if ($hold->used) {
                throw new Exception('Hold has already been used');
            }

            if ($hold->released) {
                throw new Exception('Hold has been released');
            }

            if ($hold->isExpired()) {
                throw new Exception('Hold has expired');
            }

            // Get product details
            $product = $this->productRepository->find($hold->product_id);

            if (!$product) {
                throw new Exception('Product not found');
            }

            // Calculate total
            $total = $product->price * $hold->quantity;

            // Create the order
            $order = $this->orderRepository->create(
                $hold->product_id,
                $hold->id,
                $hold->quantity,
                $total
            );

            // Mark hold as used
            $this->holdRepository->markAsUsed($hold->id);

            Log::info("Order created from hold", [
                'order_id' => $order->id,
                'hold_id' => $hold->id,
                'product_id' => $product->id,
                'quantity' => $hold->quantity,
                'total' => $total
            ]);

            return [
                'order_id' => $order->id,
                'product_id' => $order->product_id,
                'quantity' => $order->quantity,
                'total' => $order->total,
                'status' => $order->status,
                'created_at' => $order->created_at->toIso8601String(),
            ];
        });
    }
}
