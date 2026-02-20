<?php
namespace App\Jobs;
use App\Models\Dossier;
use App\Services\CloudStorageService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncToCloudJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(public Dossier $dossier) { $this->onQueue('cloud'); }

    public function handle(CloudStorageService $service): void { $service->upload($this->dossier); }

    public function failed(\Throwable $e): void {
        \App\Models\ActivityLog::logActivity('cloud_sync_failed', "Échec définitif sync {$this->dossier->ordre_paiement}: {$e->getMessage()}", $this->dossier, null, null, 'echec');
    }
}
