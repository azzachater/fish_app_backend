<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;
    protected $table = 'products';

    protected $fillable = [
        'name',
        'description',
        'price',
        'unit',
        'stock',
        'category',
        'image',
        'user_id'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock' => 'integer',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function carts()
    {
        return $this->hasMany(Cart::class); // Un produit peut être dans plusieurs paniers
    }
    public function orders()
{
    return $this->hasManyThrough(Order::class, OrderItem::class);
}
}
