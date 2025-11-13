<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'order_id',
        'invoice_no',
        'amount',
        'status',
        'paid_at'
    ];

    // ✅ Relationship to Domain Order
    public function order()
    {
        return $this->belongsTo(DomainOrder::class, 'order_id');
    }

    // ✅ Relationship to Customer (Required for your email)
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
