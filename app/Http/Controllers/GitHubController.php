<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use App\Models\Repository; 
use App\Models\Commit; 
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class GitHubController extends Controller
{
    public function fetchReposAndCommits($username)
    {
        $user = Auth::user();
        $token = $user->github_token;

        
        $reposResponse = Http::withToken($token)->get("https://api.github.com/users/{$username}/repos");

        if (!$reposResponse->successful()) {
            Log::error('Failed to fetch repositories', ['response' => $reposResponse->body()]);
            return redirect()->route('dashboard')->with('error', 'Failed to fetch repositories.');
        }

        $repos = $reposResponse->json();

        foreach ($repos as $repo) {
            $repository = Repository::updateOrCreate([
                'github_id' => $repo['id'],
            ], [
                'name' => $repo['name'],
                'url' => $repo['html_url'],
                'user_id' => $user->id,
            ]);

            $latestCommit = Commit::where('repository_id', $repository->id)->orderBy('date', 'desc')->first();

            $since = $latestCommit ? Carbon::parse($latestCommit->date)->toIso8601String() : null;

            $commitsResponse = Http::withToken($token)->get("https://api.github.com/repos/{$username}/{$repo['name']}/commits", [
                'since' => $since,
            ]);

            if (!$commitsResponse->successful()) {
                Log::error('Failed to fetch commits', ['response' => $commitsResponse->body(), 'repo' => $repo['name']]);
                continue;
            }

            $commits = $commitsResponse->json();

            foreach ($commits as $commit) {
               
                if (!is_array($commit) || !isset($commit['sha'], $commit['commit']['message'], $commit['commit']['author']['date'])) {
                    Log::error('Invalid commit data', ['commit' => $commit]);
                    continue;
                }

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
