<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Models\Repository;

class DashboardController extends Controller
{
    public function index()
    {
        $repositories = Repository::where('user_id', Auth::id())->get();

        return view('dashboard', compact('repositories'));
    }
}
