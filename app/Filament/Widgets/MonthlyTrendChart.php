<?php
namespace App\Filament\Widgets;
use App\Models\Exercice;
use App\Services\FinancialAnalyticsService;
use Filament\Widgets\ChartWidget;

class MonthlyTrendChart extends ChartWidget {
    protected static ?string $heading = 'Évolution mensuelle des montants';
    protected static ?int $sort = 2;
    protected int|string|array $columnSpan = 'full';
    protected static ?string $maxHeight = '300px';

    protected function getData(): array {
        $ex = Exercice::getActif();
        if (!$ex) return ['datasets' => [], 'labels' => []];
        $trend = app(FinancialAnalyticsService::class)->getMonthlyTrend($ex->id);
        return [
            'datasets' => [['label' => "Montants {$ex->annee} (FCFA)", 'data' => array_column($trend, 'total'), 'borderColor' => '#1B2A4A', 'backgroundColor' => 'rgba(27,42,74,0.1)', 'fill' => true, 'tension' => 0.4]],
            'labels' => array_column($trend, 'mois'),
        ];
    }
    protected function getType(): string { return 'line'; }
}
