<?php

namespace App\Repositories\Contracts;

use App\Models\PaymentWebhook;

interface PaymentWebhookRepositoryInterface
{
    public function findByIdempotencyKey(string $idempotencyKey): ?PaymentWebhook;

    public function create(string $idempotencyKey, int $orderId, string $status, array $payload): PaymentWebhook;

    public function markAsProcessed(int $webhookId): bool;
}
