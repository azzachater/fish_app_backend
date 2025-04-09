<?php

namespace App\Http\Controllers;

use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Request;
use App\Models\Spot;
use Illuminate\Support\Facades\Auth;

class SpotController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['index', 'show'])
        ];
    }
    public function index()
    {
        $spots = Spot::all();
        return response()->json($spots);
    }

    public function store(Request $request)
    {
        $fields = $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'description' => 'required|string|max:255',
            'fish_species' => 'required|string|max:255',
            'recommendedTechniques' => 'required|string|max:255',
            'depth' => 'required|string|max:255',

        ]);
        $spot = $request->user()->spots()->create($fields);
        return response()->json($spot, 201);
    }

    public function show($id)
    {
        $spot = Spot::findOrFail($id);

        return response()->json($spot);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'latitude' => 'required|numeric',
            'longitude' => 'required|numeric',
            'description' => 'required|string|max:255',
            'fish_species' => 'required|string|max:255',
            'recommendedTechniques' => 'required|string|max:255',
            'depth' => 'required|string|max:255',

        ]);
        $spot = Spot::where('user_id', Auth::id())->findOrFail($id);
        $spot->update($request->all());
        return response()->json($spot);
    }
    public function destroy($id)
    {
        $spot = Spot::where('user_id', Auth::id())->findOrFail($id);
        $spot->delete();

        return response()->json(null, 204);
    }
}
