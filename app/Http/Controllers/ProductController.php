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
        return[
            new Middleware('auth:sanctum',except:['index','show'])
        ];
    }
    public function index()
    {
        $products=Product::get();
        if($products->count()>0)
        {
            return ProductResource::collection($products);
        }
        else
        {
            return response()->json(['message' => 'No record avaiable'],200);
        }
    }
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(),[
            'name'=>'required|string|max:255',
            'description'=>'required',
            'price'=>'required|integer',
            //'photo'=>'required|file|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'stock' => 'required|integer|min:0',
        ]);
        if($validator->fails())
        { 
            return response()->json([ 
                'message'=> 'All fields are mandetory', 
                'error'=> $validator->messages(), ],422);
        }
       
        $product = Product::create([
            'name'=>$request->name,
            'description'=>$request->description,
            'price'=>$request->price,
            //'photo'=>$request->photo-->store('public/products')
            'user_id'=>\Illuminate\Support\Facades\Auth::user()->id,
            'stock' => $request->stock,
        ]);
        return response()->json([
            'message'=>'Product created successfully',
            'data'=>new ProductResource($product) 
        ],200);
    }
    public function show(Product $product)
    {
        return new ProductResource($product) ;
    }
    public function update(Request $request,Product $product)
    {
        Gate::authorize('modify', $product);
        $validator = Validator::make($request->all(),[
            'name'=>'required|string|max:255',
            'description'=>'required',
            'price'=>'required|integer',
            'stock' => 'required|integer|min:0',
        ]);
        if($validator->fails())
        { 
            return response()->json([ 
                'message'=> 'All fields are mandetory', 
                'error'=> $validator->messages(), ],422);
        }
       
        $product -> update([
            'name'=>$request->name,
            'description'=>$request->description,
            'price'=>$request->price,
            'stock' => $request->stock,
        ]);
        return response()->json([
            'message'=>'Product updated successfully',
            'data'=>new ProductResource($product) 
        ],200);
    }
    public function destroy(Product $product)
    {
        Gate::authorize('modify', $product);
        $product->delete();
        return response()->json([
            'message'=>'Product deleted successfully', 
        ],200);
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
