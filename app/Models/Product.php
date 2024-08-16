<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = ["user_id", "setting_id", "product_id", "title", "value", "checked"];

    protected $hidden = [
        'created_at', 'updated_at'
    ];
}
