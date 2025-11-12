<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DomainOrderDocument extends Model
{
    protected $fillable = ['order_id', 'doc_type', 'file_path'];
}
