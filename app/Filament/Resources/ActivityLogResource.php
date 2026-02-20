<?php
namespace App\Filament\Resources;
use App\Filament\Resources\ActivityLogResource\Pages;
use App\Models\ActivityLog;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ActivityLogResource extends Resource {
    protected static ?string $model = ActivityLog::class;
    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?string $navigationLabel = "Journal d'activité";
    protected static ?int $navigationSort = 3;
    public static function canCreate(): bool { return false; }

    public static function table(Table $table): Table {
        return $table->columns([
            Tables\Columns\TextColumn::make('created_at')->label('Date')->dateTime('d/m/Y H:i:s')->sortable()
                ->icon('heroicon-o-clock')->iconColor('gray'),
            Tables\Columns\TextColumn::make('user.name')->label('Utilisateur')->searchable()
                ->icon('heroicon-o-user')->iconColor('primary'),
            Tables\Columns\BadgeColumn::make('action')->colors([
                'primary' => 'creation', 'warning' => 'modification', 'danger' => 'suppression',
                'info' => 'connexion', 'success' => fn ($s) => str_contains($s ?? '', 'cloud'),
            ]),
            Tables\Columns\TextColumn::make('description')->limit(60)->searchable(),
            Tables\Columns\BadgeColumn::make('resultat')->colors(['success' => 'succes', 'danger' => 'echec']),
            Tables\Columns\TextColumn::make('ip_address')->label('IP')->toggleable(isToggledHiddenByDefault: true),
        ])->defaultSort('created_at', 'desc')
          ->filters([
              Tables\Filters\SelectFilter::make('action')->options(['creation'=>'Création','modification'=>'Modification','suppression'=>'Suppression','cloud_upload'=>'Cloud']),
              Tables\Filters\SelectFilter::make('resultat')->options(['succes'=>'Succès','echec'=>'Échec']),
          ])->actions([Tables\Actions\ViewAction::make()->iconButton()]);
    }
    public static function getPages(): array { return ['index' => Pages\ListActivityLogs::route('/')]; }
}
