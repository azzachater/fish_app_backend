<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Post;
use App\Models\Product;
use App\Models\Cart;
use App\Models\Event;
use App\Models\Tip;
use App\Models\Spot;

class AdminController extends Controller
{
    public function getUsers()
    {
        return response()->json(User::all());
    }

    public function deleteUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'Utilisateur supprimé']);
    }

    public function getPosts()
    {
        return response()->json(Post::all());
    }

    public function deletePost($id)
    {
        $post = Post::findOrFail($id);
        $post->delete();
        return response()->json(['message' => 'Post supprimé']);
    }

    public function getProducts()
    {
        return response()->json(Product::all());
    }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();
        return response()->json(['message' => 'Produit supprimé']);
    }

    public function getCarts()
    {
        return response()->json(Cart::all());
    }

    public function deleteCart($id)
    {
        $cart = Cart::findOrFail($id);
        $cart->delete();
        return response()->json(['message' => 'Panier supprimé']);
    }

    public function getEvents()
    {
        return response()->json(Event::all());
    }

    public function deleteEvent($id)
    {
        $event = Event::findOrFail($id);
        $event->delete();
        return response()->json(['message' => 'Événement supprimé']);
    }

    public function getTips()
    {
        return response()->json(Tip::all());
    }

    public function deleteTip($id)
    {
        $tip = Tip::findOrFail($id);
        $tip->delete();
        return response()->json(['message' => 'Conseil supprimé']);
    }

    public function getSpots()
    {
        return response()->json(Spot::all());
    }

    public function deleteSpot($id)
    {
        $spot = Spot::findOrFail($id);
        $spot->delete();
        return response()->json(['message' => 'Spot supprimé']);
    }
}
