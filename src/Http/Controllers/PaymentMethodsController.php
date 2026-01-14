<?php

namespace ERPClient\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Routing\Controller;

class PaymentMethodsController extends Controller
{
    public function index(): JsonResponse
    {
        $cardEnabled = (bool) config('erp.payments.card_enabled', true);
        $methods = ['on_delivery'];

        if ($cardEnabled) {
            $methods[] = 'card';
        }

        return response()->json([
            'data' => [
                'card_enabled' => $cardEnabled,
                'methods' => $methods,
            ],
        ]);
    }
}
