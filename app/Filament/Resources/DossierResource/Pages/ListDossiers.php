<?php

namespace App\Filament\Resources\DossierResource\Pages;

use App\Filament\Resources\DossierResource;
use App\Models\Dossier;
use App\Models\Exercice;
use App\Services\WorkContextService;
use Filament\Actions;
use Filament\Resources\Components\Tab;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Database\Eloquent\Builder;

class ListDossiers extends ListRecords
{
    protected static string $resource = DossierResource::class;

    // ══════════════════════════════════════════════════════════════
    // ACTIONS HEADER
    // ══════════════════════════════════════════════════════════════
    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('Nouveau Dossier')
                ->icon('heroicon-o-plus-circle')
                ->color('primary'),

            Actions\Action::make('export_pdf')
                ->label('📥 Export PDF')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('danger')
                ->url(fn () => $this->getExportUrl(), shouldOpenInNewTab: true),
        ];
    }

    /**
     * Construit l'URL d'export avec les filtres actifs (exercice, dépense).
     */
    private function getExportUrl(): string
    {
        $params = ['type' => 'dossiers'];

        if (class_exists(WorkContextService::class)) {
            $exId  = WorkContextService::getExerciceId();
            $depId = WorkContextService::getDepenseId();

            if ($exId)  $params['exercice_id'] = $exId;
            if ($depId) $params['depense_id']  = $depId;
        }

        return route('export.pdf', $params);
    }

    // ══════════════════════════════════════════════════════════════
    // REQUÊTE DE TABLE (filtrage par contexte de travail)
    // ══════════════════════════════════════════════════════════════
    protected function getTableQuery(): ?Builder
    {
        $query = parent::getTableQuery();

        if (class_exists(WorkContextService::class)) {
            $exId  = WorkContextService::getExerciceId();
            $depId = WorkContextService::getDepenseId();

            if ($exId)  $query->where('exercice_id', $exId);
            if ($depId) $query->where('depense_id', $depId);
        }

        return $query;
    }

    // ══════════════════════════════════════════════════════════════
    // ONGLETS DE FILTRE
    // ══════════════════════════════════════════════════════════════
    public function getTabs(): array
    {
        // Requête de base avec filtres contextuels
        $baseQuery = Dossier::query();

        if (class_exists(WorkContextService::class)) {
            $exId  = WorkContextService::getExerciceId();
            $depId = WorkContextService::getDepenseId();

            if ($exId)  $baseQuery->where('exercice_id', $exId);
            if ($depId) $baseQuery->where('depense_id', $depId);
        }

        // Exercice courant pour l'onglet dédié
        $exerciceCourant = Exercice::where('statut', 'actif')->first()
            ?? Exercice::orderByDesc('annee')->first();

        $annee             = $exerciceCourant?->annee ?? (int) date('Y');
        $exerciceCourantId = $exerciceCourant?->id;

        $tabs = [
            // ── Tous ────────────────────────────────────────────
            'tous' => Tab::make('📁 Tous')
                ->badge((clone $baseQuery)->count())
                ->badgeColor('gray')
                ->icon('heroicon-o-squares-2x2'),

            // ── Avec PDF ────────────────────────────────────────
            'avec_pdf' => Tab::make('✅ Avec PDF')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNotNull('fichier_path'))
                ->badge((clone $baseQuery)->whereNotNull('fichier_path')->count())
                ->badgeColor('success')
                ->icon('heroicon-o-document-check'),

            // ── Sans PDF ────────────────────────────────────────
            'sans_pdf' => Tab::make('❌ Sans PDF')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereNull('fichier_path'))
                ->badge((clone $baseQuery)->whereNull('fichier_path')->count())
                ->badgeColor('danger')
                ->icon('heroicon-o-document-minus'),

            // ── Investissement (via relation depense) ────────────
            'investissement' => Tab::make('🏗️ Investissement')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereHas(
                    'depense',
                    fn (Builder $dq) => $dq->where('type', 'INVESTISSEMENT')
                ))
                ->badge(
                    (clone $baseQuery)->whereHas(
                        'depense',
                        fn (Builder $dq) => $dq->where('type', 'INVESTISSEMENT')
                    )->count()
                )
                ->badgeColor('primary')
                ->icon('heroicon-o-building-office-2'),

            // ── Fonctionnement (via relation depense) ────────────
            'fonctionnement' => Tab::make('⚙️ Fonctionnement')
                ->modifyQueryUsing(fn (Builder $q) => $q->whereHas(
                    'depense',
                    fn (Builder $dq) => $dq->where('type', 'FONCTIONNEMENT')
                ))
                ->badge(
                    (clone $baseQuery)->whereHas(
                        'depense',
                        fn (Builder $dq) => $dq->where('type', 'FONCTIONNEMENT')
                    )->count()
                )
                ->badgeColor('success')
                ->icon('heroicon-o-cog-6-tooth'),
        ];

        // ── Non synchronisé (si le scope existe) ─────────────
        if (method_exists(Dossier::class, 'scopeCloudPending')) {
            $tabs['non_sync'] = Tab::make('☁️ Non sync.')
                ->modifyQueryUsing(fn (Builder $q) => $q->cloudPending())
                ->badge((clone $baseQuery)->cloudPending()->count())
                ->badgeColor('warning')
                ->icon('heroicon-o-cloud');
        }

        // ── Exercice courant ─────────────────────────────────
        $tabs["annee_{$annee}"] = Tab::make("📅 {$annee}")
            ->modifyQueryUsing(function (Builder $q) use ($exerciceCourantId, $annee) {
                if ($exerciceCourantId) {
                    return $q->where('exercice_id', $exerciceCourantId);
                }
                return $q->whereHas(
                    'exercice',
                    fn (Builder $eq) => $eq->where('annee', $annee)
                );
            })
            ->badge(
                $exerciceCourantId
                    ? (clone $baseQuery)->where('exercice_id', $exerciceCourantId)->count()
                    : (clone $baseQuery)->whereHas(
                        'exercice',
                        fn (Builder $eq) => $eq->where('annee', $annee)
                    )->count()
            )
            ->badgeColor('info')
            ->icon('heroicon-o-calendar');

        return $tabs;
    }
}
