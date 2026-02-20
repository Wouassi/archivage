<?php
namespace App\Console\Commands;
use App\Services\CloudStorageService;
use Illuminate\Console\Command;

class CloudStatusCommand extends Command {
    protected $signature = 'cloud:status';
    protected $description = 'Affiche le statut de la synchronisation cloud';

    public function handle(CloudStorageService $service): int {
        $s = $service->getStatus();
        $this->table(['Indicateur', 'Valeur'], [
            ['Provider', $s['provider']], ['Activé', $s['enabled'] ? 'Oui' : 'Non'],
            ['Auto-sync', $s['auto_sync'] ? 'Oui' : 'Non'], ['Avec PDF', $s['total_avec_pdf']],
            ['Synchronisés', $s['synced']], ['En attente', $s['pending']], ['Taux', "{$s['taux']}%"],
        ]);
        return 0;
    }
}
