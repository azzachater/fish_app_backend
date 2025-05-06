<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    protected $fillable = [
        'buyer_id', 'status', 'payment_method',
        'shipping_address', 'phone', 'total'
    ];



    // Relation avec l'acheteur
    public function buyer()
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    // Relation avec les produits commandés
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    

}
