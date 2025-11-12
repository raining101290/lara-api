<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DomainOrder;
use App\Models\DomainTld;
use App\Models\DomainTldPrice;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DomainOrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = auth()->user(); // Get logged-in user

        if (!$user) {
            return ApiResponse::error('Unauthorized access. Token required.', 401);
        }

        if ($request->has('status') && !in_array($request->status, [
            'pending', 'processing', 'active', 
            'rejected', 'failed', 'expired', 
            'cancelled', 'refunded'
        ])) {
            return ApiResponse::error('Invalid status filter', 422);
        }

        $query = DomainOrder::with(['documents'])
            ->where('customer_id', $user->id) // ✅ Return only user's orders
            ->latest();

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $orders = $query->paginate(10);

        return ApiResponse::success('Domain orders retrieved successfully', $orders);
    }
    // public function index(Request $request)
    // {
    //     if ($request->has('status') && !in_array($request->status, [
    //     'pending', 'processing', 'active', 
    //     'rejected', 'failed', 'expired', 
    //     'cancelled', 'refunded'
    //     ])) {
    //         return ApiResponse::error('Invalid status filter', 422);
    //     }
    //     $query = DomainOrder::with(['documents'])->latest();
    //     if ($request->has('status')) {
    //         $query->where('status', $request->status);
    //     }

    //     $orders = $query->paginate(10);

    //     return ApiResponse::success('Domain orders retrieved successfully', $orders);
    // }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->headers->set('Accept', 'application/json');

        $validator = Validator::make($request->all(), [
            'customer_id'     => 'required|exists:customers,id',
            'domain_name'     => 'required|string',
            'years'           => 'required|integer|min:1|max:10',
            // 'amount'          => 'required|numeric',
            'customer_type'   => 'required|in:individual,company',
            'nid_file'        => 'required|file',
            'trade_license'   => 'required|file',
            'auth_letter'     => 'required_if:customer_type,individual|file',
            'other_doc'       => 'nullable|file',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors'  => $validator->errors()
            ], 422);
        }

        // ✅ Extract TLD from domain
        $domainParts = explode('.', $request->domain_name, 2);
        $tld = '.' . strtolower(trim($domainParts[1]));

        if (!$tld) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid domain format'
            ], 422);
        }

        // ✅ Find TLD in database
        $tldRecord = DomainTld::where('name', $tld)->where('status', 'active')->first();
        if (!$tldRecord) {
            return response()->json([
                'success' => false,
                'message' => 'TLD not supported'
            ], 422);
        }

        // ✅ Get price for selected years
        $priceRecord = DomainTldPrice::where('tld_id', $tldRecord->id)
                        ->where('years', $request->years)
                        ->first();

        if (!$priceRecord) {
            return response()->json([
                'success' => false,
                'message' => 'Pricing not found for selected year'
            ], 422);
        }

        // ✅ Now assign secure price from DB
        $finalPrice = $priceRecord->register_price;

        // ✅ Create Order with DB price (not frontend price)
        $order = DomainOrder::create([
            'customer_id'   => $request->customer_id,
            'domain_name'   => $request->domain_name,
            'years'         => $request->years,
            'amount'        => $finalPrice, // ← important
            'customer_type' => $request->customer_type,
            'status'        => 'pending',
        ]);
        // Store Files Only
        $docs = [
            'nid'                => $request->file('nid_file'),
            'trade_license'      => $request->file('trade_license'),
            'authorization_letter' => $request->file('auth_letter'),
            'other'              => $request->file('other_doc'),
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

        return ApiResponse::success('Domain order placed successfully', $order->load(['documents']), 201);
    }


    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $order = DomainOrder::with(['documents'])->find($id);

        if (!$order) {
            return response()->json(['success' => false, 'message' => 'Order not found'], 404);
        }
        return ApiResponse::success('order fetched', $order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
