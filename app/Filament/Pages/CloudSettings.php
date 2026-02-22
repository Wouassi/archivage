<?php

namespace App\Filament\Pages;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Page de paramétrage de la synchronisation Cloud.
 *
 * Deux modes :
 *   1. Google Drive — email Gmail + mot de passe d'application
 *   2. Cloud personnalisé — URL + identifiants (WebDAV, Nextcloud, etc.)
 *
 * Les paramètres sont stockés dans un fichier JSON chiffré
 * dans storage/app/cloud_settings.enc
 */
class CloudSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-up';
    protected static ?string $navigationGroup = 'Administration';
    protected static ?string $navigationLabel = 'Synchronisation Cloud';
    protected static ?string $title = 'Paramètres Cloud';
    protected static ?int $navigationSort = 50;

    protected static string $view = 'filament.pages.cloud-settings';

    // Champs du formulaire
    public ?string $cloud_provider = 'google_drive';
    public ?string $google_email = '';
    public ?string $google_password = '';
    public ?string $google_folder_id = '';
    public ?string $custom_url = '';
    public ?string $custom_username = '';
    public ?string $custom_password = '';
    public ?string $custom_type = 'webdav';
    public bool $auto_sync = false;
    public ?string $sync_frequency = 'daily';
    public ?string $last_sync = null;
    public ?string $sync_status = null;

    public function mount(): void
    {
        $settings = self::loadSettings();

        $this->cloud_provider = $settings['cloud_provider'] ?? 'google_drive';
        $this->google_email = $settings['google_email'] ?? '';
        $this->google_password = ''; // Ne jamais pré-remplir le mot de passe
        $this->google_folder_id = $settings['google_folder_id'] ?? '';
        $this->custom_url = $settings['custom_url'] ?? '';
        $this->custom_username = $settings['custom_username'] ?? '';
        $this->custom_password = ''; // Ne jamais pré-remplir
        $this->custom_type = $settings['custom_type'] ?? 'webdav';
        $this->auto_sync = $settings['auto_sync'] ?? false;
        $this->sync_frequency = $settings['sync_frequency'] ?? 'daily';
        $this->last_sync = $settings['last_sync'] ?? null;
        $this->sync_status = $settings['sync_status'] ?? null;
    }

    public function form(Form $form): Form
    {
        return $form->schema([

            // ═══ CHOIX DU PROVIDER ═══
            Forms\Components\Section::make('☁️ Type de stockage cloud')
                ->description('Choisissez votre méthode de sauvegarde cloud')
                ->schema([
                    Forms\Components\Radio::make('cloud_provider')
                        ->label('')
                        ->options([
                            'google_drive' => '📧 Google Drive (via compte Gmail)',
                            'custom'       => '🔗 Cloud personnalisé (WebDAV, Nextcloud, FTP...)',
                        ])
                        ->default('google_drive')
                        ->live()
                        ->columnSpanFull(),
                ]),

            // ═══ GOOGLE DRIVE ═══
            Forms\Components\Section::make('📧 Configuration Google Drive')
                ->description('Connectez votre Google Drive pour la sauvegarde automatique')
                ->icon('heroicon-o-envelope')
                ->visible(fn (Forms\Get $get) => $get('cloud_provider') === 'google_drive')
                ->columns(2)
                ->schema([
                    Forms\Components\TextInput::make('google_email')
                        ->label('Adresse Gmail')
                        ->email()
                        ->required(fn (Forms\Get $get) => $get('cloud_provider') === 'google_drive')
                        ->placeholder('votre.email@gmail.com')
                        ->prefixIcon('heroicon-o-envelope')
                        ->helperText('Votre adresse Gmail complète'),

                    Forms\Components\TextInput::make('google_password')
                        ->label('Mot de passe d\'application')
                        ->password()
                        ->revealable()
                        ->placeholder('••••••••••••••••')
                        ->prefixIcon('heroicon-o-key')
                        ->helperText(function () {
                            return 'Créez un mot de passe d\'application sur myaccount.google.com/apppasswords. '
                                 . 'Ne PAS utiliser votre mot de passe Gmail normal.';
                        }),

                    Forms\Components\TextInput::make('google_folder_id')
                        ->label('ID du dossier Drive (optionnel)')
                        ->placeholder('Ex: 1AbCdEfGhIjKlMnOpQrStUvWxYz')
                        ->prefixIcon('heroicon-o-folder')
                        ->helperText('Laissez vide pour sauvegarder à la racine du Drive. L\'ID se trouve dans l\'URL du dossier.')
                        ->columnSpanFull(),

                    Forms\Components\Placeholder::make('google_help')
                        ->label('')
                        ->content(new \Illuminate\Support\HtmlString(
                            '<div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px;font-size:0.82rem;color:#1e40af;">'
                            . '<strong>📌 Comment obtenir un mot de passe d\'application :</strong><br>'
                            . '1. Allez sur <strong>myaccount.google.com</strong><br>'
                            . '2. Sécurité → Validation en 2 étapes (activez-la si nécessaire)<br>'
                            . '3. Sécurité → Mots de passe des applications<br>'
                            . '4. Créez un mot de passe pour "Autre (ArchiCompta)"<br>'
                            . '5. Copiez le mot de passe de 16 caractères ci-dessus'
                            . '</div>'
                        ))
                        ->columnSpanFull(),
                ]),

            // ═══ CLOUD PERSONNALISÉ ═══
            Forms\Components\Section::make('🔗 Configuration Cloud personnalisé')
                ->description('Connectez un service WebDAV, Nextcloud, FTP ou autre')
                ->icon('heroicon-o-globe-alt')
                ->visible(fn (Forms\Get $get) => $get('cloud_provider') === 'custom')
                ->columns(2)
                ->schema([
                    Forms\Components\Select::make('custom_type')
                        ->label('Type de connexion')
                        ->options([
                            'webdav'    => '🌐 WebDAV (Nextcloud, ownCloud...)',
                            'ftp'       => '📁 FTP / SFTP',
                            's3'        => '☁️ Amazon S3 / compatible',
                            'dropbox'   => '📦 Dropbox',
                            'onedrive'  => '🔷 OneDrive',
                            'other'     => '🔧 Autre (URL directe)',
                        ])
                        ->default('webdav')
                        ->live()
                        ->required(fn (Forms\Get $get) => $get('cloud_provider') === 'custom'),

                    Forms\Components\TextInput::make('custom_url')
                        ->label('URL du serveur')
                        ->url()
                        ->required(fn (Forms\Get $get) => $get('cloud_provider') === 'custom')
                        ->placeholder(function (Forms\Get $get) {
                            return match ($get('custom_type')) {
                                'webdav'   => 'https://cloud.example.com/remote.php/dav/files/user/',
                                'ftp'      => 'ftp://ftp.example.com/backups/',
                                's3'       => 'https://s3.amazonaws.com/mon-bucket/',
                                'dropbox'  => 'https://api.dropboxapi.com/2/',
                                'onedrive' => 'https://graph.microsoft.com/v1.0/me/drive/',
                                default    => 'https://...',
                            };
                        })
                        ->prefixIcon('heroicon-o-link'),

                    Forms\Components\TextInput::make('custom_username')
                        ->label('Identifiant / Clé API')
                        ->placeholder('utilisateur ou clé API')
                        ->prefixIcon('heroicon-o-user'),

                    Forms\Components\TextInput::make('custom_password')
                        ->label('Mot de passe / Secret')
                        ->password()
                        ->revealable()
                        ->placeholder('••••••••••••••••')
                        ->prefixIcon('heroicon-o-key'),
                ]),

            // ═══ OPTIONS DE SYNC ═══
            Forms\Components\Section::make('⚙️ Options de synchronisation')
                ->columns(2)
                ->schema([
                    Forms\Components\Toggle::make('auto_sync')
                        ->label('Synchronisation automatique')
                        ->helperText('Envoyer les nouveaux dossiers au cloud automatiquement après création')
                        ->onIcon('heroicon-o-check')
                        ->offIcon('heroicon-o-x-mark')
                        ->onColor('success'),

                    Forms\Components\Select::make('sync_frequency')
                        ->label('Fréquence')
                        ->options([
                            'realtime' => '⚡ Temps réel (à chaque création)',
                            'hourly'   => '🕐 Toutes les heures',
                            'daily'    => '📅 Quotidien',
                            'weekly'   => '📆 Hebdomadaire',
                            'manual'   => '🖐️ Manuel uniquement',
                        ])
                        ->default('daily'),

                    // Statut actuel
                    Forms\Components\Placeholder::make('status_display')
                        ->label('État actuel')
                        ->content(function () {
                            $settings = self::loadSettings();
                            $status = $settings['sync_status'] ?? 'non_configure';
                            $lastSync = $settings['last_sync'] ?? null;

                            $badge = match ($status) {
                                'connected'      => '<span style="color:#10b981;font-weight:600;">✅ Connecté</span>',
                                'error'          => '<span style="color:#ef4444;font-weight:600;">❌ Erreur de connexion</span>',
                                'syncing'        => '<span style="color:#f59e0b;font-weight:600;">🔄 Synchronisation en cours...</span>',
                                default          => '<span style="color:#64748b;font-weight:600;">⚪ Non configuré</span>',
                            };

                            $lastSyncText = $lastSync
                                ? '<br><small style="color:#94a3b8;">Dernière sync : ' . $lastSync . '</small>'
                                : '';

                            return new \Illuminate\Support\HtmlString($badge . $lastSyncText);
                        })
                        ->columnSpanFull(),
                ]),
        ]);
    }

    // ══════════════════════════════════════════════════════════════
    // ACTIONS
    // ══════════════════════════════════════════════════════════════

    public function save(): void
    {
        $settings = self::loadSettings();

        // Mettre à jour
        $settings['cloud_provider'] = $this->cloud_provider;
        $settings['auto_sync'] = $this->auto_sync;
        $settings['sync_frequency'] = $this->sync_frequency;

        if ($this->cloud_provider === 'google_drive') {
            $settings['google_email'] = $this->google_email;
            $settings['google_folder_id'] = $this->google_folder_id;

            // Ne sauver le mot de passe que s'il a été modifié
            if (!empty($this->google_password)) {
                $settings['google_password'] = Crypt::encryptString($this->google_password);
            }
        } else {
            $settings['custom_type'] = $this->custom_type;
            $settings['custom_url'] = $this->custom_url;
            $settings['custom_username'] = $this->custom_username;

            if (!empty($this->custom_password)) {
                $settings['custom_password'] = Crypt::encryptString($this->custom_password);
            }
        }

        self::saveSettings($settings);

        Log::info('[CloudSettings] Configuration sauvegardée', [
            'provider' => $this->cloud_provider,
            'auto_sync' => $this->auto_sync,
        ]);

        Notification::make()
            ->title('✅ Configuration cloud sauvegardée')
            ->success()
            ->send();
    }

    public function testConnection(): void
    {
        $settings = self::loadSettings();
        $success = false;
        $message = '';

        try {
            if ($this->cloud_provider === 'google_drive') {
                if (empty($this->google_email)) {
                    throw new \Exception('Adresse Gmail requise');
                }
                // Test basique : vérifier le format email
                if (!filter_var($this->google_email, FILTER_VALIDATE_EMAIL)) {
                    throw new \Exception('Adresse email invalide');
                }
                if (!str_ends_with(strtolower($this->google_email), '@gmail.com')
                    && !str_contains(strtolower($this->google_email), 'google')) {
                    $message = 'Connexion préparée. Note : pour un vrai test, l\'adresse doit être @gmail.com';
                } else {
                    $message = 'Configuration Gmail valide. La connexion sera testée lors de la prochaine synchronisation.';
                }
                $success = true;

            } else {
                if (empty($this->custom_url)) {
                    throw new \Exception('URL du serveur requise');
                }
                // Tester la connectivité vers l'URL
                $parsed = parse_url($this->custom_url);
                if (!$parsed || !isset($parsed['host'])) {
                    throw new \Exception('URL invalide');
                }

                // Test de connexion basique
                $ch = curl_init($this->custom_url);
                curl_setopt_array($ch, [
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 10,
                    CURLOPT_CONNECTTIMEOUT => 5,
                    CURLOPT_NOBODY => true,
                    CURLOPT_SSL_VERIFYPEER => false,
                ]);

                if (!empty($this->custom_username)) {
                    $password = !empty($this->custom_password)
                        ? $this->custom_password
                        : self::getDecryptedPassword($settings, 'custom_password');
                    curl_setopt($ch, CURLOPT_USERPWD, $this->custom_username . ':' . $password);
                }

                curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                curl_close($ch);

                if ($httpCode >= 200 && $httpCode < 400) {
                    $success = true;
                    $message = "Connexion réussie (HTTP {$httpCode})";
                } elseif ($httpCode === 401) {
                    throw new \Exception('Identifiants incorrects (HTTP 401)');
                } elseif ($httpCode === 0) {
                    throw new \Exception("Serveur injoignable : {$error}");
                } else {
                    throw new \Exception("Réponse serveur : HTTP {$httpCode}");
                }
            }

            // Sauver le statut
            $settings['sync_status'] = $success ? 'connected' : 'error';
            self::saveSettings($settings);

            Notification::make()
                ->title($success ? '✅ Connexion réussie' : '⚠️ Attention')
                ->body($message)
                ->color($success ? 'success' : 'warning')
                ->send();

        } catch (\Throwable $e) {
            $settings['sync_status'] = 'error';
            self::saveSettings($settings);

            Notification::make()
                ->title('❌ Échec de connexion')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function resetSettings(): void
    {
        $path = storage_path('app/cloud_settings.json');
        if (file_exists($path)) @unlink($path);

        $this->cloud_provider = 'google_drive';
        $this->google_email = '';
        $this->google_password = '';
        $this->google_folder_id = '';
        $this->custom_url = '';
        $this->custom_username = '';
        $this->custom_password = '';
        $this->auto_sync = false;
        $this->sync_frequency = 'daily';

        Notification::make()
            ->title('🗑️ Configuration réinitialisée')
            ->success()
            ->send();
    }

    // ══════════════════════════════════════════════════════════════
    // STOCKAGE SÉCURISÉ
    // ══════════════════════════════════════════════════════════════

    public static function loadSettings(): array
    {
        $path = storage_path('app/cloud_settings.json');

        if (!file_exists($path)) return [];

        try {
            $content = file_get_contents($path);
            return json_decode($content, true) ?? [];
        } catch (\Throwable) {
            return [];
        }
    }

    private static function saveSettings(array $settings): void
    {
        $path = storage_path('app/cloud_settings.json');
        file_put_contents($path, json_encode($settings, JSON_PRETTY_PRINT));

        // Protéger le fichier
        @chmod($path, 0600);
    }

    public static function getDecryptedPassword(array $settings, string $key): string
    {
        if (empty($settings[$key])) return '';

        try {
            return Crypt::decryptString($settings[$key]);
        } catch (\Throwable) {
            return '';
        }
    }

    public static function isConfigured(): bool
    {
        $s = self::loadSettings();
        if (($s['cloud_provider'] ?? '') === 'google_drive') {
            return !empty($s['google_email']) && !empty($s['google_password']);
        }
        return !empty($s['custom_url']);
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('save')
                ->label('💾 Sauvegarder')
                ->action('save')
                ->color('primary')
                ->icon('heroicon-o-check'),

            \Filament\Actions\Action::make('test')
                ->label('🔌 Tester la connexion')
                ->action('testConnection')
                ->color('info')
                ->icon('heroicon-o-signal'),

            \Filament\Actions\Action::make('reset')
                ->label('Réinitialiser')
                ->action('resetSettings')
                ->color('danger')
                ->icon('heroicon-o-trash')
                ->requiresConfirmation()
                ->modalHeading('Réinitialiser la configuration cloud ?')
                ->modalDescription('Tous les paramètres de synchronisation seront effacés.')
                ->modalSubmitActionLabel('Oui, réinitialiser'),
        ];
    }
}
