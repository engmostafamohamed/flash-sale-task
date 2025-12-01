<?php

namespace App\Repositories;

use App\Models\PaymentWebhook;
use App\Repositories\Contracts\PaymentWebhookRepositoryInterface;
use Illuminate\Support\Facades\Log;

class PaymentWebhookRepository implements PaymentWebhookRepositoryInterface
{
    /**
     * Find webhook by idempotency key
     */
    public function findByIdempotencyKey(string $idempotencyKey): ?PaymentWebhook
    {
        return PaymentWebhook::where('idempotency_key', $idempotencyKey)->first();
    }

    /**
     * Create a new webhook record
     */
    public function create(string $idempotencyKey, int $orderId, string $status, array $payload = []): PaymentWebhook
    {
        $webhook = PaymentWebhook::create([
            'idempotency_key' => $idempotencyKey,
            'order_id' => $orderId,
            'status' => $status,
            'payload' => $payload,
        ]);

        Log::info("Payment webhook created", [
            'webhook_id' => $webhook->id,
            'idempotency_key' => $idempotencyKey,
            'order_id' => $orderId,
            'status' => $status
        ]);

        return $webhook;
    }

    /**
     * Mark webhook as processed
     */
    public function markAsProcessed(int $webhookId): bool
    {
        $result = PaymentWebhook::where('id', $webhookId)
            ->update(['processed' => true]);

        if ($result) {
            Log::info("Webhook marked as processed", ['webhook_id' => $webhookId]);
        }

        return $result > 0;
    }
}
