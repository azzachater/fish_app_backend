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

    // 🛒 Afficher les articles du panier
    public function index()
    {
        $cartItems = Cart::where('user_id', Auth::id())->with('product')->get();

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
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid data', 'errors' => $validator->errors()], 422);
        }

        $product = Product::findOrFail($request->product_id);

        // On vérifie le stock mais on ne décrémente pas
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
                'user_id' => Auth::id(),
                'product_id' => $product->id,
                'quantity' => $request->quantity,
            ]);
        }

        // SUPPRIMER la ligne qui décrémente le stock
        return response()->json(['message' => 'Produit ajouté au panier', 'data' => $cart]);
    }

    // 🔄 Mettre à jour la quantité d'un produit dans le panier
    public function update(Request $request, Cart $cart)
    {
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

        // On vérifie seulement le stock, pas de modification
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

        // SUPPRIMER la réincrémentation du stock
        $cart->delete();

        return response()->json(['message' => 'Produit retiré du panier']);
    }
}