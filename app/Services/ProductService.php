<?php

namespace App\Services;

use App\Models\Product;

class ProductService
{
    public function getAll()
    {
        return Product::with(['platforms', 'salesData'])->get();
    }

    public function create(array $data)
    {
        $product = Product::create($data);

        if(isset($data['platform_ids'])) {
            $product->platforms()->sync($data['platform_ids']);
        }

        return $product;
    }

    public function update(Product $product, array $data)
    {
        $product->update($data);

        if(isset($data['platform_ids'])) {
            $product->platforms()->sync($data['platform_ids']);
        }

        return $product;
    }

    public function delete(Product $product)
    {
        return $product->delete();
    }

    public function findById($id)
    {
        return Product::with(['platforms', 'salesData'])->findOrFail($id);
    }
}