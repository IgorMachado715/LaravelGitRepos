<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Commit;
use Carbon\Carbon;

class CommitController extends Controller
{
    public function getCommitsData($repositoryId)
    {
        $commits = Commit::where('repository_id', $repositoryId)
            ->where('date', '>=', Carbon::now()->subMonths(2)->startOfMonth())
            ->get();

        
        $commitsPerDay = $commits->groupBy(function ($commit) {
            return Carbon::parse($commit->date)->format('Y-m-d');
        })->map->count();

        
        $labels = [];
        $counts = [];

        for ($i = 2; $i >= 0; $i--) {
            $startOfMonth = Carbon::now()->subMonths($i)->startOfMonth();
            $endOfMonth = Carbon::now()->subMonths($i)->endOfMonth();
            $monthName = Carbon::now()->subMonths($i)->format('F');

            for ($date = $startOfMonth; $date->lte($endOfMonth); $date->addDay()) {
                $day = $date->format('Y-m-d');
                $labels[] = $date->format('d') . ' ' . $monthName;
                $counts[] = $commitsPerDay->get($day, 0);
            }
        }

        return response()->json([
            'labels' => $labels,
            'counts' => $counts,
        ]);
    }
}
