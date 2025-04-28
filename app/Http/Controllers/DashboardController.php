<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Post;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Fetch global statistics
        $usersCount = User::count();
        $postsCount = Post::count();
        $productsCount = Product::count();
        $stockCount = Product::sum('stock');  // Assuming the 'stock' field exists in the 'products' table

        // Fetch monthly statistics
        $currentMonth = Carbon::now()->month;
        $monthlyUsers = User::whereMonth('created_at', $currentMonth)->count();
        $monthlyPosts = Post::whereMonth('created_at', $currentMonth)->count();
        $monthlyProducts = Product::whereMonth('created_at', $currentMonth)->count();

        // Fetch user statistics over the last 12 months for the chart
        $usersPerMonth = User::selectRaw('count(*) as count, MONTH(created_at) as month')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->pluck('count', 'month')
            ->toArray();
        $postsPerMonth = Post::selectRaw('count(*) as count, MONTH(created_at) as month')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->pluck('count', 'month')
            ->toArray();
        $productsPerMonth = Product::selectRaw('count(*) as count, MONTH(created_at) as month')
            ->groupBy('month')
            ->orderBy('month', 'asc')
            ->get()
            ->pluck('count', 'month')
            ->toArray();

        // Ensure each month from 1 to 12 exists in the array (fill missing months with 0)
        $months = range(1, 12);
        foreach ($months as $month) {
            if (!isset($usersPerMonth[$month])) {
                $usersPerMonth[$month] = 0;
            }
            if (!isset($postsPerMonth[$month])) {
                $postsPerMonth[$month] = 0;
            }
            if (!isset($productsPerMonth[$month])) {
                $productsPerMonth[$month] = 0;
            }
        }

        // Prepare the response data
        $data = [
            'totals' => [
                'users' => $usersCount,
                'posts' => $postsCount,
                'products' => $productsCount,
                'stock' => $stockCount,
            ],
            'monthly' => [
                'users' => $usersPerMonth,
                'posts' => $postsPerMonth,
                'products' => $productsPerMonth,
            ],
        ];

        return response()->json($data);
    }

}
