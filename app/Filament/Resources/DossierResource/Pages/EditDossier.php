<?php
namespace App\Filament\Resources\DossierResource\Pages;
use App\Events\DocumentArchived;
use App\Filament\Resources\DossierResource;
use App\Models\ActivityLog;
use App\Models\Depense;
use App\Models\Exercice;
use App\Services\PdfMerger;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Storage;

class EditDossier extends EditRecord {
    protected static string $resource = DossierResource::class;

    protected function getHeaderActions(): array {
        return [Actions\ViewAction::make(), Actions\DeleteAction::make(),
            Actions\Action::make('telecharger_pdf')->label('Télécharger PDF')->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => $this->record->pdf_exists)
                ->action(fn () => response()->download(Storage::disk('public')->path($this->record->fichier_path))),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array {
        $allPdf = [];
        if (!empty($data['fichiers_upload'])) {
            foreach ($data['fichiers_upload'] as $f) { $abs = Storage::disk('public')->path($f); if (file_exists($abs)) $allPdf[] = $abs; }
        }
        if (!empty($data['fichiers_pdf_paths'])) {
            foreach (explode(',', $data['fichiers_pdf_paths']) as $p) { $p = trim($p); if ($p && file_exists($p)) $allPdf[] = $p; }
        }
        if (!empty($allPdf)) {
            if ($this->record->fichier_path && Storage::disk('public')->exists($this->record->fichier_path)) Storage::disk('public')->delete($this->record->fichier_path);
            $dep = Depense::find($data['depense_id'] ?? $this->record->depense_id);
            $ex = Exercice::find($data['exercice_id'] ?? $this->record->exercice_id);
            $fn = str_replace(['/', '\\', ' '], '_', $data['ordre_paiement'] ?? $this->record->ordre_paiement);
            $rel = PdfMerger::merge($allPdf, $fn, $dep?->type ?? 'FONCTIONNEMENT', (string)($ex?->annee ?? date('Y')));
            if ($rel) { $data['fichier_path'] = $rel; $data['cloud_synced_at'] = null; }
            PdfMerger::cleanupTemp($allPdf);
        }
        unset($data['fichiers_upload'], $data['fichiers_pdf_paths'], $data['larascan']);
        return $data;
    }

    protected function afterSave(): void {
        $d = $this->record;
        ActivityLog::logActivity('modification', "Dossier {$d->ordre_paiement} modifié", $d, $d->getOriginal(), $d->toArray());
        if ($d->fichier_path && is_null($d->cloud_synced_at)) event(new DocumentArchived($d));
    }
}
