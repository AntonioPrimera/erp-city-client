<!DOCTYPE html>
<html>
<head>
    <title>Ai primit o nouă solicitare!</title>
</head>
<body>
<h1>Ai primit o nouă solicitare!</h1>

<p><strong>Client:</strong> {{ $order->full_name }}</p>
<p><strong>Societate:</strong> {{ $order->company }}</p>
<p><strong>Email:</strong> {{ $order->email }}</p>
<p><strong>Telefon:</strong> {{ $order->phone }}</p>
<p><strong>Adresa:</strong> {{ $order->address }}</p>

<h3>Servicii solicitate:</h3>
<ul>
    @foreach($order->items as $item)
        @php
            $product = $item['product'] ?? [];
            $price = $item['price'] ?? null;
            $currency = $item['currency'] ?? null;
        @endphp
        <li>
            <strong>{{ $product['name'] ?? 'Produs' }}</strong>
            @if($price !== null)
                - {{ number_format((float) $price, 2, '.', ' ') }}{{ $currency ? ' ' . $currency : '' }}
            @endif
        </li>
    @endforeach
</ul>
@if($order->hasPricing())
    <p>
        <strong>Subtotal:</strong>
        {{ number_format($order->total_price, 2, '.', ' ') }}{{ $order->pricing_currency ? ' ' . $order->pricing_currency : '' }}
    </p>
    @if(!empty($order->discount_percent))
        <p>
            <strong>Cupon:</strong> {{ $order->coupon_code }}
        </p>
        <p>
            <strong>Discount ({{ $order->discount_percent }}%):</strong>
            -{{ number_format($order->discount_amount, 2, '.', ' ') }}{{ $order->pricing_currency ? ' ' . $order->pricing_currency : '' }}
        </p>
        <p>
            <strong>Total final:</strong>
            {{ number_format($order->discounted_total_price, 2, '.', ' ') }}{{ $order->pricing_currency ? ' ' . $order->pricing_currency : '' }}
        </p>
    @else
        <p>
            <strong>Total:</strong>
            {{ number_format($order->total_price, 2, '.', ' ') }}{{ $order->pricing_currency ? ' ' . $order->pricing_currency : '' }}
        </p>
    @endif
@endif

<p><a href="{{ config('erp.url') . '/orders/' . $order->id }}">Vezi comandă</a></p>
</body>
</html>
