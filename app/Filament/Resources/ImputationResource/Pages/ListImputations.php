<?php
namespace App\Filament\Resources\ImputationResource\Pages;
use App\Filament\Resources\ImputationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListImputations extends ListRecords {
    protected static string $resource = ImputationResource::class;
    protected function getHeaderActions(): array {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('export_pdf')->label('📥 Export PDF')->icon('heroicon-o-arrow-down-tray')->color('danger')
                ->url(route('export.pdf', 'imputations'), shouldOpenInNewTab: true),
        ];
    }
}
