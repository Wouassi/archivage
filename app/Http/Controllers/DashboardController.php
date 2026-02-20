<?php
namespace App\Http\Controllers;
use App\Models\Exercice;
use App\Services\FinancialAnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller {
    public function __construct(private FinancialAnalyticsService $analytics) {}
    public function kpiSummary(Request $r): JsonResponse {
        $id = $r->input('exercice_id', Exercice::getActif()?->id);
        return $id ? response()->json($this->analytics->getKpiSummary((int)$id)) : response()->json(['error' => 'No exercice'], 404);
    }
}
