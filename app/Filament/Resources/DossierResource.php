<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DossierResource\Pages;
use App\Models\Dossier;
use App\Models\Exercice;
use App\Models\Imputation;
use App\Services\WorkContextService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DossierResource extends Resource
{
    protected static ?string $model = Dossier::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder-open';
    protected static ?string $navigationGroup = 'Documents';
    protected static ?string $navigationLabel = 'Dossiers Comptables';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('📋 Informations du dossier')
                ->icon('heroicon-o-document-text')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('depense_id')
                        ->label('🏷️ Catégorie de dépense')
                        ->relationship('depense', 'libelle')
                        ->searchable()->preload()->required()->live()
                        ->default(fn () => WorkContextService::getDepenseId())
                        ->afterStateUpdated(fn (Forms\Set $set) => $set('imputation_id', null)),
                    Forms\Components\Select::make('imputation_id')
                        ->label('🔢 Imputation (6 chiffres)')
                        ->options(function (Forms\Get $get) {
                            $id = $get('depense_id');
                            return $id ? Imputation::where('depense_id', $id)->get()->pluck('formatted_compte', 'id') : [];
                        })
                        ->searchable()->required(),
                    Forms\Components\Select::make('exercice_id')
                        ->label('📅 Exercice')
                        ->relationship('exercice', 'annee')
                        ->default(fn () => WorkContextService::getExerciceId() ?? Exercice::getActif()?->id)
                        ->searchable()->preload()->required(),
                    Forms\Components\TextInput::make('ordre_paiement')
                        ->label('📄 N° Ordre de paiement')
                        ->placeholder('OP-2026-INV-001')
                        ->unique(ignoreRecord: true)->required()->maxLength(50),
                    Forms\Components\DatePicker::make('date_dossier')
                        ->label('📆 Date du dossier')
                        ->default(now())->required(),
                    Forms\Components\TextInput::make('beneficiaire')
                        ->label('👤 Bénéficiaire')
                        ->required()->maxLength(255),
                    Forms\Components\TextInput::make('montant_engage')
                        ->label('💰 Montant engagé (FCFA)')
                        ->numeric()->required()->prefix('FCFA')->inputMode('decimal'),
                    Forms\Components\Textarea::make('observations')
                        ->label('📝 Observations')
                        ->rows(3)->columnSpanFull(),
                ]),

            // ═══ SECTION SCANNER LARASCAN (Asprise Scanner.js) ═══
            Forms\Components\Section::make('🖨️ Numérisation directe (Larascan)')
                ->icon('heroicon-o-camera')
                ->description('Scannez directement depuis un scanner connecté. Le chargeur ADF numérise toutes les pages automatiquement.')
                ->schema([
                    Forms\Components\ViewField::make('larascan_scanner')
                        ->view('filament.forms.larascan-section')
                        ->columnSpanFull(),
                    Forms\Components\Hidden::make('fichiers_pdf_paths'),
                ]),

            // ═══ SECTION UPLOAD CLASSIQUE ═══
            Forms\Components\Section::make('📎 Ou charger des fichiers PDF')
                ->icon('heroicon-o-paper-clip')
                ->description('Chargez jusqu\'à 500 fichiers PDF (100 Mo max chacun). Tous seront fusionnés avec les pages scannées.')
                ->collapsed()
                ->schema([
                    Forms\Components\FileUpload::make('fichiers_upload')
                        ->label('Fichiers PDF')
                        ->multiple()
                        ->acceptedFileTypes(['application/pdf'])
                        ->maxSize(102400)
                        ->maxFiles(500)
                        ->directory('uploads-tmp')
                        ->disk('public')
                        ->columnSpanFull()
                        ->helperText('Maximum 500 fichiers, 100 Mo chacun.'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('ordre_paiement')
                    ->label('N° OP')
                    ->searchable()->sortable()->copyable()
                    ->weight('bold')
                    ->icon('heroicon-o-document-text')
                    ->iconColor('primary'),
                Tables\Columns\BadgeColumn::make('depense.type')
                    ->label('Type')
                    ->colors(['primary' => 'INVESTISSEMENT', 'success' => 'FONCTIONNEMENT']),
                Tables\Columns\BadgeColumn::make('exercice.annee')
                    ->label('Exercice')
                    ->color('info'),
                Tables\Columns\TextColumn::make('imputation.formatted_compte')
                    ->label('Imputation')
                    ->searchable(query: fn (Builder $q, string $s) => $q->whereHas('imputation', fn ($q2) => $q2->where('compte', 'like', "%{$s}%")->orWhere('libelle', 'like', "%{$s}%")))
                    ->icon('heroicon-o-calculator')
                    ->iconColor('warning')
                    ->limit(30)
                    ->toggleable(),
                Tables\Columns\TextColumn::make('beneficiaire')
                    ->label('Bénéficiaire')
                    ->searchable()->limit(25)
                    ->icon('heroicon-o-user')
                    ->iconColor('gray'),
                Tables\Columns\TextColumn::make('date_dossier')
                    ->label('Date')
                    ->date('d/m/Y')
                    ->sortable()
                    ->icon('heroicon-o-calendar')
                    ->iconColor('info'),
                Tables\Columns\TextColumn::make('montant_engage')
                    ->label('Montant')
                    ->numeric(thousandsSeparator: ' ')
                    ->suffix(' FCFA')
                    ->sortable()->alignEnd()
                    ->weight('bold')
                    ->color('primary'),
                Tables\Columns\IconColumn::make('fichier_path')
                    ->label('PDF')
                    ->boolean()
                    ->trueIcon('heroicon-o-document-check')
                    ->falseIcon('heroicon-o-document-minus')
                    ->trueColor('success')
                    ->falseColor('danger'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('depense_id')->label('Dépense')->relationship('depense', 'libelle'),
                Tables\Filters\SelectFilter::make('exercice_id')->label('Exercice')->relationship('exercice', 'annee'),
                Tables\Filters\TernaryFilter::make('fichier_path')
                    ->label('PDF')
                    ->trueLabel('Avec PDF')->falseLabel('Sans PDF')
                    ->queries(
                        true: fn (Builder $q) => $q->whereNotNull('fichier_path'),
                        false: fn (Builder $q) => $q->whereNull('fichier_path'),
                    ),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()->iconButton(),
                Tables\Actions\EditAction::make()->iconButton(),
                Tables\Actions\DeleteAction::make()->iconButton(),
            ])
            ->recordClasses(fn (Dossier $r) => match (true) {
                !$r->fichier_path => 'border-l-nopdf',
                $r->depense?->type === 'INVESTISSEMENT' => 'border-l-investissement',
                $r->depense?->type === 'FONCTIONNEMENT' => 'border-l-fonctionnement',
                default => '',
            });
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Infolists\Components\Section::make('📋 Informations')
                ->icon('heroicon-o-document-text')
                ->columns(3)
                ->schema([
                    Infolists\Components\TextEntry::make('ordre_paiement')->label('N° OP')->weight('bold')->copyable(),
                    Infolists\Components\TextEntry::make('depense.type')->label('Type')->badge(),
                    Infolists\Components\TextEntry::make('depense.libelle')->label('Catégorie'),
                    Infolists\Components\TextEntry::make('exercice.annee')->label('Exercice')->badge()->color('info'),
                    Infolists\Components\TextEntry::make('date_dossier')->label('Date')->date('d/m/Y'),
                    Infolists\Components\TextEntry::make('beneficiaire')->label('Bénéficiaire'),
                    Infolists\Components\TextEntry::make('imputation.formatted_compte')->label('Imputation'),
                    Infolists\Components\TextEntry::make('montant_formate')->label('Montant')->weight('bold')->color('primary')->size('lg'),
                    Infolists\Components\TextEntry::make('observations')->label('Observations')->columnSpanFull(),
                ]),
            Infolists\Components\Section::make('📎 Document PDF')
                ->icon('heroicon-o-paper-clip')
                ->schema([
                    Infolists\Components\ViewEntry::make('pdf_viewer')
                        ->label('')
                        ->view('filament.resources.dossier-resource.pages.pdf-viewer')
                        ->columnSpanFull()
                        ->visible(fn ($record) => $record->pdf_exists),
                ]),
        ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListDossiers::route('/'),
            'create' => Pages\CreateDossier::route('/create'),
            'edit'   => Pages\EditDossier::route('/{record}/edit'),
            'view'   => Pages\ViewDossier::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $c = Dossier::sansPdf()->count();
        return $c > 0 ? (string) $c : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return 'danger';
    }
}
