<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $guarded = [];

    public function platforms()
    {
        return $this->belongsToMany(Platform::class, 'product_platform');
    }

    public function salesData()
    {
        return $this->hasMany(SaleData::class);
    }
}