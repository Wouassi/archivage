<?php
namespace App\Console\Commands;
use App\Services\CloudStorageService;
use Illuminate\Console\Command;

class CloudSyncCommand extends Command {
    protected $signature = 'cloud:sync {--exercice= : ID exercice} {--force : Re-sync tout}';
    protected $description = 'Synchronise les dossiers PDF vers le cloud';

    public function handle(CloudStorageService $service): int {
        if (!$service->isEnabled()) { $this->error('Cloud désactivé.'); return 1; }
        $this->info("Provider: {$service->getProvider()}");
        $results = $service->syncAll($this->option('exercice') ? (int)$this->option('exercice') : null, $this->option('force'));
        $this->info("Total: {$results['total']} | Succès: {$results['success']} | Échecs: {$results['failed']}");
        return $results['failed'] > 0 ? 1 : 0;
    }
}
