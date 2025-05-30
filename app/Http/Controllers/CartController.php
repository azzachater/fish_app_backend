<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CartController extends Controller
{
    use AuthorizesRequests;

    public function index()
    {
        // Récupérer uniquement le panier de l'utilisateur connecté
        $cartItems = Cart::where('user_id', Auth::id())
            ->with('product')
            ->get();

        $total = $cartItems->sum(function ($cart) {
            return $cart->product->price * $cart->quantity;
        });

        return response()->json([
            'cart' => $cartItems,
            'total_price' => $total,
        ]);
    }

    public function addToCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'user_id' => 'required|exists:users,id' // Ajout de la validation user_id
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid data', 'errors' => $validator->errors()], 422);
        }

        // Vérifier que l'user_id correspond à l'utilisateur connecté
        if ($request->user_id != Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $product = Product::findOrFail($request->product_id);

        if ($product->stock < $request->quantity) {
            return response()->json(['message' => 'Stock insuffisant'], 400);
        }

        $cart = Cart::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->first();

        if ($cart) {
            $cart->update(['quantity' => $cart->quantity + $request->quantity]);
        } else {
            $cart = Cart::create([
                'user_id' => Auth::id(), // Toujours utiliser l'ID de l'utilisateur connecté
                'product_id' => $product->id,
                'quantity' => $request->quantity,
            ]);
        }

        return response()->json(['message' => 'Produit ajouté au panier', 'data' => $cart]);
    }

    public function update(Request $request, Cart $cart)
    {
        // Vérification renforcée de l'appartenance du panier
        if ($cart->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid quantity', 'errors' => $validator->errors()], 422);
        }

        $product = Product::findOrFail($cart->product_id);

        if ($request->quantity > $product->stock) {
            return response()->json(['message' => 'Stock insuffisant'], 400);
        }

        $cart->update(['quantity' => $request->quantity]);

        return response()->json(['message' => 'Quantité mise à jour', 'data' => $cart]);
    }

    public function removeFromCart(Cart $cart)
    {
        if ($cart->user_id !== Auth::id()) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $cart->delete();

        return response()->json(['message' => 'Produit retiré du panier']);
    }
}
