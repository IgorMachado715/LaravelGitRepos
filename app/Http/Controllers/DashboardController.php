<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Repository;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $repositories = Repository::where('user_id', $user->id)->get();
        return view('dashboard', compact('repositories'));
    }
}