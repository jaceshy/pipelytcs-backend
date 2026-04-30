<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    protected $table = 'platforms';

    protected $guarded = [];

    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_platform');
    }

    public function salesData()
    {
        return $this->hasMany(SaleData::class);
    }
}