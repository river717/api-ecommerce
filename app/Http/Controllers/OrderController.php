<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreOrderRequest;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class OrderController extends Controller
{
    //Ordenes de un usuario
    #[OA\Get(
        path: '/api/orders',
        summary: 'Get authenticated user order history',
        tags: ['Orders'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Order history retrieved successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            )
        ]
    )]
    public function index(): JsonResponse
    {
        $orders = Order::where('user_id', auth()->id())
            ->with([
                'items.product',
                'payments',
            ])
            ->latest()
            ->get();

        return response()->json([
            'data' => $orders,
        ]);
    }

    #[OA\Post(
        path: '/api/orders',
        summary: 'Create a new order',
        tags: ['Orders'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['items'],
                properties: [
                    new OA\Property(
                        property: 'items',
                        type: 'array',
                        minItems: 1,
                        items: new OA\Items(
                            type: 'object',
                            required: ['product_id', 'quantity'],
                            properties: [
                                new OA\Property(
                                    property: 'product_id',
                                    type: 'integer',
                                    example: 2
                                ),
                                new OA\Property(
                                    property: 'quantity',
                                    type: 'integer',
                                    example: 2
                                ),
                            ]
                        )
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Order created successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error or insufficient stock'
            )
        ]
    )]
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $order = DB::transaction(function () use ($validated, $request) {
            $order = Order::create([
                'user_id' => $request->user()->id,
                'status' => 'pending',
                'total' => 0,
            ]);

            $total = 0;

            foreach ($validated['items'] as $item) {
                $product = Product::lockForUpdate()->findOrFail($item['product_id']);

                if (!$product->is_active) {
                    throw ValidationException::withMessages([
                        'items' => ["Product {$product->id} is not active."],
                    ]);
                }

                if ($product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => ["Insufficient stock for product {$product->id}."],
                    ]);
                }

                $unitPrice = $product->price;
                $subtotal = $unitPrice * $item['quantity'];

                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                ]);

                $product->decrement('stock', $item['quantity']);

                $total += $subtotal;
            }

            $order->update([
                'total' => $total,
            ]);

            return $order->load('items.product');
        });

        return response()->json([
            'message' => 'Order created successfully.',
            'data' => $order,
        ], 201);
    }
}
