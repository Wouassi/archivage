<?php

namespace App\Filament\Resources\DossierResource\Pages;

use App\Filament\Resources\DossierResource;
use App\Jobs\SyncToCloudJob;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Storage;

class ViewDossier extends ViewRecord
{
    protected static string $resource = DossierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make()
                ->icon('heroicon-o-pencil-square')
                ->color('primary'),
            Actions\Action::make('telecharger_pdf')
                ->label('Télécharger PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->visible(fn () => $this->record->pdf_exists)
                ->action(fn () => response()->download(
                    Storage::disk('public')->path($this->record->fichier_path)
                )),
            Actions\Action::make('sync_cloud')
                ->label('Sauvegarder cloud')
                ->icon('heroicon-o-cloud-arrow-up')
                ->color('info')
                ->visible(fn () => $this->record->fichier_path && !$this->record->cloud_synced)
                ->requiresConfirmation()
                ->modalHeading('Synchronisation cloud')
                ->modalDescription('Le document sera envoyé vers le service cloud configuré.')
                ->action(function () {
                    SyncToCloudJob::dispatch($this->record);
                    Notification::make()->title('☁️ Synchronisation lancée')->info()->send();
                }),
            Actions\DeleteAction::make()
                ->icon('heroicon-o-trash')
                ->color('danger'),
        ];
    }
}
