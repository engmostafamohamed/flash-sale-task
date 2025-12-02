<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentWebhookService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Order;
use App\Models\Product;
use App\Models\PaymentWebhook;

class WebhookController extends Controller
{
    public function __construct(
        private PaymentWebhookService $webhookService
    ) {}

    /**
     * Handle payment webhook
     */
    public function handlePayment(Request $request)
    {
        $request->validate([
            'order_id' => 'required|exists:orders,id',
            'status' => 'required|in:success,failed',
            'idempotency_key' => 'required|string',
        ]);

        // Check idempotency first
        if (PaymentWebhook::where('idempotency_key', $request->idempotency_key)->exists()) {
            return response()->json([
                'success' => true,
                'message' => 'Webhook already processed',
                'duplicate' => true,
            ]);
        }

        DB::transaction(function () use ($request) {
            $order = Order::lockForUpdate()->find($request->order_id);
            $product = Product::lockForUpdate()->find($order->product_id);

            if ($request->status === 'success') {
                if ($product->reserved < $order->quantity) {
                    throw new \Exception('Reserved quantity insufficient');
                }
                $product->stock -= $order->quantity;
                $product->reserved -= $order->quantity;
                $order->status = 'paid';
            } else {
                $product->reserved -= $order->quantity;
                $order->status = 'cancelled';
            }

            $product->save();
            $order->save();

            PaymentWebhook::create([
                'idempotency_key' => $request->idempotency_key,
                'order_id' => $order->id,
            ]);
        });

        return response()->json([
            'success' => true,
            'message' => 'Webhook processed successfully',
            'duplicate' => false,
        ]);
    }
}
