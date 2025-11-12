<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CustomerInfo extends Model
{
    protected $fillable = [
        'customer_id',
        'mobile',
        'company',
        'nid',
        'address',
        'city',
        'state',
        'postal_code',
        'country',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }
}
