<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SaleData extends Model
{
    protected $table = 'sales_data';

    protected $fillable = [
        'product_id',
        'platform_id',
        'tanggal',
        'revenue',
        'units_sold',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function platform()
    {
        return $this->belongsTo(Platform::class, 'platform_id');
    }
}