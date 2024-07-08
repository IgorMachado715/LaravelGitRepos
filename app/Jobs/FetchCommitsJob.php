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
use GuzzleHttp\Client;
use GuzzleHttp\Promise;

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
        $client = new Client();

      
        $branchesResponse = $client->request('GET', "https://api.github.com/repos/$repoName/branches", [
            'headers' => [
                'Authorization' => "Bearer {$this->token}"
            ]
        ]);
        $branches = json_decode($branchesResponse->getBody(), true);

        $commitsData = [];
        $promises = [];

        foreach ($branches as $branch) {
            $branchName = $branch['name'];
            $page = 1;
            $perPage = 100;
            do {
                $promises[] = $client->requestAsync('GET', "https://api.github.com/repos/$repoName/commits", [
                    'headers' => [
                        'Authorization' => "Bearer {$this->token}"
                    ],
                    'query' => [
                        'sha' => $branchName,
                        'since' => Carbon::now()->subDays(90)->toIso8601String(),
                        'page' => $page,
                        'per_page' => $perPage
                    ]
                ]);
                $page++;
            } while (count($promises) % $perPage === 0);
        }

        $responses = Promise\settle($promises)->wait();

        foreach ($responses as $response) {
            if ($response['state'] === 'fulfilled') {
                $commits = json_decode($response['value']->getBody(), true);
                foreach ($commits as $commit) {
                    $commitDate = Carbon::parse($commit['commit']['author']['date'])->toDateString();
                    if (!isset($commitsData[$commitDate])) {
                        $commitsData[$commitDate] = 0;
                    }
                    $commitsData[$commitDate]++;
                }
            }
        }

        $commitsData = collect($commitsData)->map(function ($count, $date) {
            return ['date' => $date, 'count' => $count];
        })->values();

        $this->repository->commit_count = array_sum(array_column($commitsData->toArray(), 'count'));
        $this->repository->save();

        Cache::put("commits_{$this->repository->id}", $commitsData, now()->addMinutes(10));
    }
}
