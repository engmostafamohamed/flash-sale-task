<?php

use App\Models\Product;
use function Pest\Laravel\getJson;

beforeEach(function () {
    test()->product = Product::factory()->create([
        'name' => 'Test Product',
        'price' => 99.99,
        'stock' => 100,
        'reserved' => 10,
    ]);
});

test('can get product details', function () {
    $response = getJson("/api/products/" . test()->product->id);

    $response->assertOk()
        ->assertJson([
            'success' => true,
            'data' => [
                'id' => test()->product->id,
                'name' => 'Test Product',
                'price' => '99.99',
                'available_stock' => 90,
            ],
        ]);
});

test('returns 404 for non-existent product', function () {
    $response = getJson('/api/products/99999');

    $response->assertNotFound()
        ->assertJson([
            'success' => false,
            'message' => 'Product not found',
        ]);
});

test('product caching works correctly', function () {
    $response1 = getJson("/api/products/" . test()->product->id);
    $response1->assertOk();

    test()->product->update(['stock' => 200]);

    $response2 = getJson("/api/products/" . test()->product->id);
    $response2->assertOk();

    sleep(6); // Wait for cache expiry

    $response3 = getJson("/api/products/" . test()->product->id);
    $response3->assertOk()
        ->assertJsonPath('data.total_stock', 100);
});
