<?php
namespace App\Filament\Resources\DossierResource\Pages;
use App\Filament\Resources\DossierResource;
use App\Models\Dossier;
use App\Services\WorkContextService;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListDossiers extends ListRecords {
    protected static string $resource = DossierResource::class;

    protected function getHeaderActions(): array {
        return [
            Actions\CreateAction::make()->label('Nouveau dossier')->icon('heroicon-o-plus-circle')->color('primary'),
            Actions\Action::make('export_pdf')->label('📥 Export PDF')->icon('heroicon-o-arrow-down-tray')->color('danger')
                ->url(route('export.pdf', 'dossiers'), shouldOpenInNewTab: true),
        ];
    }

    protected function getTableQuery(): ?Builder {
        $query = parent::getTableQuery();
        $exId = WorkContextService::getExerciceId();
        $depId = WorkContextService::getDepenseId();
        if ($exId) $query->where('exercice_id', $exId);
        if ($depId) $query->where('depense_id', $depId);
        return $query;
    }

    public function getTabs(): array {
        $exId = WorkContextService::getExerciceId();
        $depId = WorkContextService::getDepenseId();
        $base = Dossier::query();
        if ($exId) $base->where('exercice_id', $exId);
        if ($depId) $base->where('depense_id', $depId);

        return [
            'tous' => Tab::make('Tous')->badge((clone $base)->count())->badgeColor('gray')->icon('heroicon-o-squares-2x2'),
            'avec_pdf' => Tab::make('Avec PDF')->badge((clone $base)->avecPdf()->count())->badgeColor('success')
                ->icon('heroicon-o-document-check')->modifyQueryUsing(fn (Builder $q) => $q->avecPdf()),
            'sans_pdf' => Tab::make('Sans PDF')->badge((clone $base)->sansPdf()->count())->badgeColor('danger')
                ->icon('heroicon-o-document-minus')->modifyQueryUsing(fn (Builder $q) => $q->sansPdf()),
            'non_sync' => Tab::make('Non sync.')->badge((clone $base)->cloudPending()->count())->badgeColor('warning')
                ->icon('heroicon-o-cloud')->modifyQueryUsing(fn (Builder $q) => $q->cloudPending()),
        ];
    }
}
