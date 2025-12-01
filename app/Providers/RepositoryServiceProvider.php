<?php
// app/Providers/RepositoryServiceProvider.php

namespace App\Providers;

use App\Repositories\Contracts\HoldRepositoryInterface;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\PaymentWebhookRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use App\Repositories\HoldRepository;
use App\Repositories\OrderRepository;
use App\Repositories\PaymentWebhookRepository;
use App\Repositories\ProductRepository;
use Illuminate\Support\ServiceProvider;

class RepositoryServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->bind(ProductRepositoryInterface::class, ProductRepository::class);
        $this->app->bind(HoldRepositoryInterface::class, HoldRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(PaymentWebhookRepositoryInterface::class, PaymentWebhookRepository::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
    }
}
