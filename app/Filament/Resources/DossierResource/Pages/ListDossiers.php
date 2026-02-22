<?php
namespace App\Filament\Resources\DossierResource\Pages;

use App\Filament\Resources\DossierResource;
use App\Models\Dossier;
use App\Services\WorkContextService;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;

class ListDossiers extends ListRecords
{
    protected static string $resource = DossierResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouveau dossier')
                ->icon('heroicon-o-plus-circle')
                ->color('primary'),

            Actions\Action::make('export_pdf')
                ->label('📥 Export PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->url(route('export.pdf', 'dossiers'), shouldOpenInNewTab: true),

            // ═══ RAPPORT AUTOMATIQUE ═══
            Actions\Action::make('generate_report')
                ->label('📊 Rapport')
                ->icon('heroicon-o-document-chart-bar')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Générer un rapport d\'exercice')
                ->modalDescription('Rapport complet avec statistiques, ventilations, top bénéficiaires.')
                ->modalSubmitActionLabel('Générer')
                ->form([
                    \Filament\Forms\Components\Select::make('exercice_id')
                        ->label('Exercice')
                        ->options(\App\Models\Exercice::orderByDesc('annee')->pluck('annee', 'id'))
                        ->default(WorkContextService::getExerciceId())
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data) {
                    if (!class_exists(\App\Services\ReportGeneratorService::class)) {
                        Notification::make()->title('Service indisponible')->danger()->send();
                        return;
                    }
                    $path = \App\Services\ReportGeneratorService::generate($data['exercice_id']);
                    if ($path) {
                        $url = Storage::disk('public')->url($path);
                        Notification::make()
                            ->title('✅ Rapport généré')
                            ->body(basename($path))
                            ->success()
                            ->actions([
                                \Filament\Notifications\Actions\Action::make('open')
                                    ->label('📄 Ouvrir')
                                    ->url($url)
                                    ->openUrlInNewTab(),
                            ])
                            ->persistent()
                            ->send();
                    } else {
                        Notification::make()->title('❌ Aucun dossier trouvé')->danger()->send();
                    }
                }),
        ];
    }

    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery();
        if ($id = WorkContextService::getExerciceId()) $query->where('exercice_id', $id);
        if ($id = WorkContextService::getDepenseId()) $query->where('depense_id', $id);
        return $query;
    }

    public function getTabs(): array
    {
        $base = Dossier::query();
        if ($id = WorkContextService::getExerciceId()) $base->where('exercice_id', $id);
        if ($id = WorkContextService::getDepenseId()) $base->where('depense_id', $id);

        return [
            'tous'     => Tab::make('Tous')->badge((clone $base)->count())->badgeColor('gray')->icon('heroicon-o-squares-2x2'),
            'avec_pdf' => Tab::make('Avec PDF')->badge((clone $base)->avecPdf()->count())->badgeColor('success')
                ->icon('heroicon-o-document-check')->modifyQueryUsing(fn (Builder $q) => $q->avecPdf()),
            'sans_pdf' => Tab::make('Sans PDF')->badge((clone $base)->sansPdf()->count())->badgeColor('danger')
                ->icon('heroicon-o-document-minus')->modifyQueryUsing(fn (Builder $q) => $q->sansPdf()),
            'non_sync' => Tab::make('Non sync.')->badge((clone $base)->cloudPending()->count())->badgeColor('warning')
                ->icon('heroicon-o-cloud')->modifyQueryUsing(fn (Builder $q) => $q->cloudPending()),
        ];
    }
}
