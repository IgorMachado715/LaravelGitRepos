<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use App\Models\Repository;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $repositories = Cache::remember("user_{$user->id}_repositories", 60 * 60, function () use ($user) {
            return Repository::where('user_id', $user->id)->get();
        });

        return view('dashboard', compact('repositories'));
    }
}
