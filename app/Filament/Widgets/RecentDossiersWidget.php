<?php

namespace App\Filament\Widgets;

use App\Models\Dossier;
use App\Services\WorkContextService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class RecentDossiersWidget extends BaseWidget
{
    protected static ?string $heading = '📋 Dossiers récents';
    protected static ?int $sort = 5;
    protected int|string|array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        $query = Dossier::with(['depense', 'exercice'])->latest()->limit(8);

        $exId = WorkContextService::getExerciceId();
        if ($exId) $query->where('exercice_id', $exId);

        $depId = WorkContextService::getDepenseId();
        if ($depId) $query->where('depense_id', $depId);

        return $table->query($query)->columns([
            Tables\Columns\TextColumn::make('ordre_paiement')
                ->label('N° OP')
                ->weight('bold')
                ->icon('heroicon-o-document-text')
                ->iconColor('primary'),
            Tables\Columns\TextColumn::make('beneficiaire')
                ->label('Bénéficiaire')
                ->limit(25)
                ->icon('heroicon-o-user')
                ->iconColor('gray'),
            Tables\Columns\TextColumn::make('montant_engage')
                ->label('Montant')
                ->numeric(thousandsSeparator: ' ')
                ->suffix(' FCFA')
                ->color('primary')
                ->weight('semibold'),
            Tables\Columns\BadgeColumn::make('depense.type')
                ->label('Type')
                ->colors([
                    'primary' => 'INVESTISSEMENT',
                    'success' => 'FONCTIONNEMENT',
                ]),
            Tables\Columns\IconColumn::make('fichier_path')
                ->label('PDF')
                ->boolean()
                ->trueIcon('heroicon-o-document-check')
                ->falseIcon('heroicon-o-x-circle')
                ->trueColor('success')
                ->falseColor('danger'),
            Tables\Columns\TextColumn::make('created_at')
                ->label('Créé')
                ->since()
                ->icon('heroicon-o-clock')
                ->iconColor('gray'),
        ])->paginated(false);
    }
}
