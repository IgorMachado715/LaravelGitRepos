<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use App\Models\Repository;
use App\Jobs\FetchCommitsJob;

class GithubController extends Controller
{
    public function fetchRepos()
    {
        $user = Auth::user();
        $token = $user->github_token;

        $repos = [];

        
        $page = 1;
        do {
            $response = Http::withToken($token)->get('https://api.github.com/user/repos', [
                'page' => $page,
                'per_page' => 100
            ]);
            $userRepos = $response->json();
            $repos = array_merge($repos, $userRepos);
            $page++;
        } while (count($userRepos) == 100);

        
        $orgs = Http::withToken($token)->get('https://api.github.com/user/orgs')->json();
        foreach ($orgs as $org) {
            $page = 1;
            do {
                $response = Http::withToken($token)->get("https://api.github.com/orgs/{$org['login']}/repos", [
                    'page' => $page,
                    'per_page' => 100
                ]);
                $orgRepos = $response->json();
                $repos = array_merge($repos, $orgRepos);
                $page++;
            } while (count($orgRepos) == 100);
        }

        
        foreach ($repos as $repo) {
            Repository::updateOrCreate(
                ['name' => $repo['full_name'], 'user_id' => $user->id],
                ['commit_count' => 0] 
            );
        }

        $repositories = Repository::where('user_id', $user->id)->get();

        return view('dashboard', compact('repositories'));
    }

    public function getCommits($repositoryId)
    {
        $repository = Repository::find($repositoryId);
        if (!$repository) {
            return response()->json([]);
        }

        $user = Auth::user();
        $token = $user->github_token;

        
        if (Cache::has("commits_{$repositoryId}")) {
            $commitsData = Cache::get("commits_{$repositoryId}");
        } else {
            
            FetchCommitsJob::dispatch($repository, $token);

            
            return response()->json(['message' => 'Commits data is being fetched. Please try again later.']);
        }

        return response()->json($commitsData);
    }
}
