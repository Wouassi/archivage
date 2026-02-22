<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExerciceResource\Pages;
use App\Models\Exercice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ExerciceResource extends Resource
{
    protected static ?string $model = Exercice::class;
    protected static ?string $navigationIcon  = 'heroicon-o-calendar-days';
    protected static ?string $navigationGroup = 'Comptabilité';
    protected static ?int $navigationSort      = 2;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('📅 Exercice budgétaire')
                ->icon('heroicon-o-calendar-days')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('annee')->label('📆 Année')->numeric()->required()->default(date('Y')),
                    Forms\Components\Select::make('statut')->label('🔵 Statut')->options(Exercice::getStatuts())->default('actif')->required(),
                    Forms\Components\DatePicker::make('date_debut')->label('▶️ Début')->required(),
                    Forms\Components\DatePicker::make('date_fin')->label('⏹️ Fin')->required()->after('date_debut'),
                    Forms\Components\Textarea::make('description')->rows(3)->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            Tables\Columns\TextColumn::make('annee')->label('Année')->sortable()->weight('bold')
                ->icon('heroicon-o-calendar')->iconColor('primary'),
            Tables\Columns\BadgeColumn::make('statut')->colors(['success' => 'actif', 'gray' => 'clos']),
            Tables\Columns\TextColumn::make('date_debut')->label('Début')->date('d/m/Y')
                ->icon('heroicon-o-arrow-right')->iconColor('info'),
            Tables\Columns\TextColumn::make('date_fin')->label('Fin')->date('d/m/Y')
                ->icon('heroicon-o-arrow-left')->iconColor('warning'),
            Tables\Columns\TextColumn::make('dossiers_count')->label('Dossiers')->counts('dossiers')
                ->icon('heroicon-o-folder')->iconColor('success'),
        ])->defaultSort('annee', 'desc')
          ->actions([Tables\Actions\EditAction::make()->iconButton(), Tables\Actions\DeleteAction::make()->iconButton()]);
    }

    // ═══ BADGE : nombre d'exercices ═══
    public static function getNavigationBadge(): ?string
    {
        return (string) Exercice::count();
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'success';
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListExercices::route('/'),
            'create' => Pages\CreateExercice::route('/create'),
            'edit'   => Pages\EditExercice::route('/{record}/edit'),
        ];
    }
}
