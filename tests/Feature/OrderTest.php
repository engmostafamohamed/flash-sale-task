<?php

use App\Models\Hold;
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
        'expires_at' => now()->addMinutes(2),
        'used' => false,
    ]);
});

test('can create order from valid hold', function () {
    $response = postJson('/api/orders', [
        'hold_id' => test()->hold->id,
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'order_id',
                'product_id',
                'quantity',
                'total',
                'status',
            ],
        ])
        ->assertJsonPath('data.quantity', 5)
        ->assertJsonPath('data.total', '500.00')
        ->assertJsonPath('data.status', 'pending');

    // Verify hold is marked as used
    test()->hold->refresh();
    expect(test()->hold->used)->toBeTrue();
});

test('cannot create order from expired hold', function () {
    $expiredHold = Hold::factory()->create([
        'product_id' => test()->product->id,
        'quantity' => 3,
        'expires_at' => now()->subMinutes(5),
        'used' => false,
    ]);

    $response = postJson('/api/orders', [
        'hold_id' => $expiredHold->id,
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Hold has expired',
        ]);
});

test('cannot use same hold twice', function () {
    // First order
    $response1 = postJson('/api/orders', [
        'hold_id' => test()->hold->id,
    ]);
    $response1->assertCreated();

    // Try to use same hold again
    $response2 = postJson('/api/orders', [
        'hold_id' => test()->hold->id,
    ]);

    $response2->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Hold has already been used',
        ]);
});

test('order validation works correctly', function () {
    postJson('/api/orders', [])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['hold_id']);

    postJson('/api/orders', ['hold_id' => 99999])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['hold_id']);
});
