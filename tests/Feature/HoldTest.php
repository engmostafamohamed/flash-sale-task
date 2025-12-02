<?php

use App\Models\Product;
use function Pest\Laravel\postJson;

// Create a shared product before each test
beforeEach(function () {
    test()->product = Product::factory()->create([
        'stock' => 10,
        'reserved' => 0,
    ]);
});

test('can create a hold successfully', function () {
    $response = postJson('/api/holds', [
        'product_id' => test()->product->id,
        'qty' => 5,
    ]);

    $response->assertCreated()
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'hold_id',
                'expires_at',
                'quantity',
            ],
        ]);

    // Verify stock was reserved
    test()->product->refresh();
    expect(test()->product->reserved)->toBe(5);
});

test('cannot create hold with insufficient stock', function () {
    $response = postJson('/api/holds', [
        'product_id' => test()->product->id,
        'qty' => 20, // More than available
    ]);

    $response->assertStatus(400)
        ->assertJson([
            'success' => false,
            'message' => 'Insufficient stock available',
        ]);

    test()->product->refresh();
    expect(test()->product->reserved)->toBe(0);
});

test('parallel hold attempts do not oversell', function () {
    $product = Product::factory()->create([
        'stock' => 10,
        'reserved' => 0,
    ]);

    $responses = collect(range(1, 15))->map(function () use ($product) {
        return postJson('/api/holds', [
            'product_id' => $product->id,
            'qty' => 1,
        ]);
    });

    $successCount = $responses->filter(fn($r) => $r->status() === 201)->count();
    $failureCount = $responses->filter(fn($r) => $r->status() === 400)->count();

    expect($successCount)->toBe(10)
        ->and($failureCount)->toBe(5);

    $product->refresh();
    expect($product->reserved)->toBe(10);
});

test('hold validation works correctly', function () {
    postJson('/api/holds', ['qty' => 5])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['product_id']);

    postJson('/api/holds', ['product_id' => test()->product->id])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['qty']);

    postJson('/api/holds', [
        'product_id' => test()->product->id,
        'qty' => 0,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['qty']);

    postJson('/api/holds', [
        'product_id' => test()->product->id,
        'qty' => -5,
    ])->assertStatus(422)
        ->assertJsonValidationErrors(['qty']);
});
