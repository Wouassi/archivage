<?php

namespace App\Filament\Pages;

use App\Models\Depense;
use App\Models\Exercice;
use App\Services\WorkContextService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Filament\Pages\Dashboard as BaseDashboard;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-squares-2x2';
    protected static ?string $title = 'Tableau de bord';

    public ?int $exercice_id = null;
    public ?int $depense_id = null;

    public function mount(): void
    {
        $this->exercice_id = WorkContextService::getExerciceId();
        $this->depense_id = WorkContextService::getDepenseId();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('change_context')
                ->label(WorkContextService::isSet()
                    ? '🔄 ' . WorkContextService::getSummary()
                    : '⚙️ Définir le contexte de travail')
                ->color(WorkContextService::isSet() ? 'success' : 'warning')
                ->icon(WorkContextService::isSet() ? 'heroicon-o-check-circle' : 'heroicon-o-cog-6-tooth')
                ->size('lg')
                ->modalHeading('Sélection du contexte de travail')
                ->modalDescription('Choisissez l\'exercice budgétaire et la catégorie de dépense. Ce choix sera appliqué à toute l\'application.')
                ->modalIcon('heroicon-o-adjustments-horizontal')
                ->modalWidth('lg')
                ->form([
                    Section::make('Contexte budgétaire')
                        ->icon('heroicon-o-calendar-days')
                        ->schema([
                            Select::make('exercice_id')
                                ->label('📅 Exercice budgétaire')
                                ->options(Exercice::orderByDesc('annee')->pluck('annee', 'id')->map(fn ($a, $id) => "Exercice {$a} — " . (Exercice::find($id)->statut === 'actif' ? '🟢 Actif' : '🔴 Clos')))
                                ->default($this->exercice_id)
                                ->required()
                                ->searchable()
                                ->native(false)
                                ->helperText('Exercice sur lequel vous travaillez'),
                            Select::make('depense_id')
                                ->label('💰 Catégorie de dépense')
                                ->options(Depense::all()->mapWithKeys(fn ($d) => [
                                    $d->id => ($d->type === 'INVESTISSEMENT' ? '🏗️' : '⚙️') . " {$d->libelle} (Classe {$d->classe})"
                                ]))
                                ->default($this->depense_id)
                                ->required()
                                ->searchable()
                                ->native(false)
                                ->helperText('Type de dépense principal'),
                        ]),
                ])
                ->action(function (array $data) {
                    WorkContextService::setExercice($data['exercice_id']);
                    WorkContextService::setDepense($data['depense_id']);
                    $this->exercice_id = $data['exercice_id'];
                    $this->depense_id = $data['depense_id'];

                    Notification::make()
                        ->title('✅ Contexte de travail mis à jour')
                        ->body(WorkContextService::getSummary())
                        ->success()
                        ->duration(3000)
                        ->send();

                    $this->redirect(static::getUrl());
                }),
        ];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\KpiStatsWidget::class,
            \App\Filament\Widgets\MonthlyTrendChart::class,
            \App\Filament\Widgets\TypeDistributionChart::class,
            \App\Filament\Widgets\CompletionRateChart::class,
            \App\Filament\Widgets\RecentDossiersWidget::class,
            \App\Filament\Widgets\CloudStatusWidget::class,
        ];
    }
}
