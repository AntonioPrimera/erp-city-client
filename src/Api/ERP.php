<?php

namespace ERPClient\Api;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Throwable;

class ERP
{
    //--- Authentication ----------------------------------------------------------------------------------------------

    /**
     * Attempt to login a user via ERP auth endpoint.
     */
    public static function login(array $credentials): ?Response
    {
        return self::postRequest(config('erp.endpoints.auth.login'), $credentials, 'Failed to login via ERP');
    }

    /**
     * Attempt to register a new user via ERP auth endpoint.
     */
    public static function register(array $payload): ?Response
    {
        return self::postRequest(config('erp.endpoints.auth.register'), $payload, 'Failed to register via ERP');
    }

    /**
     * Logout the current user from ERP.
     */
    public static function logout(string $userToken): ?Response
    {
        $endpoint = self::buildEndpoint(config('erp.endpoints.auth.logout'));

        if (! $endpoint) {
            return null;
        }

        try {
            return self::userHttpClient($userToken)->post($endpoint);
        } catch (Throwable $e) {
            Log::error('Failed to logout from ERP', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    //--- Products ----------------------------------------------------------------------------------------------------

    /**
     * Fetch products from ERP API, falling back to an empty array on error.
     */
    public static function fetchProducts(): array
    {
        $endpoint = self::buildEndpoint(config('erp.endpoints.products.all'));

        if (! $endpoint) {
            return [];
        }

        try {
            $response = self::httpClient()->get($endpoint);

            if ($response->successful()) {
                $data = $response->json('data');

                return is_array($data) ? $data : [];
            }
        } catch (Throwable $e) {
            Log::error('Failed to fetch ERP products', [
                'message' => $e->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * Fetch products from ERP API, falling back to an empty array on error.
     */
    public static function fetchMostSoldProducts(): array
    {
        $endpoint = self::buildEndpoint(config('erp.endpoints.products.mostSold'));

        if (! $endpoint) {
            return [];
        }

        try {
            $response = self::httpClient()->get($endpoint);

            if ($response->successful()) {
                $data = $response->json('data');

                return is_array($data) ? $data : [];
            }
        } catch (Throwable $e) {
            Log::error('Failed to fetch ERP most sold products', [
                'message' => $e->getMessage(),
            ]);
        }

        return [];
    }

    /**
     * Fetch a single product by id.
     */
    public static function fetchProduct(int|string $productId): ?array
    {
        $endpointTemplate = config('erp.endpoints.products.get');
        $endpointPath = $endpointTemplate ? sprintf($endpointTemplate, $productId) : null;
        $endpoint = $endpointPath ? self::buildEndpoint($endpointPath) : null;

        if (! $endpoint) {
            return null;
        }

        try {
            $response = self::httpClient()->get($endpoint);

            if ($response->successful()) {
                $data = $response->json('data');

                return is_array($data) ? $data : null;
            }
        } catch (Throwable $e) {
            Log::error('Failed to fetch ERP product', [
                'product_id' => $productId,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    public static function storeReview(string $token, int|string $productId, string $title, string $content, int $rating)
    {
        $endpointTemplate = config('erp.endpoints.products.review');
        $endpointPath = $endpointTemplate ? sprintf($endpointTemplate, $productId) : null;
        $endpoint = $endpointPath ? self::buildEndpoint($endpointPath) : null;

        if (!$endpoint) {
            return null;
        }

        try {
            return self::userHttpClient($token)->post($endpoint, [
                'title'   => $title,
                'content' => $content,
                'rating'  => $rating,
            ]);

        } catch (Throwable $e) {
            Log::error('Failed to post ERP product review', [
                'product_id' => $productId,
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    //--- Coupons ----------------------------------------------------------------------------------------------------

    /**
     * Validate a coupon code and return coupon data if valid.
     */
    public static function validateCoupon(string $code): ?array
    {
        $endpoint = self::buildEndpoint(config('erp.endpoints.coupons.validate'));

        if (! $endpoint) {
            return null;
        }

        try {
            $response = self::httpClient()->post($endpoint, [
                'code' => $code,
            ]);

            if ($response->successful()) {
                $data = $response->json('data');

                return is_array($data) ? $data : null;
            }
        } catch (Throwable $e) {
            Log::error('Failed to validate ERP coupon', [
                'message' => $e->getMessage(),
            ]);
        }

        return null;
    }

    //--- Favorites ---------------------------------------------------------------------------------------------------

    /**
     * Fetch favorites for an authenticated user.
     */
    public static function fetchFavorites(string $token): ?Response
    {
        $endpoint = self::buildEndpoint(config('erp.endpoints.favorites.list'));

        if (! $endpoint) {
            return null;
        }

        try {
            return self::userHttpClient($token)->get($endpoint);
        } catch (Throwable $e) {
            Log::error('Failed to fetch ERP favorites', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Toggle favorite for a product.
     */
    public static function toggleFavorite(string $token, int|string $productId): ?Response
    {
        $endpointTemplate = config('erp.endpoints.favorites.toggle');
        $endpoint = $endpointTemplate ? self::buildEndpoint(sprintf($endpointTemplate, $productId)) : null;

        if (! $endpoint) {
            return null;
        }

        try {
            return self::userHttpClient($token)->post($endpoint);
        } catch (Throwable $e) {
            Log::error('Failed to toggle ERP favorite', [
                'product_id' => $productId,
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    //--- Orders ------------------------------------------------------------------------------------------------------

    /**
     * Fetch orders for the authenticated user.
     */
    public static function fetchOrders(string $token): ?Response
    {
        $endpoint = self::buildEndpoint(config('erp.endpoints.orders.list'));

        if (! $endpoint) {
            return null;
        }

        try {
            return self::userHttpClient($token)->get($endpoint);
        } catch (Throwable $e) {
            Log::error('Failed to fetch ERP orders', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public static function storeOrder(
        string|null $userToken,
        string $name,
        string $address,
        ?string $company,
        string $phone,
        string $email,
        array $items, // quantity + price + currency + id of products
        ?string $paymentType = null,
        ?string $couponCode = null,
    ): ?Response
    {
        $payload = [
            'name'    => $name,
            'address' => $address,
            'company' => $company,
            'phone'   => $phone,
            'email'   => $email,
            'items'   => $items,
        ];

        if ($paymentType) {
            $payload['payment_type'] = $paymentType;
        }
        if ($couponCode) {
            $payload['coupon_code'] = $couponCode;
        }

        try {
            $response = $userToken
                // Authenticated user checkout
                ? self::userHttpClient($userToken)
                    ->post(
                        self::buildEndpoint(config('erp.endpoints.orders.store')),
                        $payload
                    )
                // Guest user checkout
                : self::postRequest(config('erp.endpoints.orders.store'), $payload, 'Failed to store order via ERP');

            $orderData = $response->json('data');

            if (is_array($orderData)) {
                self::sendOrderMail($orderData, $email);
            }

            return $response;
        } catch (Throwable $e) {
            Log::error('Failed to fetch ERP favorites', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    //--- Profile ----------------------------------------------------------------------------------------------------

    /**
     * Fetch the authenticated user's profile details from ERP.
     */
    public static function fetchProfile(string $token): ?Response
    {
        $endpoint = self::buildEndpoint(config('erp.endpoints.auth.me'));

        if (! $endpoint) {
            return null;
        }

        try {
            return self::userHttpClient($token)->get($endpoint);
        } catch (Throwable $e) {
            Log::error('Failed to fetch ERP profile', [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    //--- Helpers -----------------------------------------------------------------------------------------------------

    protected static function httpClient(): PendingRequest
    {
        return Http::withToken((string) config('erp.token'))
            ->acceptJson();
    }

    protected static function userHttpClient(string $token): PendingRequest
    {
        return Http::withToken($token)->acceptJson();
    }

    protected static function postRequest(?string $path, array $payload, string $logMessage): ?Response
    {
        $endpoint = $path ? self::buildEndpoint($path) : null;

        if (! $endpoint) {
            return null;
        }

        try {
            return self::httpClient()->post($endpoint, $payload);
        } catch (Throwable $e) {
            Log::error($logMessage, [
                'message' => $e->getMessage(),
            ]);

            return null;
        }
    }

    protected static function buildEndpoint(string $path): ?string
    {
        $baseUrl = rtrim((string) config('erp.api_url'), '/');
        $token = (string) config('erp.token');

        if ($baseUrl === '' || $token === '') {
            return null;
        }

        return $baseUrl . $path;
    }

    protected static function sendOrderMail(array $orderData, string $customerEmail): void
    {
        if (! (bool) config('erp.mail.enabled')) {
            return;
        }

        $adminTo = (string) config('erp.mail.admin_to');
        $newOrderMailable = config('erp.mail.new_order_mailable');
        $confirmationMailable = config('erp.mail.order_confirmation_mailable');
        $order = self::makeOrderModel($orderData);

        if (! $order) {
            return;
        }

        try {
            if ($adminTo !== '' && is_string($newOrderMailable) && class_exists($newOrderMailable)) {
                Mail::to($adminTo)->send(new $newOrderMailable($order));
            }

            if (is_string($confirmationMailable) && class_exists($confirmationMailable)) {
                Mail::to($customerEmail)->send(new $confirmationMailable($order));
            }
        } catch (Throwable $e) {
            Log::error('Failed to send ERP order mail', [
                'message' => $e->getMessage(),
            ]);
        }
    }

    protected static function makeOrderModel(array $orderData): ?object
    {
        $orderModel = config('erp.mail.order_model');

        if (! is_string($orderModel) || $orderModel === '' || ! class_exists($orderModel)) {
            return null;
        }

        if (method_exists($orderModel, 'make')) {
            $order = $orderModel::make($orderData);
        } else {
            $order = new $orderModel();

            if (method_exists($order, 'fill')) {
                $order->fill($orderData);
            }
        }

        if (is_object($order)) {
            $order->items = is_array($orderData['items'] ?? null) ? $orderData['items'] : [];
        }

        return $order;
    }
}
