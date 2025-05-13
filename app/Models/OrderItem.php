<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'seller_id',
        'quantity', 'price'
    ];
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Relation avec le produit
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // Relation avec le vendeur
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }

}