<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Models\DomainTld;
use App\Support\ApiResponse;
use Illuminate\Http\Request;

class DomainTldController extends Controller
{
    /**
     * GET all TLDs with pricing
     */
    public function index()
    {
        $tlds = DomainTld::with('prices')->get();
        return ApiResponse::success('TLD list fetched', $tlds);
    }

    /**
     * CREATE TLD with pricing (1–10 years)
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:domain_tlds,name',
            'status' => 'nullable|in:active,coming_soon,disabled',
            'prices' => 'required|array|min:1',
            'prices.*.years' => 'required|integer|min:1|max:10',
            'prices.*.register_price' => 'required|numeric|min:0',
            'prices.*.renewal_price'  => 'required|numeric|min:0',
        ]);

        $tld = DomainTld::create([
            'name' => $request->name,
            'status' => $request->status ?? 'active'
        ]);

        foreach ($request->prices as $price) {
            $tld->prices()->create($price);
        }

        return ApiResponse::success('TLD created', $tld->load('prices'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $tld = DomainTld::with('prices')->find($id);

        if (!$tld) {
            return ApiResponse::error('TLD not found', null, 404);
        }

        return ApiResponse::success('TLD fetched', $tld);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $tld = DomainTld::with('prices')->find($id);
        if (!$tld) {
            return ApiResponse::error('TLD not found', 404);
        }

        $request->validate([
            'name' => 'required|string|unique:domain_tlds,name,' . $tld->id,
            'status' => 'nullable|in:active,coming_soon,disabled',
            'prices' => 'nullable|array',
            'prices.*.years' => 'required|integer|min:1|max:10',
            'prices.*.register_price' => 'required|numeric|min:0',
            'prices.*.renewal_price'  => 'required|numeric|min:0',
        ]);

        // Update TLD main fields
        $tld->update([
            'name'   => $request->name,
            'status' => $request->status ?? $tld->status
        ]);

        // Update prices if provided
        if ($request->has('prices')) {
            foreach ($request->prices as $price) {
                $tld->prices()->updateOrCreate(
                    ['years' => $price['years']],
                    [
                        'register_price' => $price['register_price'],
                        'renewal_price'  => $price['renewal_price']
                    ]
                );
            }
        }

        return ApiResponse::success('TLD updated', $tld->load('prices'));
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $tld = DomainTld::find($id);
        if (!$tld) {
            return ApiResponse::error('TLD not found', null, 404);
        }

        $tld->delete();

        return ApiResponse::success('TLD deleted successfully');
    }
}
