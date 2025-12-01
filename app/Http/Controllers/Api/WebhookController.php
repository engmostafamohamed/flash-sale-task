<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PaymentWebhookService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class WebhookController extends Controller
{
    public function __construct(
        private PaymentWebhookService $webhookService
    ) {}

    /**
     * Handle payment webhook
     */
    public function handlePayment(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'idempotency_key' => 'required|string|max:255',
            'order_id' => 'required|integer|exists:orders,id',
            'status' => 'required|string|in:success,failed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->webhookService->processWebhook(
                $request->input('idempotency_key'),
                $request->input('order_id'),
                $request->input('status'),
                $request->all()
            );

            return response()->json($result);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
