<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
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
        
        $reposResponse = Http::withToken($token)->get("https://api.github.com/user/repos");
        if (!$reposResponse->successful()) {
            Log::error('Failed to fetch repositories', ['response' => $reposResponse->body()]);
            return redirect()->route('dashboard')->withErrors('Failed to fetch repositories from GitHub.');
        }
        $repos = $reposResponse->json();
        if (!is_array($repos)) {
            Log::error('Invalid repositories response', ['response' => $repos]);
            return redirect()->route('dashboard')->withErrors('Invalid response from GitHub.');
        }
        foreach ($repos as $repo) {
            if (!is_array($repo) || !isset($repo['id'], $repo['name'], $repo['html_url'])) {
                Log::error('Invalid repository data', ['repo' => $repo]);
                continue;
            }
            
            $repository = Repository::updateOrCreate([
                'github_id' => $repo['id'],
            ], [
                'name' => $repo['name'],
                'url' => $repo['html_url'],
                'user_id' => $user->id,
            ]);
            $since = Carbon::now()->subDays(90)->toIso8601String();
            $commitsResponse = Http::withToken($token)->get("https://api.github.com/repos/{$username}/{$repo['name']}/commits", [
                'since' => $since,
            ]);
            if (!$commitsResponse->successful()) {
                Log::error('Failed to fetch commits', ['response' => $commitsResponse->body(), 'repo' => $repo['name']]);
                continue;
            }
            $commits = $commitsResponse->json();
            if (!is_array($commits)) {
                Log::error('Invalid commits response', ['response' => $commits, 'repo' => $repo['name']]);
                continue;
            }
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
        Cache::forget("user_{$user->id}_repositories");
        
        return redirect()->route('dashboard')->with('status', 'Repositories and commits updated successfully.');
    }

    public function refetchReposAndCommits()
{
    $user = Auth::user();
    $username = $user->username;
    $token = $user->github_token;
    
    $reposResponse = Http::withToken($token)->get("https://api.github.com/user/repos");
    if (!$reposResponse->successful()) {
        Log::error('Failed to fetch repositories', ['response' => $reposResponse->body()]);
        return response()->json(['error' => 'Failed to fetch repositories from GitHub.'], 500);
    }
    $repos = $reposResponse->json();
    if (!is_array($repos)) {
        Log::error('Invalid repositories response', ['response' => $repos]);
        return response()->json(['error' => 'Invalid response from GitHub.'], 500);
    }
    $allRepositories = [];
    foreach ($repos as $repo) {
        if (!is_array($repo) || !isset($repo['id'], $repo['name'], $repo['html_url'])) {
            Log::error('Invalid repository data', ['repo' => $repo]);
            continue;
        }
        
        $repository = Repository::updateOrCreate([
            'github_id' => $repo['id'],
        ], [
            'name' => $repo['name'],
            'url' => $repo['html_url'],
            'user_id' => $user->id,
        ]);

        $since = Carbon::now()->subDays(90)->toIso8601String();
        $commitsResponse = Http::withToken($token)->get("https://api.github.com/repos/{$username}/{$repo['name']}/commits", [
            'since' => $since,
        ]);
        if (!$commitsResponse->successful()) {
            Log::error('Failed to fetch commits', ['response' => $commitsResponse->body(), 'repo' => $repo['name']]);
            continue;
        }
        $commits = $commitsResponse->json();
        if (!is_array($commits)) {
            Log::error('Invalid commits response', ['response' => $commits, 'repo' => $repo['name']]);
            continue;
        }
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

        $repositoryData = [
            'id' => $repository->id,
            'name' => $repository->name,
            'url' => $repository->url,
            'commits' => $commits,
        ];
        $allRepositories[] = $repositoryData;
    }
    Cache::forget("user_{$user->id}_repositories");

    return response()->json($allRepositories);
}
}