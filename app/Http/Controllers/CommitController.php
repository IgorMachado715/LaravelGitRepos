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
    $startDate = Carbon::now()->subDays(89); 
    $endDate = Carbon::now();

    $commits = Commit::where('repository_id', $repositoryId)
        ->whereBetween('date', [$startDate->startOfDay(), $endDate->endOfDay()])
        ->get()
        ->groupBy(function ($commit) {
            return Carbon::parse($commit->date)->format('Y-m-d');
        });

    $labels = [];
    $counts = [];
    $currentDate = $startDate->copy();

    while ($currentDate->lte($endDate)) {
        $day = $currentDate->format('Y-m-d');
        $labels[] = $currentDate->format('d M'); 
        $counts[] = $commits->get($day, collect())->count();
        $currentDate->addDay();
    }

    return response()->json([
        'labels' => $labels,
        'counts' => $counts,
    ]);
}
}