<?php
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\GitHubController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\CommitController;
use Illuminate\Support\Facades\Route;
use Laravel\Socialite\Facades\Socialite;


Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/auth/github/redirect', function () {
    return Socialite::driver('github')->stateless()->redirect();
});
 
Route::get('/auth/github/callback', function () {
    $githubUser = Socialite::driver('github')->stateless()->user();
 
    $user = User::updateOrCreate([
        'github_id' => $githubUser->id,
    ], [
        'name' => $githubUser->name,
        'email' => $githubUser->email,
        'github_token' => $githubUser->token,
        'github_refresh_token' => $githubUser->refreshToken,
    ]);
 
    Auth::login($user);
 
    return redirect()->route('github.repos', ['username' => $githubUser->nickname]);
});

Route::get('/github/repos/{username}', [GitHubController::class, 'fetchReposAndCommits'])->name('github.repos');
Route::get('/api/commits/{repository}', [CommitController::class, 'getCommitsData']);
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

Route::fallback(function () {
    return view('errors/4xx');
});


require __DIR__.'/auth.php';
