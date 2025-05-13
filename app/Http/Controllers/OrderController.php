<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        // Récupère les commandes de l'utilisateur avec leurs items et produits associés
        $orders = Order::with(['items.product', 'buyer'])
            ->where('buyer_id', $user->id)
            ->latest()
            ->get();

        return response()->json($orders);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|max:20',
            'address' => 'required|string|max:255',
            'payment_method' => 'required|in:cash_on_delivery,online'
        ]);

        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        try {
            return DB::transaction(function () use ($request, $user) {
                $cartItems = Cart::with('product.user')
                    ->where('user_id', $user->id)
                    ->get();

                if ($cartItems->isEmpty()) {
                    return response()->json(['message' => 'Cart is empty'], 400);
                }

                $order = Order::create([
                    'buyer_id' => $user->id,
                    'status' => 'pending',
                    'payment_method' => $request->payment_method,
                    'shipping_address' => $request->address,
                    'phone' => $request->phone,
                    'total' => $cartItems->sum(fn($item) => $item->product->price * $item->quantity)
                ]);

                foreach ($cartItems as $item) {
                    // Vérification du stock avant création
                    if ($item->product->stock < $item->quantity) {
                        throw new \Exception("Insufficient stock for product: {$item->product->name}");
                    }

                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item->product_id,
                        'seller_id' => $item->product->user_id,
                        'quantity' => $item->quantity,
                        'price' => $item->product->price
                    ]);

                    // Mise à jour du stock
                    $item->product->decrement('stock', $item->quantity);
                }

                Cart::where('user_id', $user->id)->delete();
                return response()->json([
                    'message' => 'Order created successfully',
                    'order' => $order->load('items.product')
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Order creation failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $order = Order::with(['items.product', 'buyer'])
            ->where('buyer_id', $user->id)
            ->find($id);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        return response()->json($order);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
