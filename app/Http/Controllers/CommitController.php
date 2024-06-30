<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Commit;
use App\Models\Repository;
use Carbon\Carbon;

class CommitController extends Controller
{
    public function getCommitsData($repositoryId)
    {
        $commits = Commit::where('repository_id', $repositoryId)
            ->where('date', '>=', Carbon::now()->subMonths(2)->startOfMonth())
            ->get();

        $commitsPerMonth = $commits->groupBy(function ($commit) {
            return Carbon::parse($commit->date)->format('Y-m');
        })->map->count();

        $months = [];
        $counts = [];
        
        for ($i = 2; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');
            $months[] = Carbon::now()->subMonths($i)->format('F');
            $counts[] = $commitsPerMonth->get($month, 0);
        }

        return response()->json([
            'labels' => $months,
            'counts' => $counts,
        ]);
    }
}
