<?php
namespace App\Listeners;
use App\Events\DocumentArchived;
use App\Jobs\SyncToCloudJob;

class SyncDocumentToCloud {
    public function handle(DocumentArchived $event): void {
        if (config('cloud.auto_sync', false)) {
            SyncToCloudJob::dispatch($event->dossier)->onQueue('cloud');
        }
    }
}
