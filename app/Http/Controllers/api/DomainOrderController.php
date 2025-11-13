<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\InvoiceMail;
use App\Models\Customer;
use App\Models\DomainOrder;
use App\Models\DomainTld;
use App\Models\DomainTldPrice;
use App\Models\Invoice;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Mail;

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DomainOrder;
use App\Models\DomainTld;
use App\Models\DomainTldPrice;
use App\Models\Customer;
use App\Models\Invoice;
use App\Mail\InvoiceMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Support\ApiResponse;

class DomainOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Detect who is calling
        $isAdmin = auth('api')->check();
        $isCustomer = auth('customer_api')->check();

        if (!$isAdmin && !$isCustomer) {
            return ApiResponse::error('Unauthorized access. Token required.', 401);
        }

        // Validate optional status filter
        if ($request->has('status') && !in_array($request->status, [
            'pending', 'processing', 'active', 'rejected',
            'failed', 'expired', 'cancelled', 'refunded'
        ])) {
            return ApiResponse::error('Invalid status filter', 422);
        }

        // Build base query
        $query = DomainOrder::with(['documents', 'customer'])->latest();

        if ($isCustomer) {
            $user = auth('customer_api')->user();
            $query->where('customer_id', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Pagination
        $limit = $request->query('limit', 10);
        $orders = $limit === 'all' ? $query->get() : $query->paginate((int) $limit);

        return ApiResponse::success('Domain orders retrieved successfully', $orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $isAdmin = auth('api')->check();
        $isCustomer = auth('customer_api')->check();

        if (!$isAdmin && !$isCustomer) {
            return ApiResponse::error('Unauthorized access.', 401);
        }

        // Validation rules differ slightly
        $rules = [
            'domain_name'   => 'required|string',
            'years'         => 'required|integer|min:1|max:10',
            'customer_type' => 'required|in:individual,company',
            'nid_file'      => 'required|file',
            'trade_license' => 'required|file',
            'auth_letter'   => 'required_if:customer_type,individual|file',
            'other_doc'     => 'nullable|file',
        ];

        if ($isAdmin) {
            $rules['customer_id'] = 'required|exists:customers,id';
        }

        $validator = Validator::make($request->all(), $rules);
        if ($validator->fails()) {
            return ApiResponse::error('Validation error', $validator->errors(), 422);
        }

        // Determine which customer to assign
        $customerId = $isAdmin
            ? $request->customer_id
            : auth('customer_api')->id();

        // Extract TLD
        $domainParts = explode('.', $request->domain_name, 2);
        $tld = isset($domainParts[1]) ? '.' . strtolower(trim($domainParts[1])) : null;

        if (!$tld) {
            return ApiResponse::error('Invalid domain format', 422);
        }

        // Find active TLD and price
        $tldRecord = DomainTld::where('name', $tld)->where('status', 'active')->first();
        if (!$tldRecord) {
            return ApiResponse::error('TLD not supported', 422);
        }

        $priceRecord = DomainTldPrice::where('tld_id', $tldRecord->id)
            ->where('years', $request->years)
            ->first();

        if (!$priceRecord) {
            return ApiResponse::error('Pricing not found for selected year', 422);
        }

        $finalPrice = $priceRecord->register_price;

        // Create order
        $order = DomainOrder::create([
            'customer_id'   => $customerId,
            'domain_name'   => $request->domain_name,
            'years'         => $request->years,
            'amount'        => $finalPrice,
            'customer_type' => $request->customer_type,
            'status'        => 'pending',
        ]);

        // Upload files
        $docs = [
            'nid' => $request->file('nid_file'),
            'trade_license' => $request->file('trade_license'),
            'authorization_letter' => $request->file('auth_letter'),
            'other' => $request->file('other_doc'),
        ];

        foreach ($docs as $type => $file) {
            if ($file) {
                $path = $file->store("domain_docs/$order->id", 'public');
                $order->documents()->create([
                    'doc_type'  => $type,
                    'file_path' => $path
                ]);
            }
        }

        // Generate invoice
        $invoiceNo = 'INV-' . date('Ymd') . '-' . str_pad($order->id, 5, '0', STR_PAD_LEFT);
        $invoice = Invoice::create([
            'customer_id' => $order->customer_id,
            'order_id'    => $order->id,
            'invoice_no'  => $invoiceNo,
            'amount'      => $order->amount,
            'status'      => 'unpaid',
        ]);

        // Email invoice to customer
        $customer = Customer::find($order->customer_id);
        Mail::to($customer->email)->send(new InvoiceMail($invoice));

        return ApiResponse::success('Domain order placed successfully', $order->load(['documents']), 201);
    }

    /**
     * Display a specific order.
     */
    public function show($id)
    {
        $order = DomainOrder::with(['documents', 'customer'])->find($id);

        if (!$order) {
            return ApiResponse::error('Order not found', null, 404);
        }

        $isAdmin = auth('api')->check();
        $isCustomer = auth('customer_api')->check();

        if ($isCustomer && $order->customer_id !== auth('customer_api')->id()) {
            return ApiResponse::error('Unauthorized to view this order', 403);
        }

        return ApiResponse::success('Order fetched successfully', $order);
    }

    public function destroy($id)
    {
        $order = DomainOrder::find($id);

        if (!$order) {
            return ApiResponse::error('Order not found', null, 404);
        }

        $isAdmin = auth('api')->check();
        $isCustomer = auth('customer_api')->check();

        if ($isCustomer && $order->customer_id !== auth('customer_api')->id()) {
            return ApiResponse::error('Unauthorized to delete this order', 403);
        }

        $order->delete();
        return ApiResponse::success('Order deleted successfully');
    }
}

