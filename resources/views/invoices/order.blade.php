<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice - Order #{{ $order->order_number }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 20px;
            color: #333;
        }
        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border: 1px solid #ddd;
        }
        .header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #333;
        }
        .company-info h1 {
            margin: 0;
            font-size: 24px;
            color: #333;
        }
        .invoice-info {
            text-align: right;
        }
        .invoice-info h2 {
            margin: 0;
            font-size: 20px;
            color: #333;
        }
        .invoice-info p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        .details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .section h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        .section p {
            margin: 5px 0;
            font-size: 14px;
            color: #666;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        thead {
            background-color: #f5f5f5;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            font-weight: bold;
            color: #333;
        }
        tbody tr:hover {
            background-color: #f9f9f9;
        }
        .text-right {
            text-align: right;
        }
        .totals {
            margin-top: 20px;
            margin-left: auto;
            width: 300px;
        }
        .totals table {
            margin: 0;
        }
        .totals td {
            padding: 8px 12px;
            border-bottom: none;
        }
        .totals .total-row {
            border-top: 2px solid #333;
            font-weight: bold;
            font-size: 18px;
        }
        .footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
            text-align: center;
            color: #666;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <div class="header">
            <div class="company-info">
                <h1>TrustFactory E-Commerce</h1>
                <p>Order Invoice</p>
            </div>
            <div class="invoice-info">
                <h2>Invoice</h2>
                <p><strong>Order #:</strong> {{ $order->order_number }}</p>
                <p><strong>Date:</strong> {{ $order->created_at->format('F d, Y') }}</p>
                <p><strong>Status:</strong> {{ ucfirst($order->status) }}</p>
            </div>
        </div>

        <div class="details">
            <div class="section">
                <h3>Shipping Address</h3>
                <p><strong>{{ $order->shipping_name }}</strong></p>
                <p>{{ $order->shipping_email }}</p>
                @if($order->shipping_phone)
                    <p>{{ $order->shipping_phone }}</p>
                @endif
                <p>{{ $order->shipping_address }}</p>
                <p>
                    {{ $order->shipping_city }}
                    @if($order->shipping_state), {{ $order->shipping_state }}@endif
                    {{ $order->shipping_postal_code }}
                </p>
                <p>{{ $order->shipping_country }}</p>
            </div>

            <div class="section">
                <h3>Billing Address</h3>
                <p><strong>{{ $order->billing_name ?? $order->shipping_name }}</strong></p>
                <p>{{ $order->billing_email ?? $order->shipping_email }}</p>
                <p>{{ $order->billing_address ?? $order->shipping_address }}</p>
                <p>
                    {{ $order->billing_city ?? $order->shipping_city }}
                    @if($order->billing_state ?? $order->shipping_state), {{ $order->billing_state ?? $order->shipping_state }}@endif
                    {{ $order->billing_postal_code ?? $order->shipping_postal_code }}
                </p>
                <p>{{ $order->billing_country ?? $order->shipping_country }}</p>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Product</th>
                    <th class="text-right">Quantity</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($order->orderItems as $item)
                <tr>
                    <td>
                        <strong>{{ $item->product_name }}</strong>
                        @if($item->product_sku)
                            <br><small>SKU: {{ $item->product_sku }}</small>
                        @endif
                    </td>
                    <td class="text-right">{{ $item->quantity }}</td>
                    <td class="text-right">${{ number_format($item->price, 2) }}</td>
                    <td class="text-right">${{ number_format($item->subtotal, 2) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>

        <div class="totals">
            <table>
                <tr>
                    <td>Subtotal:</td>
                    <td class="text-right">${{ number_format($order->subtotal, 2) }}</td>
                </tr>
                @if($order->tax > 0)
                <tr>
                    <td>Tax:</td>
                    <td class="text-right">${{ number_format($order->tax, 2) }}</td>
                </tr>
                @endif
                <tr class="total-row">
                    <td>Total:</td>
                    <td class="text-right">${{ number_format($order->total, 2) }}</td>
                </tr>
            </table>
        </div>

        <div class="footer">
            <p>Thank you for your business!</p>
            <p>This is an automated invoice generated on {{ now()->format('F d, Y \a\t g:i A') }}</p>
        </div>
    </div>
</body>
</html>
