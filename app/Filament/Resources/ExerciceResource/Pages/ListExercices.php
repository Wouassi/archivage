<?php
namespace App\Filament\Resources\ExerciceResource\Pages;
use App\Filament\Resources\ExerciceResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
class ListExercices extends ListRecords {
    protected static string $resource = ExerciceResource::class;
    protected function getHeaderActions(): array {
        return [
            Actions\CreateAction::make(),
            Actions\Action::make('export_pdf')->label('📥 Export PDF')->icon('heroicon-o-arrow-down-tray')->color('danger')
                ->url(route('export.pdf', 'exercices'), shouldOpenInNewTab: true),
        ];
    }
}
