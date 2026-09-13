<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Stripe\Stripe;

class PaymentController extends Controller
{
    public function store(Order $order): JsonResponse
    {
        if ($order->user_id !== auth()->id()) {
            return response()->json([
                'message' => 'You are not allowed to pay this order.',
            ], 403);
        }

        if ($order->status !== 'pending') {
            return response()->json([
                'message' => 'This order cannot be paid.',
            ], 422);
        }

        Stripe::setApiKey(config('services.stripe.secret'));

        $existingPayment = $order->payments()
            ->where('status', 'pending')
            ->first();

        if ($existingPayment) {
            return response()->json([
                'message' => 'PaymentIntent already exists.',
                'payment_intent_id' => $existingPayment->stripe_payment_intent_id,
            ]);
        }

        $paymentIntent = \Stripe\PaymentIntent::create([
            'amount' => (int) round($order->total * 100),
            'currency' => 'usd',
            'metadata' => [
                'order_id' => $order->id,
                'user_id' => $order->user_id,
            ],
        ]);

        $payment = $order->payments()->create([
            'stripe_payment_intent_id' => $paymentIntent->id,
            'amount' => $order->total,
            'currency' => 'usd',
            'status' => 'pending',
        ]);

        return response()->json([
            'message' => 'PaymentIntent created successfully.',
            'payment_intent_id' => $payment->stripe_payment_intent_id,
            'client_secret' => $paymentIntent->client_secret,
        ]);
    }
}