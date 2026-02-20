<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\Dossier;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CloudStorageService
{
    public function getProvider(): string { return config('cloud.provider', 'disabled'); }
    public function isEnabled(): bool { return !in_array($this->getProvider(), ['disabled', 'local']); }

    public function upload(Dossier $dossier): bool
    {
        if (!$this->isEnabled() || !$dossier->fichier_path) return false;
        try {
            $localPath = Storage::disk('public')->path($dossier->fichier_path);
            if (!file_exists($localPath)) return false;

            $cloudPath = $this->buildCloudPath($dossier);
            $this->getCloudDisk()->put($cloudPath, file_get_contents($localPath));

            $dossier->update(['cloud_path' => $cloudPath, 'cloud_synced_at' => now()]);
            ActivityLog::logActivity('cloud_upload', "Document {$dossier->ordre_paiement} synchronisé vers {$this->getProvider()}", $dossier, null, ['cloud_path' => $cloudPath]);
            return true;
        } catch (\Exception $e) {
            Log::error("CloudStorage upload: {$e->getMessage()}");
            ActivityLog::logActivity('cloud_upload', "Échec sync {$dossier->ordre_paiement}: {$e->getMessage()}", $dossier, null, null, 'echec');
            return false;
        }
    }

    public function download(Dossier $dossier): ?string
    {
        if (!$dossier->cloud_path) return null;
        try {
            $disk = $this->getCloudDisk();
            if (!$disk->exists($dossier->cloud_path)) return null;
            $localPath = $dossier->fichier_path ?? "DOSSIER_ARCHIVE/downloads/" . basename($dossier->cloud_path);
            Storage::disk('public')->put($localPath, $disk->get($dossier->cloud_path));
            return $localPath;
        } catch (\Exception $e) { Log::error("CloudStorage download: {$e->getMessage()}"); return null; }
    }

    public function delete(Dossier $dossier): bool
    {
        if (!$dossier->cloud_path) return false;
        try { $this->getCloudDisk()->delete($dossier->cloud_path); return true; }
        catch (\Exception $e) { return false; }
    }

    public function exists(Dossier $dossier): bool
    {
        if (!$dossier->cloud_path) return false;
        try { return $this->getCloudDisk()->exists($dossier->cloud_path); }
        catch (\Exception $e) { return false; }
    }

    public function syncAll(?int $exerciceId = null, bool $force = false): array
    {
        $query = Dossier::avecPdf();
        if ($exerciceId) $query->parExercice($exerciceId);
        if (!$force) $query->cloudPending();

        $dossiers = $query->get();
        $results = ['success' => 0, 'failed' => 0, 'total' => $dossiers->count()];
        foreach ($dossiers as $d) { $this->upload($d) ? $results['success']++ : $results['failed']++; }
        return $results;
    }

    public function getStatus(): array
    {
        $totalPdf = Dossier::avecPdf()->count();
        $synced = Dossier::cloudSynced()->count();
        return [
            'provider' => $this->getProvider(), 'enabled' => $this->isEnabled(),
            'auto_sync' => config('cloud.auto_sync', false),
            'total_avec_pdf' => $totalPdf, 'synced' => $synced,
            'pending' => Dossier::cloudPending()->count(),
            'taux' => $totalPdf > 0 ? round(($synced / $totalPdf) * 100, 1) : 0,
        ];
    }

    public function testConnection(): bool
    {
        if (!$this->isEnabled()) return false;
        try {
            $disk = $this->getCloudDisk();
            $test = config('cloud.root_folder', '') . '/.connection_test';
            $disk->put($test, 'test'); $disk->delete($test);
            return true;
        } catch (\Exception $e) { return false; }
    }

    private function getCloudDisk()
    {
        return match ($this->getProvider()) {
            'google_drive' => Storage::disk('google'), 'dropbox' => Storage::disk('dropbox'),
            's3' => Storage::disk('s3'), default => Storage::disk('public'),
        };
    }

    private function buildCloudPath(Dossier $dossier): string
    {
        $root = config('cloud.root_folder', 'ArchivageComptable');
        if (config('cloud.keep_structure', true) && $dossier->fichier_path) {
            return "{$root}/" . str_replace('DOSSIER_ARCHIVE/', '', $dossier->fichier_path);
        }
        return "{$root}/" . basename($dossier->fichier_path ?? 'document.pdf');
    }
}
