<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MasterMenu extends Model
{
    protected $fillable = [
        'category',
        'nama',
        'is_active',
        'order_index',
    ];
}