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
    /*public function store(Request $request)
    {
        // Validation des données d'entrée
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'unit' => 'required|string',
            'image' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'stock' => 'required|integer|min:0',
            'category' => 'required|string',
        ]);

        // Vérification des erreurs de validation
        if ($validator->fails()) {
            return response()->json([
                'message' => 'All fields are mandatory',
                'errors' => $validator->messages(),
            ], 422);
        }

        // Gestion de l'image
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('public/products');
            $imageName = basename($imagePath); // Récupérer juste le nom de l'image
        } else {
            return response()->json([
                'message' => 'Image upload failed',
            ], 500);
        }
        $fields['user_id'] = Auth::id();

        // Création du produit
        $product = Product::create([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'unit' => $request->unit,
            'image' => $imageName,
            'stock' => $request->stock,
            'category' => $request->category,
            'user_id' => Auth::id(),
        ]);

        return response()->json([
            'message' => 'Product created successfully',
            'data' => new ProductResource($product),
        ], 201);
    }*/
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
    /*public function update(Request $request, Product $product)
    {
        Gate::authorize('modify', $product);
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'unit' => 'required|string',
            'image' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'stock' => 'required|integer|min:0',
            'category' => 'required|string',
        ]);
        if ($validator->fails()) {
            return response()->json([
                'message' => 'All fields are mandetory',
                'error' => $validator->messages(),
            ], 422);
        }

        $product->update([
            'name' => $request->name,
            'description' => $request->description,
            'price' => $request->price,
            'stock' => $request->stock,
        ]);
        return response()->json([
            'message' => 'Product updated successfully',
            'data' => new ProductResource($product)
        ], 200);
    }*/
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric',
            'unit' => 'required|string',
            'image' => 'required|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'stock' => 'required|integer|min:0',
            'category' => 'required|string',
        ]);

        // Récupérer le journal de l'utilisateur connecté
        $product = Product::where('user_id', Auth::id())->findOrFail($id);

        // Mettre à jour les données
        $product->update($request->all());

        return response()->json($product);
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

        $product->decrement('stock', $request->quantity);

        return response()->json(['message' => 'Produit ajouté au panier', 'data' => $cart]);
    }
}
