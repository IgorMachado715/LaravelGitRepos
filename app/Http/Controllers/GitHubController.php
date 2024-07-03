<?php

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

        // Fetch repositories
        $reposResponse = Http::withToken($token)->get("https://api.github.com/users/{$username}/repos");
        $repos = $reposResponse->json();

        foreach ($repos as $repo) {
            // Handle errors in fetching repositories
            if (!is_array($repo) || isset($repo['message'])) {
                Log::error('Failed to fetch repository data', ['repo' => $repo]);
                continue; // Skip this repository and move to the next one
            }

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

            // Fetch commits
            $commitsResponse = Http::withToken($token)->get("https://api.github.com/repos/{$username}/{$repo['name']}/commits", [
                'since' => $since,
            ]);

            // Handle errors in fetching commits
            if ($commitsResponse->failed()) {
                Log::error('Failed to fetch commits', ['repo' => $repo['name'], 'status' => $commitsResponse->status()]);
                continue; // Skip this repository's commits and move to the next repository
            }

            $commits = $commitsResponse->json();

            foreach ($commits as $commit) {
                if (is_array($commit) && isset($commit['sha'])) {
                    Commit::updateOrCreate([
                        'sha' => $commit['sha'],
                    ], [
                        'message' => $commit['commit']['message'],
                        'repository_id' => $repository->id,
                        'date' => Carbon::parse($commit['commit']['author']['date'])->toDateTimeString(),
                    ]);
                }
            }
        }

        return redirect()->route('dashboard');
    }
}
