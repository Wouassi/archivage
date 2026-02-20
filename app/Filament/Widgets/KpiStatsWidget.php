<?php

namespace App\Filament\Widgets;

use App\Services\FinancialAnalyticsService;
use App\Services\WorkContextService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class KpiStatsWidget extends StatsOverviewWidget
{
    protected static ?int $sort = 1;
    protected int|string|array $columnSpan = 'full';

    protected function getStats(): array
    {
        $exId = WorkContextService::getExerciceId();
        $ex = WorkContextService::getExercice();

        if (!$ex) {
            return [
                Stat::make('⚠️ Aucun exercice', 'Définissez le contexte depuis le tableau de bord')
                    ->icon('heroicon-o-exclamation-triangle')
                    ->color('warning'),
            ];
        }

        $kpi = app(FinancialAnalyticsService::class)->getKpiSummary($exId);

        return [
            Stat::make('📁 Dossiers total', number_format($kpi['total_dossiers']))
                ->description("Exercice {$ex->annee}")
                ->icon('heroicon-o-folder')
                ->color('primary'),

            Stat::make('✅ Avec PDF', number_format($kpi['avec_pdf']))
                ->description("{$kpi['taux_completude']}% de complétude")
                ->icon('heroicon-o-document-check')
                ->color('success'),

            Stat::make('❌ Sans PDF', number_format($kpi['sans_pdf']))
                ->description($kpi['sans_pdf'] > 5 ? 'Seuil dépassé !' : 'Sous contrôle')
                ->icon('heroicon-o-document-minus')
                ->color($kpi['sans_pdf'] > 5 ? 'danger' : 'warning'),

            Stat::make('💰 Montant total', number_format($kpi['montant_total'], 0, ',', ' ') . ' FCFA')
                ->description("Budget {$ex->annee}")
                ->icon('heroicon-o-banknotes')
                ->color('primary'),

            Stat::make('🏗️ Investissement', number_format($kpi['montant_investissement'], 0, ',', ' ') . ' F')
                ->description($kpi['montant_total'] > 0
                    ? round(($kpi['montant_investissement'] / $kpi['montant_total']) * 100, 1) . '% du total'
                    : '0%')
                ->icon('heroicon-o-building-office')
                ->color('info'),

            Stat::make('⚙️ Fonctionnement', number_format($kpi['montant_fonctionnement'], 0, ',', ' ') . ' F')
                ->description($kpi['montant_total'] > 0
                    ? round(($kpi['montant_fonctionnement'] / $kpi['montant_total']) * 100, 1) . '% du total'
                    : '0%')
                ->icon('heroicon-o-cog')
                ->color('success'),
        ];
    }
}
