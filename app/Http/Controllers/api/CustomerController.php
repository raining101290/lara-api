<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerInfo;
use App\Models\DomainOrder;
use App\Models\Invoice;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Fetch all customers with their info
        $customers = Customer::with('info')->get();

        return response()->json([
            'success' => true,
            'data' => $customers
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            // Validate request
            $request->validate([
                'full_name'        => 'required|string|max:255',
                'email'            => 'required|email|unique:customers,email',
                'password'         => 'required|min:6',
                'confirm_password' => 'required|same:password',

                'mobile'       => 'nullable|string',
                'company'      => 'nullable|string',
                'nid'          => 'nullable|string',
                'address'      => 'nullable|string',
                'city'         => 'nullable|string',
                'state'        => 'nullable|string',
                'postal_code'  => 'nullable|string',
                'country'      => 'nullable|string',
            ]);

            $customer = Customer::create([
                'full_name'        => $request->full_name,
                'email'            => $request->email,
                'password'         => Hash::make($request->password),
            ]);

            CustomerInfo::create([
                'customer_id' => $customer->id,
                'mobile'      => $request->mobile,
                'company'     => $request->company,
                'nid'         => $request->nid,
                'address'     => $request->address,
                'city'        => $request->city,
                'state'       => $request->state,
                'postal_code' => $request->postal_code,
                'country'     => $request->country,
            ]);

            return ApiResponse::success('Customer created successfully', $customer->load('info'), 201);
        }

        // Validation errors (like duplicate email)
        catch (ValidationException $e) {
            return ApiResponse::error('Validation Error', $e->errors(), 422);
        }

        // Database errors (safety net)
        catch (QueryException $e) {
            return ApiResponse::error('Database Error', $e->getMessage(), 500);
        }

        // General fallback error
        catch (\Exception $e) {
            return ApiResponse::error('Something went wrong', $e->getMessage(), 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $customer = Customer::with('info')->find($id);
        if (!$customer) {
            return ApiResponse::error('Customer not found', null, 404);
        }
        return ApiResponse::success('Customer details fetched successfully', $customer);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        try {
            $customer = Customer::with('info')->find($id);

            if (!$customer) {
                return ApiResponse::error('Customer not found', null, 404);
            }

            // Validate request
            $request->validate([
                'full_name'        => 'sometimes|required|string|max:255',
                'email'            => 'sometimes|required|email|unique:customers,email,' . $customer->id,
                'password'         => 'sometimes|nullable|min:6',
                'confirm_password' => 'sometimes|nullable|same:password',

                'status'           => 'sometimes|integer|in:0,1',

                'mobile'       => 'sometimes|nullable|string',
                'company'      => 'sometimes|nullable|string',
                'nid'          => 'sometimes|nullable|string',
                'address'      => 'sometimes|nullable|string',
                'city'         => 'sometimes|nullable|string',
                'state'        => 'sometimes|nullable|string',
                'postal_code'  => 'sometimes|nullable|string',
                'country'      => 'sometimes|nullable|string',
            ]);

            // Update customer table
            $customer->update([
                'full_name'        => $request->full_name,
                'email'            => $request->email,
                'password'         => $request->password ? Hash::make($request->password) : $customer->password,
                'status'          => $request->status ?? $customer->status,
            ]);

            // Update or create customer info
            CustomerInfo::updateOrCreate(
                ['customer_id' => $customer->id],
                [
                    'mobile'       => $request->mobile,
                    'company'      => $request->company,
                    'nid'          => $request->nid,
                    'address'      => $request->address,
                    'city'         => $request->city,
                    'state'        => $request->state,
                    'postal_code'  => $request->postal_code,
                    'country'      => $request->country,
                ]
            );

            return ApiResponse::success('Customer updated successfully', $customer->load('info'));

        } catch (ValidationException $e) {
            return ApiResponse::error('Validation Error', $e->errors(), 422);
        } catch (QueryException $e) {
            return ApiResponse::error('Database Error', $e->getMessage(), 500);
        } catch (\Exception $e) {
            return ApiResponse::error('Something went wrong', $e->getMessage(), 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $customer = Customer::find($id);

            if (!$customer) {
                return ApiResponse::error('Customer not found', null, 404);
            }
            $customer->delete();
            return ApiResponse::success('Customer deleted successfully');

        } catch (QueryException $e) {
            return ApiResponse::error('Database Error', $e->getMessage(), 500);
        } catch (\Exception $e) {
            return ApiResponse::error('Something went wrong', $e->getMessage(), 500);
        }
    }

    /**
     * Update basic details of the specified customer.
     */
    public function updateBasic(Request $request, $id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return ApiResponse::error('Customer not found', null, 404);
        }

        $request->validate([
            'full_name' => 'required|string|max:255',
            'email'     => 'required|email|unique:customers,email,' . $customer->id,
        ]);

        $customer->update($request->only(['full_name', 'email']));

        return ApiResponse::success('Basic details updated', $customer);
    }


    /**
     * Update additional info of the specified customer.
     */
    public function updateInfo(Request $request, $id)
    {
        $customer = Customer::with('info')->find($id);
        if (!$customer) {
            return ApiResponse::error('Customer not found', null, 404);
        }

        $request->validate([
            'mobile'      => 'nullable|string',
            'company'     => 'nullable|string',
            'nid'         => 'nullable|string',
            'address'     => 'nullable|string',
            'city'        => 'nullable|string',
            'state'       => 'nullable|string',
            'postal_code' => 'nullable|string',
            'country'     => 'nullable|string',
        ]);

        $customer->info()->updateOrCreate(
            ['customer_id' => $customer->id],
            $request->only([
                'mobile',
                'company',
                'nid',
                'address',
                'city',
                'state',
                'postal_code',
                'country'
            ])
        );
        
        return ApiResponse::success('Customer information updated', $customer->fresh('info')->info);
    }

    /**
     * Update the password of the specified customer.
     */

    public function updatePassword(Request $request, $id)
    {
        $customer = Customer::find($id);
        if (!$customer) {
            return ApiResponse::error('Customer not found', null, 404);
        }

        $request->validate([
            'current_password' => 'required',
            'new_password'     => 'required|min:6',
            'confirm_password' => 'required|same:new_password',
        ]);

        // Check current password
        if (!Hash::check($request->current_password, $customer->password)) {
            return ApiResponse::error('Current password is incorrect', null, 400);
        }

        // Update password
        $customer->update([
            'password' => Hash::make($request->new_password)
        ]);

        return ApiResponse::success('Password updated successfully');
    }


    /**
     * Update the status of the specified customer.
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|integer|in:0,1'
        ]);

        $customer = Customer::find($id);

        if (!$customer) {
            return ApiResponse::error('Customer not found', null, 404);
        }

        $customer->update([
            'status' => $request->status
        ]);

        return ApiResponse::success('Status updated successfully', [
            'id'     => $customer->id,
            'status' => $customer->status
        ]);
    }

    
    public function dashboardSummary(Request $request)
    {
        try {
            $customer = auth('customer_api')->user(); 

            if (!$customer) {
                return ApiResponse::error('Unauthorized access. Token required.', 401);
            }

            // --- Domains ---
            $totalDomains = DomainOrder::where('customer_id', $customer->id)->count();
            $activeDomains = DomainOrder::where('customer_id', $customer->id)
                ->where('status', 'approved')
                ->count();

            // --- Invoices ---
            $totalInvoices = Invoice::where('customer_id', $customer->id)->count();

            $unpaidInvoices = Invoice::where('customer_id', $customer->id)
                ->where('status', 'unpaid')
                ->count();

            $amountDue = Invoice::where('customer_id', $customer->id)
                ->where('status', 'unpaid')
                ->sum('amount');

            $paidInvoices = Invoice::where('customer_id', $customer->id)
                ->where('status', 'paid')
                ->count();

            $paidThisYear = Invoice::where('customer_id', $customer->id)
                ->where('status', 'paid')
                ->whereYear('paid_at', now()->year)
                ->sum('amount');

            // --- Recent activity ---
            $recentInvoices = Invoice::where('customer_id', $customer->id)
                ->latest()
                ->take(5)
                ->get(['id', 'invoice_no', 'amount', 'status', 'created_at']);

            $recentDomains = DomainOrder::where('customer_id', $customer->id)
                ->latest()
                ->take(5)
                ->get(['id', 'domain_name', 'status', 'amount', 'created_at']);

            // --- Response ---
            return ApiResponse::success('Dashboard summary fetched successfully', [
                'user' => [
                    'id' => $customer->id,
                    'full_name' => $customer->full_name,
                    'email' => $customer->email,
                ],
                'domains' => [
                    'total' => $totalDomains,
                    'active' => $activeDomains,
                ],
                'invoices' => [
                    'total_count' => $totalInvoices,
                    'paid_count' => $paidInvoices,
                    'unpaid_count' => $unpaidInvoices,
                    'amount_due' => $amountDue,
                    'paid_this_year' => $paidThisYear,
                ],
                'recent' => [
                    'domains' => $recentDomains,
                    'invoices' => $recentInvoices,
                ]
            ]);

        } catch (\Exception $e) {
            return ApiResponse::error('Failed to fetch dashboard summary', $e->getMessage(), 500);
        }
    }
}
