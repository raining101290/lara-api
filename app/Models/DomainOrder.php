<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainOrder extends Model
{
    protected $fillable = [
        'customer_id', 'domain_name', 'years', 'amount', 'customer_type', 'status'
    ];
    
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function documents()
    {
        return $this->hasMany(DomainOrderDocument::class, 'order_id');
    }
    public function invoice()
    {
        return $this->hasOne(Invoice::class, 'order_id');
    }
}
