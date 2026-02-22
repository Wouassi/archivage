<?php

namespace App\Console\Commands;

use App\Services\UpdateNotificationService;
use Illuminate\Console\Command;

/**
 * Commande artisan pour notifier les utilisateurs d'une mise à jour.
 *
 * Usage :
 *   php artisan app:notify-update "1.3.0" "Fusion PDF, export backup, sync cloud"
 *   php artisan app:notify-update "1.3.1"  (sans changelog)
 *   php artisan app:version                 (afficher la version actuelle)
 */
class NotifyUpdateCommand extends Command
{
    protected $signature = 'app:notify-update
                            {version : Numéro de version (ex: 1.3.0)}
                            {changelog? : Description des nouveautés}';

    protected $description = 'Notifier tous les utilisateurs d\'une mise à jour de l\'application';

    public function handle(): int
    {
        $version   = $this->argument('version');
        $changelog = $this->argument('changelog') ?? '';

        $old = UpdateNotificationService::getCurrentVersion();

        $this->info("Version actuelle : v{$old}");
        $this->info("Nouvelle version : v{$version}");

        if (!empty($changelog)) {
            $this->info("Changelog : {$changelog}");
        }

        if (!$this->confirm("Envoyer la notification de mise à jour à tous les utilisateurs ?")) {
            $this->warn('Annulé.');
            return 0;
        }

        UpdateNotificationService::notifyUpdate($version, $changelog);

        $this->info("✅ Notification v{$version} envoyée à tous les utilisateurs !");

        return 0;
    }
}
