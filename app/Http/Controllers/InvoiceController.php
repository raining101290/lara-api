<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Mail;
use App\Mail\InvoiceMail;

class InvoiceController extends Controller
{
    // Admin: list all invoices (with optional filters)
    public function index(Request $request)
    {
        $query = Invoice::with('order', 'order.documents')->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        $invoices = $query->paginate(15);

        return ApiResponse::success('Invoices retrieved', $invoices);
    }

    // Customer: list own invoices
    public function myInvoices(Request $request)
    {
        $customer = auth('customer_api')->user();

        if (!$customer) {
            return ApiResponse::error('Unauthorized access. Token required.', 401);
        }

        $query = Invoice::with('order')
            ->where('customer_id', $customer->id)
            ->latest();

        // Search by invoice number or domain name
        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                ->orWhereHas('order', function ($q2) use ($search) {
                    $q2->where('domain_name', 'like', "%{$search}%");
                });
            });
        }

        // Filter by status
        if ($status = $request->query('status')) {
            $status = strtolower($status);
            if (in_array($status, ['paid', 'unpaid'])) {
                $query->where('status', $status);
            }
        }

        // Pagination (default 10)
        $perPage = (int) $request->query('limit', 10);
        $invoices = $query->paginate($perPage);

        return ApiResponse::success('Invoices fetched', $invoices);
    }

    // Customer/Admin: show single invoice (customer must own)
    public function show($id)
    {
        $invoice = Invoice::with('order')->find($id);
        if (! $invoice) {
            return ApiResponse::error('Invoice not found', null, 404);
        }

        // if customer guard, ensure owner
        if ($guard = auth('customer_api')->user()) {
            if ($invoice->customer_id !== $guard->id) {
                return ApiResponse::error('Unauthorized', null, 403);
            }
        }

        return ApiResponse::success('Invoice fetched', $invoice);
    }

    // Download PDF (uses barryvdh/laravel-dompdf)
    public function downloadPdf($id)
    {
        $invoice = Invoice::with('order')->find($id);
        if (! $invoice) {
            return ApiResponse::error('Invoice not found', null, 404);
        }
        if (auth('customer_api')->check() && auth('customer_api')->id() !== $invoice->customer_id) {
            return ApiResponse::error('Unauthorized', null, 403);
        }

        $pdf = PDF::loadView('invoices.pdf', compact('invoice'));
        $filename = $invoice->invoice_no . '.pdf';

        return $pdf->download($filename);
    }

    // Admin: mark invoice paid
    public function markPaid($id)
    {
        $invoice = Invoice::find($id);
        if (! $invoice) {
            return ApiResponse::error('Invoice not found', null, 404);
        }

        $invoice->status = 'paid';
        $invoice->paid_at = now();
        $invoice->save();

        // Optionally update order status, trigger events, send email...
        return ApiResponse::success('Invoice marked as paid', $invoice);
    }

    // Helper: create & email invoice (you can call this from DomainOrderController)
    public static function createInvoiceForOrder($order)
    {
        $invoiceNo = 'INV-' . date('Ymd') . '-' . str_pad($order->id, 5, '0', STR_PAD_LEFT);

        $invoice = Invoice::create([
            'customer_id' => $order->customer_id,
            'order_id'    => $order->id,
            'invoice_no'  => $invoiceNo,
            'amount'      => $order->amount,
            'status'      => 'unpaid',
        ]);

        // Send mail (InvoiceMail should accept invoice model)
        Mail::to($order->customer->email)->send(new InvoiceMail($invoice));

        return $invoice;
    }
}
