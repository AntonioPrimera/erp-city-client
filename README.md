# ERP City Client (Laravel)

Reusable ERP client used by multiple Laravel apps integrating with https://github.com/AntonioPrimera/erp-city. This package centralizes the ERP API client, auth session helper, ERP controllers/routes, enums, and order mail templates.

## Requirements
- PHP ^8.2
- Laravel ^12

## Installation (local path)
1) Add the path repository and require the package in your app's `composer.json`:

```json
{
  "repositories": [
    {
    "type": "path",
    "url": "../erp-city-client",
      "options": { "symlink": true }
    }
  ],
  "require": {
    "erp-city/erp-city-client": "*"
  }
}
```

2) Install the package:

```bash
composer install erp-city/erp-city-client
```

3) Publish the config:

```bash
php artisan vendor:publish --tag=erp-city-client-config
```

4) (Optional) Publish mail views:

```bash
php artisan vendor:publish --tag=erp-city-client-views
```

Published views land in `resources/views/vendor/erp-city-client` and are referenced via the `erp-city-client::` namespace.

## Environment variables
Add these to `.env`:

```env
ERP_URL=
ERP_API_URL=
ERP_API_TOKEN=

# Optional: mail notifications for orders
ERP_MAIL_ENABLED=false
ERP_MAIL_ADMIN_TO=
ERP_ORDER_MODEL=
ERP_NEW_ORDER_MAILABLE=
ERP_ORDER_CONFIRMATION_MAILABLE=
ERP_ROUTES_ENABLED=true

# Optional: Stripe payments (uses services.stripe.*)
STRIPE_SECRET=
STRIPE_CURRENCY=ron
ERP_CARD_PAYMENTS_ENABLED=true
```

## Config
The published config file is `config/erp.php`.

If you need to override endpoints or mail settings, edit `config/erp.php` in your app. For example:

```php
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
```

Mail is sent only if:
- `ERP_MAIL_ENABLED=true`
- `order_model` and the mailable classes exist

## Usage
### ERP API client
```php
use ERPClient\Api\ERP;

$products = ERP::fetchProducts();
$product = ERP::fetchProduct($id);
$orders = ERP::fetchOrders($token);
```

### ERP auth session helper
```php
use ERPClient\Services\ERPAuthService;

$auth = app(ERPAuthService::class);
$token = $auth->token();
```

### ERP auth requests
```php
use ERPClient\Http\Requests\ERPLoginRequest;
use ERPClient\Http\Requests\ERPRegisterRequest;
```

### ERP controllers & routes
The package registers routes under the `erp/` path prefix, with route names prefixed by `erp.` (for example `erp.orders.store` -> `POST /erp/orders`, `erp.auth.login` -> `POST /erp/auth/login`, `erp.paymentMethods` -> `GET /erp/payment-methods`). You can disable auto-loading with `ERP_ROUTES_ENABLED=false`.

### Enums
```php
use ERPClient\Enums\OrderStatus;
use ERPClient\Enums\PaymentType;
```

## Notes
- The package uses the same config key (`erp`) across all apps.
- Order notification emails and card payments via Stripe are optional and fully controlled by config/env.
