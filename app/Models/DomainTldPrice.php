<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainTldPrice extends Model
{
    protected $fillable = [
        'tld_id',
        'years',
        'register_price',
        'renewal_price'
    ];

    public function tlds()
    {
        return $this->belongsTo(DomainTld::class, 'tld_id');
    }
}
