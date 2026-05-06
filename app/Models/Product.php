<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $table = 'products';

    protected $fillable = [
        'user_id',
        'nama_produk',
        'sku',
        'category',
        'price',
        'units_sold',
        'revenue',
        'trend',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function platforms()
    {
        return $this->belongsToMany(
            Platform::class,
            'product_platform',
            'product_id',
            'platform_id'
        );
    }

    public function salesData()
    {
        return $this->hasMany(SaleData::class, 'product_id');
    }
}