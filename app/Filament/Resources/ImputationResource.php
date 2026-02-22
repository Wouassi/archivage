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
                        ->preload()->required()->live()
                        ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state) {
                            if (!$state) return;
                            $depense = Depense::find($state);
                            if (!$depense) return;
                            $classe = $depense->classe;
                            $current = $get('compte') ?? '';
                            if (empty($current)) { $set('compte', $classe . '00000'); return; }
                            if (strlen($current) >= 1 && substr($current, 0, 1) !== $classe) {
                                $set('compte', $classe . substr($current, 1));
                            }
                        })
                        ->helperText(function (Forms\Get $get) {
                            $d = $get('depense_id') ? Depense::find($get('depense_id')) : null;
                            if (!$d) return 'Sélectionnez une dépense';
                            return $d->type === 'INVESTISSEMENT'
                                ? '📦 Investissement → code commence par 2'
                                : '⚙️ Fonctionnement → code commence par 6';
                        }),
                    Forms\Components\TextInput::make('compte')
                        ->label('🔢 Code comptable (6 chiffres)')
                        ->required()->minLength(6)->maxLength(6)->regex('/^\d{6}$/')
                        ->placeholder(function (Forms\Get $get) {
                            $d = $get('depense_id') ? Depense::find($get('depense_id')) : null;
                            return $d ? ($d->type === 'INVESTISSEMENT' ? '2XXXXX' : '6XXXXX') : 'Choisir dépense';
                        })
                        ->helperText(function (Forms\Get $get) {
                            $d = $get('depense_id') ? Depense::find($get('depense_id')) : null;
                            return $d ? "1er chiffre = {$d->classe} ({$d->type})" : '6 chiffres exactement';
                        })
                        ->rules([function (Forms\Get $get) {
                            return function (string $attr, $val, \Closure $fail) use ($get) {
                                if (!$val || !$get('depense_id')) return;
                                $d = Depense::find($get('depense_id'));
                                if ($d && substr($val, 0, 1) !== $d->classe) {
                                    $fail("Le code doit commencer par {$d->classe} pour {$d->type}.");
                                }
                            };
                        }]),
                    Forms\Components\TextInput::make('libelle')
                        ->label('📝 Libellé')->required()->maxLength(255)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('compte')->label('Code')->sortable()->weight('bold')
                ->copyable()->icon('heroicon-o-hashtag')->iconColor('primary')->searchable(),
            Tables\Columns\TextColumn::make('libelle')->label('Libellé')->searchable()->limit(40),
            Tables\Columns\TextColumn::make('depense.libelle')->label('Dépense')->icon('heroicon-o-tag')->iconColor('info'),
            Tables\Columns\BadgeColumn::make('depense.type')->label('Type')
                ->colors(['primary' => 'INVESTISSEMENT', 'success' => 'FONCTIONNEMENT']),
            Tables\Columns\TextColumn::make('dossiers_count')->label('Dossiers')->counts('dossiers')
                ->icon('heroicon-o-folder')->iconColor('warning'),
        ])
        ->actions([Tables\Actions\EditAction::make()->iconButton(), Tables\Actions\DeleteAction::make()->iconButton()]);
    }

    // ═══ BADGE : nombre total d'imputations ═══
    public static function getNavigationBadge(): ?string
    {
        return (string) Imputation::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'info';
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
