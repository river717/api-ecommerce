<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;
use Stripe\Stripe;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PaymentController extends Controller
{

    #[OA\Post(
        path: '/api/orders/{order}/payment',
        summary: 'Create a Stripe PaymentIntent for an order',
        tags: ['Payments'],
        security: [['bearerAuth' => []]],
        parameters: [
            new OA\Parameter(
                name: 'order',
                description: 'Order ID',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
                example: 3
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'PaymentIntent created successfully'
            ),
            new OA\Response(
                response: 401,
                description: 'Unauthenticated'
            ),
            new OA\Response(
                response: 403,
                description: 'User is not allowed to pay this order'
            ),
            new OA\Response(
                response: 422,
                description: 'Order cannot be paid'
            )
        ]
    )]
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
            'automatic_payment_methods' => [
                'enabled' => true,
                'allow_redirects' => 'never',
            ],
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

    #[OA\Post(
        path: '/api/stripe/webhook',
        summary: 'Receive Stripe webhook events',
        tags: ['Payments'],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                type: 'object',
                description: 'Stripe webhook event payload'
            )
        ),
        responses: [
            new OA\Response(
                response: 200,
                description: 'Webhook received successfully'
            ),
            new OA\Response(
                response: 400,
                description: 'Invalid payload or signature'
            )
        ]
    )]
    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Stripe-Signature');

        try {
            $event = \Stripe\Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
        } catch (\UnexpectedValueException $e) {
            return response()->json([
                'message' => 'Invalid payload.',
            ], 400);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            return response()->json([
                'message' => 'Invalid signature.',
            ], 400);
        }

        //Pagos exitosos
        if ($event->type === 'payment_intent.succeeded') {
            $paymentIntent = $event->data->object;

            $payment = \App\Models\Payment::where(
                'stripe_payment_intent_id',
                $paymentIntent->id
            )->first();

            if ($payment) {
                $payment->update([
                    'status' => 'succeeded',
                ]);

                $payment->order()->update([
                    'status' => 'paid',
                ]);
            }
        }

        //Pagos fallidos
        if (
            $event->type === 'payment_intent.payment_failed' ||
            $event->type === 'payment_intent.canceled'
        ) {
            $paymentIntent = $event->data->object;

            $payment = \App\Models\Payment::where(
                'stripe_payment_intent_id',
                $paymentIntent->id
            )->first();

            if ($payment) {
                $this->restoreStockAndCancelOrder($payment);
            }
        }

        return response()->json([
            'message' => 'Webhook received successfully.',
        ]);
    }

    private function restoreStockAndCancelOrder(\App\Models\Payment $payment): void
    {
        $order = $payment->order()->with('items')->first();

        if (!$order || $order->status === 'cancelled') {
            return;
        }

        \DB::transaction(function () use ($order, $payment) {
            foreach ($order->items as $item) {
                $item->product()->increment('stock', $item->quantity);
            }

            $payment->update([
                'status' => 'failed',
            ]);

            $order->update([
                'status' => 'cancelled',
            ]);
        });
    }
}