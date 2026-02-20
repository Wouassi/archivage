<?php
namespace App\Filament\Resources;
use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UserResource extends Resource {
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?string $navigationLabel = 'Utilisateurs';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form {
        return $form->schema([Forms\Components\Section::make('👤 Utilisateur')
            ->icon('heroicon-o-user-circle')
            ->columns(2)->schema([
            Forms\Components\TextInput::make('name')->label('📛 Nom complet')->required(),
            Forms\Components\TextInput::make('email')->label('📧 Email')->email()->required()->unique(ignoreRecord: true),
            Forms\Components\TextInput::make('password')->label('🔑 Mot de passe')->password()
                ->dehydrateStateUsing(fn ($state) => $state ? bcrypt($state) : null)
                ->dehydrated(fn ($state) => filled($state))->required(fn (string $op) => $op === 'create'),
            Forms\Components\Toggle::make('active')->label('✅ Actif')->default(true),
            Forms\Components\Select::make('roles')->label('🛡️ Rôle')->relationship('roles', 'name')->preload()->required()->columnSpanFull(),
        ])]);
    }

    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('name')->label('Nom')->searchable()->weight('bold')
                ->icon('heroicon-o-user')->iconColor('primary'),
            Tables\Columns\TextColumn::make('email')->searchable()->icon('heroicon-o-envelope')->iconColor('info'),
            Tables\Columns\TextColumn::make('roles.name')->label('Rôle')->badge()->color('success'),
            Tables\Columns\IconColumn::make('active')->label('Actif')->boolean()->trueColor('success')->falseColor('danger'),
            Tables\Columns\TextColumn::make('created_at')->label('Créé le')->dateTime('d/m/Y')
                ->icon('heroicon-o-clock')->iconColor('gray'),
        ])->actions([Tables\Actions\EditAction::make()->iconButton(), Tables\Actions\DeleteAction::make()->iconButton()]);
    }

    public static function getPages(): array {
        return ['index' => Pages\ListUsers::route('/'), 'create' => Pages\CreateUser::route('/create'), 'edit' => Pages\EditUser::route('/{record}/edit')];
    }
}
