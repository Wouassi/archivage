<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImputationResource\Pages;
use App\Models\Imputation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ImputationResource extends Resource
{
    protected static ?string $model = Imputation::class;
    protected static ?string $navigationIcon = 'heroicon-o-calculator';
    protected static ?string $navigationGroup = 'Comptabilité';
    protected static ?string $navigationLabel = 'Imputations';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Imputation budgétaire')
                ->icon('heroicon-o-calculator')
                ->description('Le code comptable doit contenir exactement 6 chiffres (OHADA/SYSCOHADA)')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('depense_id')
                        ->label('🏷️ Dépense parente')
                        ->relationship('depense', 'libelle')
                        ->searchable()->preload()->required()
                        ->helperText('La classe (2 ou 6) sera vérifiée automatiquement'),
                    Forms\Components\TextInput::make('compte')
                        ->label('🔢 Code comptable (6 chiffres)')
                        ->required()
                        ->minLength(6)
                        ->maxLength(6)
                        ->regex('/^\d{6}$/')
                        ->placeholder('Ex: 241000, 601100')
                        ->helperText('Exactement 6 chiffres. Le 1er chiffre doit correspondre à la classe de la dépense (2=Investissement, 6=Fonctionnement)')
                        ->live(onBlur: true)
                        ->afterStateUpdated(function (Forms\Components\TextInput $component, ?string $state) {
                            if ($state && !preg_match('/^\d{6}$/', $state)) {
                                $component->state(substr(preg_replace('/\D/', '', $state), 0, 6));
                            }
                        }),
                    Forms\Components\TextInput::make('libelle')
                        ->label('📝 Libellé')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('compte')
                ->label('Code')
                ->sortable()
                ->weight('bold')
                ->copyable()
                ->icon('heroicon-o-hashtag')
                ->iconColor('primary')
                ->searchable(),
            Tables\Columns\TextColumn::make('libelle')
                ->label('Libellé')
                ->searchable()
                ->limit(40),
            Tables\Columns\TextColumn::make('depense.libelle')
                ->label('Dépense')
                ->icon('heroicon-o-tag')
                ->iconColor('info'),
            Tables\Columns\BadgeColumn::make('depense.type')
                ->label('Type')
                ->colors([
                    'primary' => 'INVESTISSEMENT',
                    'success' => 'FONCTIONNEMENT',
                ]),
            Tables\Columns\TextColumn::make('dossiers_count')
                ->label('Dossiers')
                ->counts('dossiers')
                ->icon('heroicon-o-folder')
                ->iconColor('warning'),
        ])
        ->actions([
            Tables\Actions\EditAction::make()->iconButton(),
            Tables\Actions\DeleteAction::make()->iconButton(),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListImputations::route('/'),
            'create' => Pages\CreateImputation::route('/create'),
            'edit' => Pages\EditImputation::route('/{record}/edit'),
        ];
    }
}
