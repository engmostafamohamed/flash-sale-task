<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\HoldService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class HoldController extends Controller
{
    public function __construct(
        private HoldService $holdService
    ) {}

    /**
     * Create a hold
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|integer|exists:products,id',
            'qty' => 'required|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $hold = $this->holdService->createHold(
                $request->input('product_id'),
                $request->input('qty')
            );

            return response()->json([
                'success' => true,
                'message' => 'Hold created successfully',
                'data' => $hold
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }
}
