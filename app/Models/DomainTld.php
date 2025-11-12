<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainTld extends Model
{
    protected $fillable = ['name', 'base_price', 'status'];

    public function prices()
    {
        return $this->hasMany(DomainTldPrice::class, 'tld_id');
    }
}