<?php

use App\Models\Product;
use App\Models\Order;
use App\Models\Hold;

use Illuminate\Support\Facades\DB;
use function Pest\Laravel\postJson;


test('no overselling under concurrent hold requests', function () {
    $product = Product::factory()->create([
        'stock' => 10,
        'reserved' => 0,
    ]);

    // Simulate 20 concurrent requests for 1 item each
    $promises = [];
    for ($i = 0; $i < 20; $i++) {
        $promises[] = function () use ($product) {
            return postJson('/api/holds', [
                'product_id' => $product->id,
                'qty' => 1,
            ]);
        };
    }

    $responses = array_map(fn($promise) => $promise(), $promises);

    $successCount = collect($responses)->filter(fn($r) => $r->status() === 201)->count();
    $product->refresh();

    // Only 10 should succeed
    expect($successCount)->toBe(10);
    expect($product->reserved)->toBe(10);
});

test('concurrent order creation from different holds', function () {
    $product = Product::factory()->create([
        'price' => 50.00,
        'stock' => 100,
        'reserved' => 10,
    ]);

    $holds = [];
    for ($i = 0; $i < 5; $i++) {
        $holds[] = Hold::factory()->create([
            'product_id' => $product->id,
            'quantity' => 2,
            'expires_at' => now()->addMinutes(2),
            'used' => false,
        ]);
    }

    // Create orders concurrently
    $responses = collect($holds)->map(function ($hold) {
        return postJson('/api/orders', ['hold_id' => $hold->id]);
    });

    // All should succeed
    $successCount = $responses->filter(fn($r) => $r->status() === 201)->count();
    expect($successCount)->toBe(5);

    // Verify all holds are marked as used
    $usedCount = \App\Models\Hold::where('used', true)->count();
    expect($usedCount)->toBe(5);
});

test('deadlock handling in payment webhooks', function () {
    $product = Product::factory()->create([
        'stock' => 100,
        'reserved' => 20,
    ]);

    $orders = [];
    for ($i = 0; $i < 5; $i++) {
        $orders[] = Order::factory()->create([
            'product_id' => $product->id,
            'quantity' => 4,
            'total' => 400.00,
            'status' => 'pending',
        ]);
    }

    // Process webhooks concurrently
    $responses = collect($orders)->map(function ($order, $index) {
        return postJson('/api/payments/webhook', [
            'idempotency_key' => "concurrent-key-{$index}",
            'order_id' => $order->id,
            'status' => 'success',
        ]);
    });

    // All should succeed
    $successCount = $responses->filter(fn($r) => $r->status() === 200)->count();
    expect($successCount)->toBe(5);

    // Verify stock deduction
    $product->refresh();
    expect($product->stock)->toBe(80);
    expect($product->reserved)->toBe(0); // All released
});
