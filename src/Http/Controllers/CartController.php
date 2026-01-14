<?php

namespace ERPClient\Http\Controllers;

use ERPClient\Api\ERP;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class CartController extends Controller
{
    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function index(): JsonResponse
    {
        $cart = session()->get('cart', []);

        return response()->json(array_values($cart));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function add(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id'       => ['required'],
            'quantity' => ['required', 'integer', 'min:1'],
            'price'    => ['nullable', 'numeric', 'min:0'],

        ]);

        $id = $validated['id'];
        $quantity = $validated['quantity'];
        $price = $validated['price'] ?? null;

        $cart = session()->get('cart', []);

        // For now, a product can only be added once to the cart if it doesn't have a price
        // (meaning it is a service)
        if (isset($cart[$id]) && is_numeric((float) $price) && (float) $price > 0) {
            $cart[$id]['quantity'] += $quantity;
        } else {
            $cart[$id] = [
                'id'       => $id,
                'quantity' => $quantity,
                'price'    => (float) $price,
            ];
        }

        session()->put('cart', $cart);

        return response()->json(array_values($cart));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'id'       => ['required'],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $id = $validated['id'];
        $quantity = $validated['quantity'];

        $cart = session()->get('cart', []);

        if (isset($cart[$id])) {
            $cart[$id]['quantity'] = $quantity;
        }

        session()->put('cart', $cart);

        return response()->json(array_values($cart));
    }

    /**
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function remove($id): JsonResponse
    {
        $cart = session()->get('cart', []);
        unset($cart[$id]);
        session()->put('cart', $cart);

        return response()->json(array_values($cart));
    }

    public function clear(): JsonResponse
    {
        session()->forget('cart');

        return response()->json([]);
    }

    public function validateAddress(Request $request): Response
    {
        $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'phone'       => ['required', 'string', 'max:20'],
            'email'       => ['required', 'string', 'email', 'max:255'],
            'town'        => ['required', 'string', 'max:255'],
            'company'     => ['required', 'string', 'max:255'],
        ]);

        return response('Address is valid', 200);
    }

    public function applyCoupon(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50'],
        ]);

        $coupon = ERP::validateCoupon($validated['code']);

        if (! $coupon) {
            return response()->json([
                'message' => 'Cupon invalid.',
            ], 404);
        }

        session()->put('coupon', [
            'code' => $coupon['code'] ?? $validated['code'],
            'discount_percent' => $coupon['discount_percent'] ?? null,
        ]);

        return response()->json([
            'data' => $coupon,
        ]);
    }

    public function clearCoupon(): JsonResponse
    {
        session()->forget('coupon');

        return response()->json([
            'data' => null,
        ]);
    }
}
