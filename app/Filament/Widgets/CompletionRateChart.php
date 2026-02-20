<?php
namespace App\Filament\Widgets;
use App\Models\Dossier;
use App\Models\Exercice;
use Filament\Widgets\ChartWidget;

class CompletionRateChart extends ChartWidget {
    protected static ?string $heading = 'Taux de complétude PDF';
    protected static ?int $sort = 4;
    protected int|string|array $columnSpan = 1;
    protected static ?string $maxHeight = '280px';

    protected function getData(): array {
        $ex = Exercice::getActif();
        if (!$ex) return ['datasets' => [], 'labels' => []];
        return [
            'datasets' => [['data' => [Dossier::parExercice($ex->id)->avecPdf()->count(), Dossier::parExercice($ex->id)->sansPdf()->count()], 'backgroundColor' => ['#0D7C66', '#B83232']]],
            'labels' => ['Avec PDF', 'Sans PDF'],
        ];
    }
    protected function getType(): string { return 'doughnut'; }
}
