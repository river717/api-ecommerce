<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterRequest;
use App\Http\Requests\LoginRequest;
use Tymon\JWTAuth\Facades\JWTAuth;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    /**
     * Register a new customer.
     */
    #[OA\Post(
        path: '/api/auth/register',
        summary: 'Register a new user',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['name', 'email', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(
                        property: 'name',
                        type: 'string',
                        example: 'Juan Pérez'
                    ),
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        format: 'email',
                        example: 'juan@example.com'
                    ),
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        format: 'password',
                        example: 'password123'
                    ),
                    new OA\Property(
                        property: 'password_confirmation',
                        type: 'string',
                        format: 'password',
                        example: 'password123'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'User registered successfully'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            )
        ]
    )]
    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
        ]);

        return response()->json([
            'message' => 'User registered successfully.',
            'user' => $user,
        ], 201);
    }

    /**
    * Authenticate user and return JWT token.
    */
    #[OA\Post(
        path: '/api/auth/login',
        summary: 'Authenticate user and generate JWT',
        tags: ['Auth'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ['email', 'password'],
                properties: [
                    new OA\Property(
                        property: 'email',
                        type: 'string',
                        format: 'email',
                        example: 'marvin.test@example.com'
                    ),
                    new OA\Property(
                        property: 'password',
                        type: 'string',
                        format: 'password',
                        example: 'password123'
                    ),
                ]
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Login successful'
            ),
            new OA\Response(
                response: 401,
                description: 'Invalid credentials'
            ),
            new OA\Response(
                response: 422,
                description: 'Validation error'
            )
        ]
    )]
    public function login(LoginRequest $request): JsonResponse
        {
        $credentials = $request->validated();

        if (!$token = JWTAuth::attempt($credentials)) {
            return response()->json([
            'message' => 'Invalid credentials.',
            ], 401);
        }

        return response()->json([
            'message' => 'Login successful.',
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }

    #[OA\Get(
        path: '/api/auth/me',
        summary: 'Get authenticated user',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Authenticated user information'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            )
        ]
    )]
    public function me(): JsonResponse
    {
        return response()->json([
            'user' => JWTAuth::user(),
        ]);
    }

    #[OA\Post(
        path: '/api/auth/logout',
        summary: 'Logout authenticated user',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Logout successful'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            )
        ]
    )]
    public function logout(): JsonResponse
    {
        JWTAuth::invalidate(JWTAuth::getToken());

        return response()->json([
            'message' => 'Logout successful.',
        ]);
    }

    #[OA\Post(
        path: '/api/auth/refresh',
        summary: 'Refresh JWT token',
        tags: ['Auth'],
        security: [['bearerAuth' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Token refreshed successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            )
        ]
    )]
    public function refresh(): JsonResponse
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());

        return response()->json([
            'access_token' => $token,
            'token_type' => 'Bearer',
            'expires_in' => JWTAuth::factory()->getTTL() * 60,
        ]);
    }
}