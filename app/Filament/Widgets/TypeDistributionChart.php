<?php
namespace App\Filament\Widgets;
use App\Models\Depense;
use App\Models\Exercice;
use App\Services\FinancialAnalyticsService;
use Filament\Widgets\ChartWidget;

class TypeDistributionChart extends ChartWidget {
    protected static ?string $heading = 'Répartition Invest. / Fonct.';
    protected static ?int $sort = 3;
    protected int|string|array $columnSpan = 1;
    protected static ?string $maxHeight = '280px';

    protected function getData(): array {
        $ex = Exercice::getActif();
        if (!$ex) return ['datasets' => [], 'labels' => []];
        $a = app(FinancialAnalyticsService::class);
        return [
            'datasets' => [['data' => [$a->getTotalByType($ex->id, Depense::TYPE_INVESTISSEMENT), $a->getTotalByType($ex->id, Depense::TYPE_FONCTIONNEMENT)], 'backgroundColor' => ['#1B2A4A', '#0D7C66']]],
            'labels' => ['Investissement', 'Fonctionnement'],
        ];
    }
    protected function getType(): string { return 'doughnut'; }
}
