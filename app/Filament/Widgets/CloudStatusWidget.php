<?php
namespace App\Filament\Widgets;
use App\Services\CloudStorageService;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CloudStatusWidget extends StatsOverviewWidget {
    protected static ?int $sort = 6;
    protected function getStats(): array {
        $s = app(CloudStorageService::class)->getStatus();
        $color = match (true) { !$s['enabled'] => 'gray', $s['pending'] === 0 => 'success', default => 'warning' };
        return [Stat::make('Cloud', $s['enabled'] ? ucfirst(str_replace('_',' ',$s['provider'])) : 'Désactivé')
            ->description("{$s['synced']}/{$s['total_avec_pdf']} sync ({$s['taux']}%)")->icon('heroicon-o-cloud')->color($color)];
    }
}
