<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class FavoriteController extends Controller
{
    public function index()
    {
        $user_id = Auth::id();

        $favorites = DB::table('favorites')
            ->join('yslygi', 'favorites.yslygi_id', '=', 'yslygi.id_yslygi')
            ->where('favorites.user_id', $user_id)
            ->select('yslygi.id_yslygi', 'yslygi.name', 'yslygi.opisanie', 'yslygi.price', 'yslygi.foto')
            ->get();

        return view('favorites', compact('favorites'));
    }

    public function add(Request $request)
    {
        if (!Auth::check() || !$request->has('product_id')) {
            return redirect()->route('home');
        }

        $user_id = Auth::id();
        $product_id = $request->input('product_id');

        $exists = Favorite::where('user_id', $user_id)
            ->where('yslygi_id', $product_id)
            ->exists();

        if ($exists) {
            return back()->with('notification', 'Услуга уже в избранном')->with('notification_type', 'info');
        }

        Favorite::create([
            'user_id' => $user_id,
            'yslygi_id' => $product_id,
        ]);

        return back()->with('notification', 'Услуга добавлена в избранное!')->with('notification_type', 'success');
    }

    public function remove(Request $request)
    {
        if (!Auth::check()) {
            return response()->json(['status' => 'error', 'message' => 'Пожалуйста, войдите в систему']);
        }

        Favorite::where('user_id', Auth::id())
            ->where('yslygi_id', $request->input('product_id'))
            ->delete();

        return response()->json(['status' => 'success', 'message' => 'Услуга удалена из избранного']);
    }
}
