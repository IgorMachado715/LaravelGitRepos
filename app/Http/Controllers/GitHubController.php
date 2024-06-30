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

            // Fetch commits
            $page = 1;
            do {
                $commitsResponse = Http::withToken($token)->get("https://api.github.com/repos/{$username}/{$repo['name']}/commits", [
                    'per_page' => 100,
                    'page' => $page,
                ]);
                $commits = $commitsResponse->json();

                if (empty($commits)) {
                    break;
                }

                foreach ($commits as $commit) {
                    if (isset($commit['commit']['author']['date'])) {
                        Commit::updateOrCreate([
                            'sha' => $commit['sha'],
                        ], [
                            'message' => $commit['commit']['message'],
                            'repository_id' => $repository->id,
                            'date' => Carbon::parse($commit['commit']['author']['date'])->toDateTimeString(),
                        ]);
                    }
                }

                $page++;
            } while (!empty($commits));
        }

        return redirect()->route('dashboard');
    }
}
