<?php
namespace App\Filament\Resources\ActivityLogResource\Pages;
use App\Filament\Resources\ActivityLogResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListActivityLogs extends ListRecords {
    protected static string $resource = ActivityLogResource::class;
    protected function getHeaderActions(): array {
        return [
            Actions\Action::make('export_pdf')->label('📥 Export PDF')->icon('heroicon-o-arrow-down-tray')->color('danger')
                ->url(route('export.pdf', 'activity_logs'), shouldOpenInNewTab: true),
        ];
    }
}
