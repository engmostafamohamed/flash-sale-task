<?php

use App\Models\Hold;
use App\Models\Order;
use App\Models\Product;
use function Pest\Laravel\postJson;

beforeEach(function () {
    test()->product = Product::factory()->create([
        'price' => 100.00,
        'stock' => 50,
        'reserved' => 5,
    ]);

    test()->hold = Hold::factory()->create([
        'product_id' => test()->product->id,
        'quantity' => 5,
        'used' => true,
    ]);

    test()->order = Order::factory()->create([
        'product_id' => test()->product->id,
        'hold_id' => test()->hold->id,
        'quantity' => 5,
        'total' => 500.00,
        'status' => 'pending',
    ]);
});

test('successful payment webhook updates order and deducts stock', function () {
    $response = postJson('/api/payments/webhook', [
        'idempotency_key' => 'unique-key-123',
        'order_id' => test()->order->id,
        'status' => 'success',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Webhook processed successfully',
            'duplicate' => false,
        ]);

    test()->order->refresh();
    expect(test()->order->status)->toBe('paid');

    test()->product->refresh();
    expect(test()->product->stock)->toBe(45);
    expect(test()->product->reserved)->toBe(0);
});

test('failed payment webhook cancels order and releases stock', function () {
    $response = postJson('/api/payments/webhook', [
        'idempotency_key' => 'unique-key-456',
        'order_id' => test()->order->id,
        'status' => 'failed',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Webhook processed successfully',
        ]);

    test()->order->refresh();
    expect(test()->order->status)->toBe('cancelled');

    test()->product->refresh();
    expect(test()->product->stock)->toBe(50);
    expect(test()->product->reserved)->toBe(0);
});

test('webhook idempotency prevents duplicate processing', function () {
    $idempotencyKey = 'duplicate-test-key';

    $response1 = postJson('/api/payments/webhook', [
        'idempotency_key' => $idempotencyKey,
        'order_id' => test()->order->id,
        'status' => 'success',
    ]);
    $response1->assertOk();

    $initialStock = test()->product->fresh()->stock;

    $response2 = postJson('/api/payments/webhook', [
        'idempotency_key' => $idempotencyKey,
        'order_id' => test()->order->id,
        'status' => 'success',
    ]);

    $response2->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Webhook already processed',
            'duplicate' => true,
        ]);

    test()->product->refresh();
    expect(test()->product->stock)->toBe($initialStock);
});

test('webhook can arrive before order creation completes', function () {
    $newOrder = Order::factory()->create([
        'product_id' => test()->product->id,
        'quantity' => 3,
        'total' => 300.00,
        'status' => 'pending',
    ]);

    $response = postJson('/api/payments/webhook', [
        'idempotency_key' => 'early-webhook-key',
        'order_id' => $newOrder->id,
        'status' => 'success',
    ]);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'message' => 'Webhook processed successfully',
        ]);

    $newOrder->refresh();
    expect($newOrder->status)->toBe('paid');
});

test('webhook validation works correctly', function () {
    postJson('/api/payments/webhook', [
        'order_id' => test()->order->id,
        'status' => 'success',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['idempotency_key']);

    postJson('/api/payments/webhook', [
        'idempotency_key' => 'test-key',
        'status' => 'success',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['order_id']);

    postJson('/api/payments/webhook', [
        'idempotency_key' => 'test-key',
        'order_id' => test()->order->id,
        'status' => 'invalid-status',
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['status']);
});
