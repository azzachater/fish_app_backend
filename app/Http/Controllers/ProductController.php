<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Http\Resources\ProductResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;
use App\Models\Order;


class ProductController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show'])
        ];
    }
    public function index()
    {
        $products = Product::with('user')->get();

        return response()->json([
            'success' => true,
            'data' => $products,
            'message' => $products->isEmpty() ? 'No products found' : 'Products retrieved successfully'
        ]);
    }
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'unit' => 'required|string|max:20',
            'stock' => 'required|integer|min:0',
            'category' => 'required|string|max:255',
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);
        //stocke l'image dans le dossier storage/app/public/products
        $imagePath = $request->file('image')->store('products', 'public');

        $product = Product::create([
            'name' => $validated['name'],
            'description' => $validated['description'],
            'price' => $validated['price'],
            'unit' => $validated['unit'],
            'stock' => $validated['stock'],
            'category' => $validated['category'],
            'image' => $imagePath,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => $product
        ], 201);
    }

    public function show(Product $product)
    {
        return new ProductResource($product);
    }
    // Dans ProductController.php
    public function update(Request $request, Product $product)
    {
        // Valider les données
        $validated = $request->validate([
            'name' => 'required|max:255',
            'description' => 'nullable',
            'price' => 'required|numeric',
            'unit' => 'required',
            'stock' => 'required|integer',
            'category' => 'required',
            'image' => 'nullable|image|max:2048',
        ]);

        // Mettre à jour les champs
        $product->update($validated);

        // Gérer l'image si elle est fournie
        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products');
            $product->image = $path;
            $product->save();
        }

        return response()->json([
            'message' => 'Produit mis à jour avec succès',
            'data' => $product
        ]);
    }
    public function destroy(Product $product)
    {
        Gate::authorize('modify', $product);
        $product->delete();
        return response()->json([
            'message' => 'Product deleted successfully',
        ], 200);
    }
    public function addToCartFromProduct(Request $request, $productId)
    {
        $validator = Validator::make($request->all(), [
            'quantity' => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Invalid data', 'errors' => $validator->errors()], 422);
        }

        $product = Product::findOrFail($productId);

        if ($product->stock < $request->quantity) {
            return response()->json(['message' => 'Stock insuffisant'], 400);
        }

        $user = Auth::user();
        $cart = Cart::where('user_id', $user->id)->where('product_id', $productId)->first();

        if ($cart) {
            $cart->update(['quantity' => $cart->quantity + $request->quantity]);
        } else {
            $cart = Cart::create([
                'user_id' => $user->id,
                'product_id' => $productId,
                'quantity' => $request->quantity,
            ]);
        }
        return response()->json(['message' => 'Produit ajouté au panier', 'data' => $cart]);
    }
    public function placeOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string',
            'address' => 'required|string',
            'payment_method' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['message' => 'Validation failed', 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $cartItems = Cart::with('product')->where('user_id', $user->id)->get();

        if ($cartItems->isEmpty()) {
            return response()->json(['message' => 'Votre panier est vide'], 400);
        }

        DB::beginTransaction();
        try {
            foreach ($cartItems as $item) {
                $product = $item->product;

                // Vérification finale du stock avant commande
                if ($product->stock < $item->quantity) {
                    DB::rollBack();
                    return response()->json([
                        'message' => "Stock insuffisant pour le produit {$product->name}. Il ne reste que {$product->stock} unité(s)",
                        'product_id' => $product->id
                    ], 400);
                }

                // Création de la commande
                Order::create([
                    'user_id' => $user->id,
                    'product_id' => $product->id,
                    'quantity' => $item->quantity,
                    'price' => $product->price * $item->quantity,
                    'phone' => $request->phone,
                    'address' => $request->address,
                    'payment_method' => $request->payment_method,
                    'status' => 'pending'
                ]);

                // Décrémentation du stock SEULEMENT ICI
                $product->decrement('stock', $item->quantity);
            }

            // Vider le panier après commande
            Cart::where('user_id', $user->id)->delete();

            DB::commit();
            return response()->json(['message' => 'Commande passée avec succès']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Erreur lors de la commande',
                'error' => $e->getMessage()
            ], 500);
        }
    }
    public function checkStock($productId, $quantity)
    {
        try {
            $product = Product::findOrFail($productId);

            return response()->json([
                'available' => $product->stock >= $quantity,
                'current_stock' => $product->stock,
                'product_id' => $product->id // Pour le débogage
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'available' => false,
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
