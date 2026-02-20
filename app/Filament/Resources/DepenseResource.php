<?php
namespace App\Filament\Resources;
use App\Filament\Resources\DepenseResource\Pages;
use App\Models\Depense;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DepenseResource extends Resource {
    protected static ?string $model = Depense::class;
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'Comptabilité';
    protected static ?string $navigationLabel = 'Dépenses';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form {
        return $form->schema([
            Forms\Components\Section::make('💰 Catégorie de dépense')
                ->icon('heroicon-o-banknotes')
                ->description('Investissement = Classe 2 | Fonctionnement = Classe 6')
                ->columns(2)->schema([
                Forms\Components\TextInput::make('libelle')->label('📝 Libellé')->required()->maxLength(255),
                Forms\Components\Select::make('type')->label('🏷️ Type')->options(Depense::getTypes())->required()->live()
                    ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $state ? $set('classe', Depense::getClasseForType($state)) : null),
                Forms\Components\TextInput::make('classe')->label('📊 Classe OHADA')->disabled()->dehydrated()
                    ->helperText('Calculée automatiquement selon le type'),
                Forms\Components\Textarea::make('description')->label('📋 Description')->rows(3)->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('libelle')->label('Libellé')->searchable()->sortable()->weight('bold')
                ->icon('heroicon-o-tag')->iconColor('primary'),
            Tables\Columns\BadgeColumn::make('type')->colors(['primary' => 'INVESTISSEMENT', 'success' => 'FONCTIONNEMENT']),
            Tables\Columns\TextColumn::make('classe')->label('Classe')->badge()->color('info'),
            Tables\Columns\TextColumn::make('imputations_count')->label('Imputations')->counts('imputations')
                ->icon('heroicon-o-calculator')->iconColor('warning'),
            Tables\Columns\TextColumn::make('dossiers_count')->label('Dossiers')->counts('dossiers')
                ->icon('heroicon-o-folder')->iconColor('success'),
        ])->filters([Tables\Filters\SelectFilter::make('type')->options(Depense::getTypes())])
          ->actions([Tables\Actions\EditAction::make()->iconButton(), Tables\Actions\DeleteAction::make()->iconButton()]);
    }

    public static function getPages(): array {
        return ['index' => Pages\ListDepenses::route('/'), 'create' => Pages\CreateDepense::route('/create'), 'edit' => Pages\EditDepense::route('/{record}/edit')];
    }
}
