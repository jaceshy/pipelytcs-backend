<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Platform extends Model
{
    protected $table = 'platforms';

    protected $fillable = [
        'nama',
    ];

    public function products()
    {
        return $this->belongsToMany(
            Product::class,
            'product_platform',
            'platform_id',
            'product_id'
        );
    }

    public function salesData()
    {
        return $this->hasMany(SaleData::class, 'platform_id');
    }
}