<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\Repository; 
use App\Models\Commit; 
use Carbon\Carbon;

class GitHubController extends Controller
{
    public function fetchReposAndCommits($username)
    {
        $user = Auth::user();
        $token = $user->github_token;

        // Fetch repositories
        $reposResponse = Http::withToken($token)->get("https://api.github.com/users/{$username}/repos");
        $repos = $reposResponse->json();

        foreach ($repos as $repo) {
            $repository = Repository::updateOrCreate([
                'github_id' => $repo['id'],
            ], [
                'name' => $repo['name'],
                'url' => $repo['html_url'],
                'user_id' => $user->id,
            ]);

            // Fetch the latest commit in the repository
            $latestCommit = Commit::where('repository_id', $repository->id)->orderBy('date', 'desc')->first();

            $since = $latestCommit ? Carbon::parse($latestCommit->date)->toIso8601String() : null;

            $commitsResponse = Http::withToken($token)->get("https://api.github.com/repos/{$username}/{$repo['name']}/commits", [
                'since' => $since,
            ]);

            $commits = $commitsResponse->json();

            foreach ($commits as $commit) {
                Commit::updateOrCreate([
                    'sha' => $commit['sha'],
                ], [
                    'message' => $commit['commit']['message'],
                    'repository_id' => $repository->id,
                    'date' => Carbon::parse($commit['commit']['author']['date'])->toDateTimeString(),
                ]);
            }
        }

        return redirect()->route('dashboard');
    }
}
