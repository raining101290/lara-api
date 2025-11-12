<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainOrder extends Model
{
    protected $fillable = [
        'customer_id', 'domain_name', 'years', 'amount', 'customer_type', 'status'
    ];

    // public function contact()
    // {
    //     return $this->hasOne(DomainOrderContact::class, 'order_id');
    // }

    public function documents()
    {
        return $this->hasMany(DomainOrderDocument::class, 'order_id');
    }
}
