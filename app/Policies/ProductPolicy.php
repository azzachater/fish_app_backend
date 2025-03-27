<?php

namespace App\Policies;

use App\Models\Product;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class ProductPolicy
{
    public function modify(User $user, Product $product): Response
    {
        return $user->id === $product->user_id
            ?Response::allow()
            :Response::deny('You do not own this product');
    }
}
