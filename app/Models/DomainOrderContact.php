<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainOrderContact extends Model
{
    protected $fillable = [
        'order_id', 'name', 'organization', 'email', 'phone', 'nid', 'city', 'zip_code', 'state', 'address'
    ];
}
