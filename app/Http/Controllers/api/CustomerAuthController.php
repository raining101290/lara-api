<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CustomerInfo;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use App\Models\Customer;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;
use PHPOpenSourceSaver\JWTAuth\Exceptions\JWTException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Database\QueryException;
use Illuminate\Validation\ValidationException;

class CustomerAuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email'    => 'required|email',
            'password' => 'required'
        ]);

        $credentials = $request->only('email', 'password');

        if (!$token = auth('customer_api')->attempt($credentials)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials'
            ], 401);
        }

        // Load the authenticated user with customerInfo relation
        $user = auth('customer_api')->user()->load('info');

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'token'   => $token,
            'user'    => $user
        ]);
    }

    public function register(Request $request)
    {
        try {
            // Validate request
            $validator = Validator::make($request->all(), [
                'full_name'        => 'required|string|max:255',
                'email'            => 'required|email|unique:customers,email',
                'password'         => 'required|min:6',
                'password_confirmation' => 'required|same:password',

                'mobile'       => 'nullable|string',
                'company'      => 'nullable|string',
                'nid'          => 'nullable|string',
                'address'      => 'nullable|string',
                'city'         => 'nullable|string',
                'state'        => 'nullable|string',
                'postal_code'  => 'nullable|string',
                'country'      => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'success' => false,
                    'errors'  => $validator->errors()
                ], 422);
            }

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
        // $validator = Validator::make($request->all(), [
        //     'full_name' => 'required|string|max:255',
        //     'email'     => 'required|email|unique:customers,email',
        //     'password'  => 'required|string|min:6|confirmed',
        // ]);

        // if ($validator->fails()) {
        //     return response()->json([
        //         'success' => false,
        //         'errors'  => $validator->errors()
        //     ], 422);
        // }

        // $customer = Customer::create([
        //     'full_name' => $request->full_name,
        //     'email'     => $request->email,
        //     'password'  => Hash::make($request->password),
        // ]);

        // return response()->json([
        //     'success' => true,
        //     'message' => 'Customer registered successfully',
        //     'user'    => $customer
        // ], 201);
    }

    public function logout()
    {
        auth('customer_api')->logout();
        return response()->json(['message' => 'Successfully logged out']);
    }

    public function refresh()
    {
        return response()->json([
            'token' => auth('customer_api')->refresh()
        ]);
    }

    public function profile()
    {
        return response()->json(auth('customer_api')->user());
    }
    
}
