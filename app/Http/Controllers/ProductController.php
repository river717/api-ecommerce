<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use OpenApi\Attributes as OA;

class ProductController extends Controller
{
    #[OA\Get(
        path: '/api/products',
        summary: 'List active products',
        tags: ['Products'],
        responses: [
            new OA\Response(
                response: 200,
                description: 'List of active products'
            )
        ]
    )]
    public function index(): JsonResponse
    {
        $products = Product::where('is_active', true)->get();

        return response()->json([
            'data' => $products,
        ]);
    }

    //Create
    #[OA\Post(
        path: '/api/products',
        summary: 'Create a product',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'price', 'stock'],
                properties: [
                    new OA\Property(
                        property: 'name',
                        type: 'string',
                        example: 'Laptop Pro 15'
                    ),
                    new OA\Property(
                        property: 'description',
                        type: 'string',
                        nullable: true,
                        example: 'Laptop de alto rendimiento.'
                    ),
                    new OA\Property(
                        property: 'price',
                        type: 'number',
                        format: 'float',
                        example: 1299.99
                    ),
                    new OA\Property(
                        property: 'stock',
                        type: 'integer',
                        example: 15
                    ),
                    new OA\Property(
                        property: 'is_active',
                        type: 'boolean',
                        example: true
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Product created successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            )
        ]
    )]
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = Product::create($request->validated());

        return response()->json([
            'message' => 'Product created successfully.',
            'data' => $product,
        ], 201);
    }

    //Read
    #[OA\Get(
        path: '/api/products/{product}',
        summary: 'Get a product by ID',
        tags: ['Products'],
        parameters: [
            new OA\Parameter(
                name: 'product',
                description: 'Product ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product retrieved successfully'
            ),
            new OA\Response(
                response: 404,
                description: 'Product not found'
            )
        ]
    )]
    public function show(Product $product): JsonResponse
    {
        return response()->json([
            'data' => $product,
        ]);
    }

    //Update
    #[OA\Put(
        path: '/api/products/{product}',
        summary: 'Update a product',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'product',
                description: 'Product ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            )
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(
                        property: 'name',
                        type: 'string',
                        example: 'Laptop Pro 15 Updated'
                    ),
                    new OA\Property(
                        property: 'description',
                        type: 'string',
                        nullable: true,
                        example: 'Laptop actualizado.'
                    ),
                    new OA\Property(
                        property: 'price',
                        type: 'number',
                        format: 'float',
                        example: 1399.99
                    ),
                    new OA\Property(
                        property: 'stock',
                        type: 'integer',
                        example: 20
                    ),
                    new OA\Property(
                        property: 'is_active',
                        type: 'boolean',
                        example: true
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product updated successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 404,
                description: 'Product not found'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            )
        ]
    )]
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product->update($request->validated());

        return response()->json([
            'message' => 'Product updated successfully.',
            'data' => $product->fresh(),
        ]);
    }

    //Delete
    #[OA\Delete(
        path: '/api/products/{product}',
        summary: 'Delete a product',
        tags: ['Products'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'product',
                description: 'Product ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 1
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Product deleted successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 404,
                description: 'Product not found'
            )
        ]
    )]
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }
}
