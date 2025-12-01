<?php
// app/Services/PaymentWebhookService.php

namespace App\Services;

use App\Models\Order;
use App\Models\PaymentWebhook;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PaymentWebhookRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookService
{
    public function __construct(
    private PaymentWebhookRepositoryInterface $webhookRepository,
    private OrderRepositoryInterface $orderRepository,
    private ProductRepositoryInterface $productRepository
    ) {}

     /**
     * Process payment webhook (idempotent)
     */
    public function processWebhook(string $idempotencyKey, int $orderId, string $status, array $payload = []): array
    {
        return DB::transaction(function () use ($idempotencyKey, $orderId, $status, $payload) {
            // Check if webhook already exists
            $existingWebhook = $this->webhookRepository->findByIdempotencyKey($idempotencyKey);

            if ($existingWebhook) {
                Log::info("Duplicate webhook received", [
                    'idempotency_key' => $idempotencyKey,
                    'webhook_id' => $existingWebhook->id,
                    'already_processed' => $existingWebhook->processed
                ]);

                return [
                    'success' => true,
                    'message' => 'Webhook already processed',
                    'webhook_id' => $existingWebhook->id,
                    'order_id' => $existingWebhook->order_id,
                    'duplicate' => true,
                ];
            }

            // Create webhook record
            $webhook = $this->webhookRepository->create($idempotencyKey, $orderId, $status, $payload);

            // Lock the order
            $order = $this->orderRepository->findWithLock($orderId);

            if (!$order) {
                Log::error("Order not found for webhook", [
                    'order_id' => $orderId,
                    'idempotency_key' => $idempotencyKey
                ]);

                throw new Exception('Order not found');
            }

            // Only process if order is still pending
            if (!$order->isPending()) {
                Log::warning("Order is not in pending status", [
                    'order_id' => $orderId,
                    'current_status' => $order->status,
                    'webhook_status' => $status
                ]);

                $this->webhookRepository->markAsProcessed($webhook->id);

                return [
                    'success' => true,
                    'message' => 'Order already processed',
                    'webhook_id' => $webhook->id,
                    'order_id' => $order->id,
                    'order_status' => $order->status,
                ];
            }

            // Process based on payment status
            if ($status === PaymentWebhook::STATUS_SUCCESS) {
                $this->processSuccessfulPayment($order);
            } else {
                $this->processFailedPayment($order);
            }

            // Mark webhook as processed
            $this->webhookRepository->markAsProcessed($webhook->id);

            Log::info("Webhook processed successfully", [
                'webhook_id' => $webhook->id,
                'order_id' => $order->id,
                'status' => $status
            ]);

            return [
                'success' => true,
                'message' => 'Webhook processed successfully',
                'webhook_id' => $webhook->id,
                'order_id' => $order->id,
                'order_status' => $order->fresh()->status,
                'duplicate' => false,
            ];
        });
    }

    /**
     * Process successful payment
     */
    private function processSuccessfulPayment(Order $order): void
    {
        // Lock the product
        $product = $this->productRepository->findWithLock($order->product_id);

        if (!$product) {
            throw new Exception('Product not found');
        }

        // Deduct from actual stock
        $this->productRepository->decrementStock($order->product_id, $order->quantity);

        // Deduct from reserved
        $this->productRepository->decrementReserved($order->product_id, $order->quantity);

        // Update order status
        $this->orderRepository->updateStatus($order->id, Order::STATUS_PAID);

        Log::info("Payment successful - stock deducted", [
            'order_id' => $order->id,
            'product_id' => $order->product_id,
            'quantity' => $order->quantity
        ]);
    }

    /**
     * Process failed payment
     */
    private function processFailedPayment(Order $order): void
    {
        // Lock the product
        $product = $this->productRepository->findWithLock($order->product_id);

        if (!$product) {
            throw new Exception('Product not found');
        }

        // Release reserved stock
        $this->productRepository->decrementReserved($order->product_id, $order->quantity);

        // Update order status
        $this->orderRepository->updateStatus($order->id, Order::STATUS_CANCELLED);

        Log::info("Payment failed - stock released", [
            'order_id' => $order->id,
            'product_id' => $order->product_id,
            'quantity' => $order->quantity
        ]);
    }

}
