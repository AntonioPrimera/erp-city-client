<?php

namespace ERPClient\Http\Controllers;

use ERPClient\Api\ERP;
use ERPClient\Enums\PaymentType;
use ERPClient\Services\ERPAuthService;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class OrderController extends Controller
{
    public function __construct(protected ERPAuthService $authService)
    {
    }

    public function store(Request $request): Response
    {
        $validated = $request->validate([
            'name'               => ['required', 'string', 'max:255'],
            'address'            => ['required', 'string', 'max:255'],
            'company'            => ['nullable', 'string', 'max:255'],
            'phone'              => ['required', 'string', 'max:20'],
            'email'              => ['required', 'string', 'email', 'max:255'],
            'items'              => ['required', 'array'],
            'items.*.quantity'   => ['required', 'integer', 'min:1'],
            'items.*.id'         => ['required', 'integer'], // the ERP product id
            'payment_type'       => ['nullable', Rule::enum(PaymentType::class)],
            'coupon_code'        => ['nullable', 'string', 'max:50'],
        ]);

        $items = $this->resolvePricedItems($validated['items']);
        $total = $this->calculateTotal($items);
        $paymentType = PaymentType::tryFrom($validated['payment_type'] ?? '') ?? null;
        $cardPaymentsEnabled = (bool) config('erp.payments.card_enabled', true);
        $couponCode = $validated['coupon_code'] ?? null;

        if ($couponCode) {
            $coupon = ERP::validateCoupon($couponCode);

            if (! $coupon) {
                return response([
                    'message' => 'Cupon invalid.',
                    'errors' => ['coupon_code' => ['Cupon invalid.']],
                ], 422);
            }

            $total = $this->applyDiscount($total, (float) ($coupon['discount_percent'] ?? 0));
        }

        if (! $cardPaymentsEnabled && $total > 0) {
            $paymentType = PaymentType::OnDelivery;
        }

        if ($total > 0 && $cardPaymentsEnabled && ! $paymentType) {
            return response([
                'message' => 'Payment type is required for paid orders.',
                'errors' => ['payment_type' => ['Payment type is required for paid orders.']],
            ], 422);
        }

        // If the user token is null, it means we store the order as guest
        $userToken = $this->authService->token();

        if ($total > 0 && $cardPaymentsEnabled && $paymentType === PaymentType::Card) {
            $pendingOrderId = (string) Str::uuid();
            $pendingOrders = session()->get('pending_orders', []);
            $pendingOrders[$pendingOrderId] = [
                'user_token'   => $userToken,
                'name'         => $validated['name'],
                'address'      => $validated['address'],
                'company'      => $validated['company'],
                'phone'        => $validated['phone'],
                'email'        => $validated['email'],
                'items'        => $items,
                'payment_type' => $paymentType->value,
                'coupon_code'  => $couponCode,
            ];
            session()->put('pending_orders', $pendingOrders);

            $checkout = $this->createStripeCheckoutSession($total, $pendingOrderId, $validated['email']);

            if (! $checkout['ok']) {
                return response([
                    'message' => 'Failed to start card payment.',
                ], 502);
            }

            return response(['checkout_url' => $checkout['url']]);
        }

        $response = ERP::storeOrder(
            $userToken ?? null,
            $validated['name'],
            $validated['address'],
            $validated['company'],
            $validated['phone'],
            $validated['email'],
            $items,
            $cardPaymentsEnabled ? $paymentType?->value : null,
            $couponCode,
        );

        if (! $response) {
            return response([
                'message' => 'Failed to store order.',
            ], 502);
        }

        if ($response->getStatusCode() !== 201) {
            return response($response->json(), $response->getStatusCode());
        }

        return response($response->json());
    }

    public function checkoutSuccess(Request $request): Response
    {
        $validated = $request->validate([
            'pending_order_id' => ['required', 'string'],
            'session_id' => ['required', 'string'],
        ]);

        $pendingOrderId = $validated['pending_order_id'];
        $pendingOrders = session()->get('pending_orders', []);
        $pendingOrder = $pendingOrders[$pendingOrderId] ?? null;

        if (! $pendingOrder) {
            return response('Order data missing or expired.', 410);
        }

        $checkoutSession = $this->fetchStripeCheckoutSession($validated['session_id']);

        if (! $checkoutSession['ok'] || ($checkoutSession['data']['payment_status'] ?? '') !== 'paid') {
            return response('Payment not confirmed.', 402);
        }

        $response = ERP::storeOrder(
            $pendingOrder['user_token'] ?? null,
            $pendingOrder['name'],
            $pendingOrder['address'],
            $pendingOrder['company'],
            $pendingOrder['phone'],
            $pendingOrder['email'],
            $pendingOrder['items'],
            $pendingOrder['payment_type'] ?? null,
            $pendingOrder['coupon_code'] ?? null,
        );

        unset($pendingOrders[$pendingOrderId]);
        session()->put('pending_orders', $pendingOrders);
        session()->forget('cart');

        return $response->getStatusCode() === 201
            ? response()->redirectToRoute('shop', ['payment' => 'success'])
            : response('Failed to store order.', $response->getStatusCode());
    }

    public function checkoutCancel(): Response
    {
        return response()->redirectToRoute('shop', ['payment' => 'cancelled']);
    }

    protected function calculateTotal(array $items): float
    {
        $total = 0;

        foreach ($items as $item) {
            $price = isset($item['price']) ? (float) $item['price'] : 0;
            $quantity = isset($item['quantity']) ? (int) $item['quantity'] : 0;
            $total += $price * $quantity;
        }

        return $total;
    }

    protected function applyDiscount(float $total, float $percent): float
    {
        if ($percent <= 0) {
            return $total;
        }

        $discount = ($total * $percent) / 100;

        return max(0, round($total - $discount, 2));
    }

    protected function resolvePricedItems(array $items): array
    {
        $products = collect(ERP::fetchProducts())->keyBy('id');

        if ($products->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => 'Nu am putut valida produsele din ERP.',
            ]);
        }

        $pricedItems = [];

        foreach ($items as $item) {
            $product = $products->get($item['id']);

            if (! $product) {
                throw ValidationException::withMessages([
                    'items' => 'Produsele selectate nu sunt valide.',
                ]);
            }

            $price = is_numeric($product['price'] ?? null) ? (float) $product['price'] : 0;
            $currency = $product['currency'] ?? config('services.stripe.currency', 'ron');

            $pricedItems[] = [
                'id' => (int) $item['id'],
                'quantity' => (int) $item['quantity'],
                'price' => $price,
                'currency' => $currency,
            ];
        }

        return $pricedItems;
    }

    protected function createStripeCheckoutSession(float $total, string $pendingOrderId, ?string $email = null): array
    {
        $secret = config('services.stripe.secret');

        if (! $secret) {
            return ['ok' => false];
        }

        $amount = (int) round($total * 100);
        $successUrl = route('erp.checkout.success', ['pending_order_id' => $pendingOrderId])
            . '&session_id={CHECKOUT_SESSION_ID}';
        $cancelUrl = route('erp.checkout.cancel');

        $response = Http::asForm()
            ->withBasicAuth($secret, '')
            ->post('https://api.stripe.com/v1/checkout/sessions', [
                'locale' => 'ro',
                'mode' => 'payment',
                'success_url' => $successUrl,
                'cancel_url' => $cancelUrl,
                'customer_email' => $email,
                'line_items' => [
                    [
                        'quantity' => 1,
                        'price_data' => [
                            'currency' => config('services.stripe.currency', 'ron'),
                            'product_data' => [
                                'name' => 'Comanda Prosys',
                            ],
                            'unit_amount' => $amount,
                        ],
                    ],
                ],
                'client_reference_id' => $pendingOrderId,
            ]);

        if (! $response->successful()) {
            return ['ok' => false];
        }

        return [
            'ok' => true,
            'url' => $response->json('url'),
        ];
    }

    protected function fetchStripeCheckoutSession(string $sessionId): array
    {
        $secret = config('services.stripe.secret');

        if (! $secret) {
            return ['ok' => false];
        }

        $response = Http::withBasicAuth($secret, '')
            ->get("https://api.stripe.com/v1/checkout/sessions/{$sessionId}");

        if (! $response->successful()) {
            return ['ok' => false];
        }

        return [
            'ok' => true,
            'data' => $response->json(),
        ];
    }
}
