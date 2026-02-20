<?php
namespace App\Filament\Resources;
use App\Filament\Resources\ParametreResource\Pages;
use App\Models\Parametre;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ParametreResource extends Resource {
    protected static ?string $model = Parametre::class;
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?string $navigationLabel = 'Paramètres';
    protected static ?int $navigationSort = 4;

    public static function form(Form $form): Form {
        return $form->schema([Forms\Components\Section::make('⚙️ Paramètre applicatif')
            ->icon('heroicon-o-cog-6-tooth')->columns(2)->schema([
            Forms\Components\TextInput::make('cle')->label('🔑 Clé')->required()->unique(ignoreRecord: true),
            Forms\Components\Select::make('type')->label('📊 Type')->options(Parametre::getTypes())->required()->default('string'),
            Forms\Components\Textarea::make('valeur')->label('📝 Valeur')->required()->columnSpanFull(),
            Forms\Components\Textarea::make('description')->rows(2)->columnSpanFull(),
        ])]);
    }

    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('cle')->label('Clé')->searchable()->weight('bold')
                ->icon('heroicon-o-key')->iconColor('primary'),
            Tables\Columns\TextColumn::make('valeur')->limit(50)->icon('heroicon-o-document-text')->iconColor('info'),
            Tables\Columns\BadgeColumn::make('type')->color('warning'),
            Tables\Columns\TextColumn::make('description')->limit(40)->color('gray'),
        ])->actions([Tables\Actions\EditAction::make()->iconButton(), Tables\Actions\DeleteAction::make()->iconButton()]);
    }
    public static function getPages(): array {
        return ['index' => Pages\ListParametres::route('/'), 'create' => Pages\CreateParametre::route('/create'), 'edit' => Pages\EditParametre::route('/{record}/edit')];
    }
}
