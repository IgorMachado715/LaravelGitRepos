<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use App\Models\Repository;
use Carbon\Carbon;

class FetchCommitsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $repository;
    protected $token;

    public function __construct(Repository $repository, $token)
    {
        $this->repository = $repository;
        $this->token = $token;
    }

    public function handle()
    {
        $repoName = $this->repository->name;
        $branches = Http::withToken($this->token)->get("https://api.github.com/repos/$repoName/branches")->json();

        $commitsData = [];

        foreach ($branches as $branch) {
            $branchName = $branch['name'];
            $page = 1;
            $perPage = 100;
            do {
                $commits = Http::withToken($this->token)->get("https://api.github.com/repos/$repoName/commits", [
                    'sha' => $branchName,
                    'since' => Carbon::now()->subDays(90)->toIso8601String(),
                    'page' => $page,
                    'per_page' => $perPage
                ])->json();

                foreach ($commits as $commit) {
                    $commitDate = Carbon::parse($commit['commit']['author']['date'])->toDateString();
                    if (!isset($commitsData[$commitDate])) {
                        $commitsData[$commitDate] = 0;
                    }
                    $commitsData[$commitDate]++;
                }

                $page++;
            } while (count($commits) === $perPage);
        }

        $commitsData = collect($commitsData)->map(function ($count, $date) {
            return ['date' => $date, 'count' => $count];
        })->values();

        
        $this->repository->commit_count = array_sum(array_column($commitsData->toArray(), 'count'));
        $this->repository->save();

       
        Cache::put("commits_{$this->repository->id}", $commitsData, now()->addMinutes(10));
    }
}
