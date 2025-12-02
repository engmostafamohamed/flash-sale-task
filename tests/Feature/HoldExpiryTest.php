<?php

use App\Jobs\ReleaseExpiredHoldsJob;
use App\Models\Hold;
use App\Models\Product;

test('expired holds release reserved stock', function () {
    $product = Product::factory()->create([
        'stock' => 100,
        'reserved' => 30,
    ]);

    // Create expired holds
    Hold::factory()->create([
        'product_id' => $product->id,
        'quantity' => 10,
        'expires_at' => now()->subMinutes(5),
        'used' => false,
        'released' => false,
    ]);

    Hold::factory()->create([
        'product_id' => $product->id,
        'quantity' => 20,
        'expires_at' => now()->subMinutes(3),
        'used' => false,
        'released' => false,
    ]);

    // Create valid hold (should not be released)
    $validHold = Hold::factory()->create([
        'product_id' => $product->id,
        'quantity' => 5,
        'expires_at' => now()->addMinutes(2),
        'used' => false,
        'released' => false,
    ]);

    // Run the job
    $job = new ReleaseExpiredHoldsJob();
    $job->handle(app(\App\Services\HoldService::class));

    // Verify reserved stock was released
    $product->refresh();
    expect($product->reserved)->toBe(0); // Only valid hold remains

    // Verify holds are marked as released
    $expiredHolds = Hold::where('released', true)->count();
    expect($expiredHolds)->toBe(2);

    // Verify valid hold is untouched
    $validHold->refresh();
    expect($validHold->released)->toBeFalse();
});
