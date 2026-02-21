<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ImputationResource\Pages;
use App\Models\Depense;
use App\Models\Imputation;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ImputationResource extends Resource
{
    protected static ?string $model = Imputation::class;
    protected static ?string $navigationIcon  = 'heroicon-o-calculator';
    protected static ?string $navigationGroup = 'Comptabilité';
    protected static ?string $navigationLabel = 'Imputations';
    protected static ?int $navigationSort      = 3;

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
                        ->preload()
                        ->required()
                        ->live()
                        // Quand la dépense change, pré-remplir le 1er chiffre du code
                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state) {
                            if (! $state) return;

                            $depense = Depense::find($state);
                            if (! $depense) return;

                            $classe = $depense->classe; // '2' ou '6'
                            $currentCompte = $get('compte') ?? '';

                            // Si le champ est vide → pré-remplir avec la classe + 5 zéros
                            if (empty($currentCompte)) {
                                $set('compte', $classe . '00000');
                                return;
                            }

                            // Si le 1er chiffre ne correspond pas → le corriger
                            if (strlen($currentCompte) >= 1 && substr($currentCompte, 0, 1) !== $classe) {
                                $set('compte', $classe . substr($currentCompte, 1));
                            }
                        })
                        ->helperText(function (Forms\Get $get) {
                            $depenseId = $get('depense_id');
                            if (! $depenseId) return 'Sélectionnez une dépense pour déterminer la classe';

                            $depense = Depense::find($depenseId);
                            if (! $depense) return null;

                            return match ($depense->type) {
                                'INVESTISSEMENT' => '📦 Investissement → le code doit commencer par 2',
                                'FONCTIONNEMENT' => '⚙️ Fonctionnement → le code doit commencer par 6',
                                default => null,
                            };
                        }),

                    Forms\Components\TextInput::make('compte')
                        ->label('🔢 Code comptable (6 chiffres)')
                        ->required()
                        ->minLength(6)
                        ->maxLength(6)
                        ->regex('/^\d{6}$/')
                        ->placeholder(function (Forms\Get $get) {
                            $depenseId = $get('depense_id');
                            if (! $depenseId) return 'Choisissez d\'abord la dépense';

                            $depense = Depense::find($depenseId);
                            if (! $depense) return '000000';

                            return match ($depense->type) {
                                'INVESTISSEMENT' => '2XXXXX (ex: 241000)',
                                'FONCTIONNEMENT' => '6XXXXX (ex: 601100)',
                                default => '000000',
                            };
                        })
                        ->helperText(function (Forms\Get $get) {
                            $depenseId = $get('depense_id');
                            if (! $depenseId) return 'Exactement 6 chiffres';

                            $depense = Depense::find($depenseId);
                            if (! $depense) return 'Exactement 6 chiffres';

                            return "Le 1er chiffre doit être {$depense->classe} ({$depense->type})";
                        })
                        // Validation custom : vérifier que le 1er chiffre correspond à la classe
                        ->rules([
                            function (Forms\Get $get) {
                                return function (string $attribute, $value, \Closure $fail) use ($get) {
                                    if (! $value) return;

                                    // Vérifier format 6 chiffres
                                    if (! preg_match('/^\d{6}$/', $value)) {
                                        $fail('Le code comptable doit contenir exactement 6 chiffres.');
                                        return;
                                    }

                                    // Vérifier cohérence avec la dépense
                                    $depenseId = $get('depense_id');
                                    if (! $depenseId) return;

                                    $depense = Depense::find($depenseId);
                                    if (! $depense) return;

                                    $premierChiffre = substr($value, 0, 1);
                                    if ($premierChiffre !== $depense->classe) {
                                        $fail(
                                            "Le code doit commencer par {$depense->classe} pour une dépense de type {$depense->type}. "
                                            . "Vous avez saisi {$premierChiffre}."
                                        );
                                    }
                                };
                            },
                        ]),

                    Forms\Components\TextInput::make('libelle')
                        ->label('📝 Libellé')
                        ->required()
                        ->maxLength(255)
                        ->columnSpanFull()
                        ->placeholder('Ex: Matériel de bureau, Fournitures, Équipement informatique...'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('compte')
                    ->label('Code')->sortable()->weight('bold')->copyable()
                    ->icon('heroicon-o-hashtag')->iconColor('primary')->searchable(),
                Tables\Columns\TextColumn::make('libelle')
                    ->label('Libellé')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('depense.libelle')
                    ->label('Dépense')->icon('heroicon-o-tag')->iconColor('info'),
                Tables\Columns\BadgeColumn::make('depense.type')
                    ->label('Type')
                    ->colors(['primary' => 'INVESTISSEMENT', 'success' => 'FONCTIONNEMENT']),
                Tables\Columns\TextColumn::make('dossiers_count')
                    ->label('Dossiers')->counts('dossiers')
                    ->icon('heroicon-o-folder')->iconColor('warning'),
            ])
            ->actions([
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListImputations::route('/'),
            'create' => Pages\CreateImputation::route('/create'),
            'edit'   => Pages\EditImputation::route('/{record}/edit'),
        ];
    }
}
