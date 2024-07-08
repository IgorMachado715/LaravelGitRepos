<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Models\Commit;
use App\Models\Repository;
use Carbon\Carbon;

class CommitController extends Controller
{
    public function getCommitsData($repositoryId)
    {
        $repository = Repository::findOrFail($repositoryId);
        $commits = Commit::where('repository_id', $repositoryId)
            ->where('date', '>=', Carbon::now()->subDays(90))
            ->orderBy('date')
            ->get();

        $commitCountsByDay = [];
        foreach ($commits as $commit) {
            $date = $commit->date->format('Y-m-d');
            if (!isset($commitCountsByDay[$date])) {
                $commitCountsByDay[$date] = 0;
            }
            $commitCountsByDay[$date]++;
        }

        $labels = array_keys($commitCountsByDay);
        $counts = array_values($commitCountsByDay);

        return response()->json([
            'labels' => $labels,
            'counts' => $counts,
        ]);
    }
}
