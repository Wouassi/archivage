<?php
namespace App\Filament\Resources\DepenseResource\Pages;
use App\Filament\Resources\DepenseResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListDepenses extends ListRecords {
    protected static string $resource = DepenseResource::class;
    protected function getHeaderActions(): array {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('export_pdf')->label('📥 Export PDF')->icon('heroicon-o-arrow-down-tray')->color('danger')
                ->url(route('export.pdf', 'depenses'), shouldOpenInNewTab: true),
        ];
    }
}
