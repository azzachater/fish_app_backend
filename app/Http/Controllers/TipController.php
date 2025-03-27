<?php

namespace App\Http\Controllers;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Http\Request;
use App\models\Tip;
use Illuminate\Support\Facades\Auth;

class TipController extends Controller implements HasMiddleware
{
    public static function middleware(){
        return[
            new Middleware('auth:sanctum',except:['index', 'show'])
        ];
    }
    //afficher la liste de tips de l'utilisateur connecté 
    public function index(){
        
        $tips=Tip::where('user_id', Auth::id())->get();
        return response()->json($tips);

    }
    //afficher les detailles d'un conseil/astuce specifique
    public function show($id){
        $tips=Tip::where('user_id',Auth::id())->findOrFail($id);
        return response()->json($tips);
    }

    public function store(Request $request){
        $fields=$request->validate([
            'title'=>'required|string|max:255',
            'description'=>'required|string|max:255',
            'category'=>'required|string|max:255',
            'image'=>'required|max:255',
        ]);

        $tip =$request->user()->tips()->create($fields);
        return response()->json($tip, 201);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'title'=>'required|string',
            'description'=>'required|string',
            'category'=>'required|string',
            'image'=>'required',
        ]);

        //njibou conseil!:astuce mtaa connected user
        $tip=Tip::where('user_id', Auth::id())->findOrFail($id);

        //nbadlou juste les donnees hedhom bech n'empechiw les autre donnees d'etre modifier accidentalement
        $tip->update($request->only(['title', 'description', 'category', 'image']));
        return response()->json($tip);
    }

    public function destroy($id)
    {
    $tip = Tip::where('user_id', Auth::id())->findOrFail($id);
    $tip->delete();
     return response()->json(['message' => 'Your tip has been successfully deleted.'], 200);
    }


    
}
