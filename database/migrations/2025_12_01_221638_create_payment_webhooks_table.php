<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('idempotency_key')->unique();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('success'); // success, failed
            $table->boolean('processed')->default(false);
            $table->json('payload')->nullable(); // Store full webhook data
            $table->timestamps();

            // Indexes
            $table->index(['idempotency_key', 'processed']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_webhooks');
    }
};
