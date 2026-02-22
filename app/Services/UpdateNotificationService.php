<?php

namespace App\Services;

use App\Models\User;
use Filament\Notifications\Notification;
use Filament\Notifications\Actions\Action;
use Illuminate\Support\Facades\Log;

/**
 * Système de notification de mise à jour.
 *
 * Usage :
 *   // Après un déploiement / mise à jour :
 *   UpdateNotificationService::notifyUpdate('1.3.0', 'Fusion PDF améliorée, export backup ZIP');
 *
 *   // Ou via artisan :
 *   php artisan app:notify-update "1.3.0" "Fusion PDF améliorée, export backup"
 *
 *   // Vérifier la version actuelle :
 *   UpdateNotificationService::getCurrentVersion();
 */
class UpdateNotificationService
{
    /**
     * Fichier de version local
     */
    private static function versionFile(): string
    {
        return storage_path('app/app_version.json');
    }

    /**
     * Version actuelle
     */
    public static function getCurrentVersion(): string
    {
        $data = self::loadVersionData();
        return $data['version'] ?? '1.0.0';
    }

    /**
     * Notifier tous les utilisateurs d'une mise à jour.
     * Envoie une notification Filament en base de données.
     */
    public static function notifyUpdate(string $newVersion, string $changelog = '', ?string $title = null): void
    {
        $oldVersion = self::getCurrentVersion();

        // Sauver la nouvelle version
        self::saveVersionData([
            'version'    => $newVersion,
            'updated_at' => now()->toIso8601String(),
            'changelog'  => $changelog,
            'previous'   => $oldVersion,
        ]);

        // Titre de la notification
        $title = $title ?? "🚀 Mise à jour v{$newVersion}";

        // Construire le body
        $body = "ArchiCompta Pro a été mis à jour de v{$oldVersion} à v{$newVersion}.";
        if (!empty($changelog)) {
            $body .= "\n\n📋 Nouveautés :\n{$changelog}";
        }

        // Envoyer à tous les utilisateurs
        $users = User::all();

        foreach ($users as $user) {
            try {
                $notification = Notification::make()
                    ->title($title)
                    ->body($body)
                    ->icon('heroicon-o-arrow-up-circle')
                    ->iconColor('success')
                    ->info()
                    ->actions([
                        Action::make('voir')
                            ->label('✅ Compris')
                            ->markAsRead(),
                    ]);

                $notification->sendToDatabase($user);

            } catch (\Throwable $e) {
                Log::warning("[Update] Notification échouée pour {$user->email}: " . $e->getMessage());
            }
        }

        Log::info("[Update] v{$newVersion} — " . $users->count() . " utilisateur(s) notifié(s)");
    }

    /**
     * Notifier un seul utilisateur (ex: après sa première connexion post-update)
     */
    public static function notifyUserIfNeeded(User $user): void
    {
        $data = self::loadVersionData();
        $currentVersion = $data['version'] ?? '1.0.0';
        $userLastSeen = self::getUserLastSeenVersion($user);

        if (version_compare($currentVersion, $userLastSeen, '>')) {
            $changelog = $data['changelog'] ?? '';

            Notification::make()
                ->title("🆕 Nouveautés v{$currentVersion}")
                ->body(!empty($changelog) ? $changelog : "L'application a été mise à jour.")
                ->icon('heroicon-o-sparkles')
                ->iconColor('info')
                ->info()
                ->actions([
                    Action::make('ok')
                        ->label('✅ Compris')
                        ->markAsRead(),
                ])
                ->sendToDatabase($user);

            self::setUserLastSeenVersion($user, $currentVersion);
        }
    }

    /**
     * Obtenir la dernière version vue par un utilisateur
     */
    private static function getUserLastSeenVersion(User $user): string
    {
        $file = storage_path("app/user_versions/{$user->id}.txt");
        if (file_exists($file)) {
            return trim(file_get_contents($file)) ?: '0.0.0';
        }
        return '0.0.0';
    }

    /**
     * Marquer la version vue par un utilisateur
     */
    private static function setUserLastSeenVersion(User $user, string $version): void
    {
        $dir = storage_path('app/user_versions');
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        file_put_contents("{$dir}/{$user->id}.txt", $version);
    }

    /**
     * Charger les données de version
     */
    private static function loadVersionData(): array
    {
        $file = self::versionFile();
        if (!file_exists($file)) return ['version' => '1.0.0'];
        try {
            return json_decode(file_get_contents($file), true) ?? ['version' => '1.0.0'];
        } catch (\Throwable) {
            return ['version' => '1.0.0'];
        }
    }

    /**
     * Sauver les données de version
     */
    private static function saveVersionData(array $data): void
    {
        $file = self::versionFile();
        file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT));
    }
}
