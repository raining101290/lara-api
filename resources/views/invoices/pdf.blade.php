<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->invoice_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; }
        h1, h2, h3 { margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #000; padding: 8px; text-align: left; }
    </style>
</head>
<body>
    <h2>Invoice: {{ $invoice->invoice_no }}</h2>
    <p><strong>Customer ID:</strong> {{ $invoice->customer_id }}</p>
    <p><strong>Domain:</strong> {{ $invoice->order->domain_name ?? 'N/A' }}</p>
    <p><strong>Amount:</strong> ৳{{ number_format($invoice->amount, 2) }}</p>
    <p><strong>Status:</strong> {{ ucfirst($invoice->status) }}</p>

    <hr>
    <p>Thank you for your order!</p>
</body>
</html>
