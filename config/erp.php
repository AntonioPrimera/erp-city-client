<?php

return [
    'url'     => env('ERP_URL'),
    'api_url' => env('ERP_API_URL'),
    'token'   => env('ERP_API_TOKEN'),

    'mail' => [
        'enabled' => env('ERP_MAIL_ENABLED', false),
        'admin_to' => env('ERP_MAIL_ADMIN_TO', env('MAIL_CONTACT_ADDRESS')),
        'order_model' => env('ERP_ORDER_MODEL', ERPClient\Models\Order::class),
        'new_order_mailable' => env('ERP_NEW_ORDER_MAILABLE', ERPClient\Mail\NewOrderNotification::class),
        'order_confirmation_mailable' => env('ERP_ORDER_CONFIRMATION_MAILABLE', ERPClient\Mail\OrderConfirmation::class),
    ],

    'routes' => [
        'enabled' => env('ERP_ROUTES_ENABLED', true),
    ],

    'payments' => [
        'card_enabled' => env('ERP_CARD_PAYMENTS_ENABLED', true),
    ],

    'endpoints' => [
        'products' => [
            'all' => '/products',
            'get' => '/products/{id}',
            'mostSold' => '/products/most-sold',
        ],
        'coupons' => [
            'validate' => '/coupons/validate',
        ],

        'auth' => [
            'login'    => '/auth/login',
            'register' => '/auth/register',
            'logout'   => '/auth/logout',
            'me'       => '/auth/me',
        ],

        'favorites' => [
            'list'   => '/favorites',
            'toggle' => '/favorites/%s/toggle',
        ],

        'orders' => [
            'store' => '/orders',
            'list'  => '/orders',
        ],
    ],
];
