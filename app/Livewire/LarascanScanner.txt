<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use App\Services\ScannerLarascanService;

/**
 * Composant Livewire : Scanner Larascan
 *
 * Workflow :
 *   1. L'utilisateur scanne des pages (une à une OU en lot ADF) → elles s'accumulent
 *   2. L'utilisateur peut aussi uploader des PDF/images → ils s'ajoutent à la liste
 *   3. Rien n'est créé en BDD tant que l'utilisateur ne clique pas sur "Créer"
 *   4. Au clic sur "Créer", CreateDossier récupère tous les chemins et fusionne en un seul PDF
 */
class LarascanScanner extends Component
{
    use WithFileUploads;

    public $maxDocuments = 500;
    public $availableScanners = [];
    public $selectedScanner = null;
    public $selectedScannerType = 'wia';
    public $scannerStatus = '';
    public $resolution = 75;
    public $colorMode = 'Gray';
    public $scannedDocuments = [];
    public $uploadedDocuments = [];
    public $uploadFiles = [];
    public $message = '';
    public $isDetecting = false;
    public $isScanning = false;
    public $isBatchScanning = false;
    public $batchProgress = 0;

    public function mount()
    {
        Log::info("🔵 LarascanScanner monté");
        $this->detectScanners();
    }

    // ══════════════════════════════════════════════════════════════
    // DÉTECTION DES SCANNERS
    // ══════════════════════════════════════════════════════════════

    public function detectScanners(): void
    {
        $this->isDetecting = true;
        $this->message = '';

        try {
            $service = app(ScannerLarascanService::class);
            $this->availableScanners = $service->detectScanners();

            if (count($this->availableScanners) > 0) {
                $this->selectedScanner = $this->availableScanners[0]['id'];
                $this->selectedScannerType = $this->availableScanners[0]['driver']
                    ?? $this->availableScanners[0]['type']
                    ?? 'wia';
                $this->scannerStatus = "✅ " . count($this->availableScanners) . " scanner(s) détecté(s)";
                $this->message = "✅ " . count($this->availableScanners) . " scanner(s) trouvé(s)";
            } else {
                $this->scannerStatus = "⚠️ Aucun scanner détecté";
                $this->message = "⚠️ Aucun scanner détecté — branchez un scanner puis cliquez Actualiser";
            }
        } catch (\Exception $e) {
            $this->scannerStatus = "❌ Erreur: " . $e->getMessage();
            $this->message = "❌ " . $e->getMessage();
            Log::error("❌ Erreur détection", ['error' => $e->getMessage()]);
        } finally {
            $this->isDetecting = false;
        }
    }

    public function refreshScanners(): void
    {
        $this->message = "🔄 Recherche de scanners...";
        $this->availableScanners = [];
        $this->selectedScanner = null;
        $this->detectScanners();
    }

    // ══════════════════════════════════════════════════════════════
    // SCAN SIMPLE (1 page par clic)
    // ══════════════════════════════════════════════════════════════

    /**
     * Numérise UNE seule page et l'ajoute à la liste.
     * NE crée PAS de dossier — c'est CreateDossier qui le fera au "Créer".
     */
    public function scanDocument()
    {
        $this->message = '';
        $this->isScanning = true;

        try {
            if (!$this->selectedScanner) {
                $this->message = "⚠️ Aucun scanner sélectionné";
                return;
            }

            $result = $this->executeSingleScan();

            if ($result) {
                $total = count($this->getAllDocuments());
                $this->message = "✅ Page numérisée ({$result['size_formatted']}) — {$total} document(s) au total";
            }
        } catch (\Exception $e) {
            $this->message = "❌ " . $e->getMessage();
            Log::error("❌ SCAN EXCEPTION", ['error' => $e->getMessage()]);
        } finally {
            $this->isScanning = false;
        }
    }

    // ══════════════════════════════════════════════════════════════
    // SCAN MULTIPLE / ADF (bac entier)
    // ══════════════════════════════════════════════════════════════

    /**
     * Scanne en boucle jusqu'à épuisement du bac ADF.
     * Chaque page est ajoutée à la liste sans créer de dossier.
     * S'arrête quand le bac est vide ou après 2 erreurs consécutives.
     */
    public function scanBatchAdf()
    {
        $this->message = '';
        $this->isBatchScanning = true;
        $this->batchProgress = 0;

        try {
            if (!$this->selectedScanner) {
                $this->message = "⚠️ Aucun scanner sélectionné";
                return;
            }

            Log::info("📚 BATCH ADF START");

            $service = app(ScannerLarascanService::class);
            $maxPages = $this->maxDocuments - count($this->getAllDocuments());

            // ── Méthode 1 : ADF natif (si le service le supporte) ──
            if (method_exists($service, 'scanBatchAdf')) {
                $results = $service->scanBatchAdf([
                    'scanner_id'   => $this->selectedScanner,
                    'scanner_type' => $this->selectedScannerType,
                    'resolution'   => (int) $this->resolution,
                    'mode'         => $this->colorMode,
                    'max_pages'    => $maxPages,
                ]);

                foreach ($results as $result) {
                    if ($result['success'] ?? false) {
                        $this->addScannedDocument($result);
                        $this->batchProgress++;
                    }
                }

                if ($this->batchProgress > 0) {
                    $this->message = "✅ Scan ADF terminé : {$this->batchProgress} page(s) numérisée(s)";
                } else {
                    $this->message = "⚠️ Aucune page — vérifiez le bac ADF";
                }

                return;
            }

            // ── Méthode 2 : Fallback page par page ──
            $consecutiveErrors = 0;

            for ($i = 0; $i < $maxPages; $i++) {
                // Vérifier si l'utilisateur a demandé l'arrêt
                if (!$this->isBatchScanning) {
                    break;
                }

                try {
                    $result = $this->executeSingleScan();

                    if ($result) {
                        $this->batchProgress++;
                        $consecutiveErrors = 0;
                        $this->message = "🔄 Scan ADF en cours : {$this->batchProgress} page(s)...";
                    } else {
                        $consecutiveErrors++;
                    }
                } catch (\Exception $e) {
                    $consecutiveErrors++;
                    $errorMsg = strtolower($e->getMessage());

                    // Mots-clés indiquant un bac vide
                    $bacVideKeywords = [
                        'no documents', 'paper empty', 'out of paper',
                        'no more pages', 'feeder empty', 'adf empty',
                        'document feeder', 'bac vide', 'wia_error_paper_empty',
                        'no paper', 'empty', 'aucun document',
                    ];

                    foreach ($bacVideKeywords as $kw) {
                        if (str_contains($errorMsg, $kw)) {
                            Log::info("📭 Bac ADF vide — arrêt", ['pages' => $this->batchProgress]);
                            $consecutiveErrors = 99; // Forcer l'arrêt
                            break;
                        }
                    }
                }

                if ($consecutiveErrors >= 2) {
                    Log::info("⛔ Arrêt scan ADF", ['pages' => $this->batchProgress, 'errors' => $consecutiveErrors]);
                    break;
                }
            }

            if ($this->batchProgress > 0) {
                $this->message = "✅ Scan ADF terminé : {$this->batchProgress} page(s) numérisée(s)";
            } else {
                $this->message = "⚠️ Aucune page scannée — vérifiez que des documents sont dans le bac";
            }

        } catch (\Exception $e) {
            $this->message = "❌ " . $e->getMessage();
            Log::error("❌ BATCH ADF EXCEPTION", ['error' => $e->getMessage()]);
        } finally {
            $this->isBatchScanning = false;
            $this->savePathsToSession();
            $this->dispatch('documents-updated');
        }
    }

    /**
     * Arrête le scan ADF en cours.
     */
    public function stopBatchScan(): void
    {
        $this->isBatchScanning = false;
        $this->message = "⏹️ Scan arrêté — {$this->batchProgress} page(s) conservée(s)";
        $this->savePathsToSession();
        $this->dispatch('documents-updated');
    }

    // ══════════════════════════════════════════════════════════════
    // UPLOAD MANUEL
    // ══════════════════════════════════════════════════════════════

    public function updatedUploadFiles()
    {
        $this->message = '';

        try {
            if (count($this->uploadFiles) === 0) return;

            $processed = 0;
            $errors = 0;

            foreach ($this->uploadFiles as $file) {
                try {
                    $ext = strtolower($file->getClientOriginalExtension());
                    if (!in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])) {
                        $errors++;
                        continue;
                    }

                    if ($file->getSize() > 400 * 1024 * 1024) {
                        $this->message = "⚠️ Trop volumineux (max 400 Mo) : " . $file->getClientOriginalName();
                        $errors++;
                        continue;
                    }

                    $filename = 'upload_' . uniqid() . '_' . time() . '.' . $ext;
                    $path = $file->storeAs('scanner/temp', $filename, 'public');

                    $this->uploadedDocuments[] = [
                        'id'         => uniqid('upload_'),
                        'name'       => $file->getClientOriginalName(),
                        'path'       => $path,
                        'size'       => $file->getSize(),
                        'type'       => 'uploaded',
                        'created_at' => now()->format('H:i:s'),
                    ];
                    $processed++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("❌ FILE ERROR", ['error' => $e->getMessage()]);
                }
            }

            if ($processed > 0) {
                $this->message = "✅ {$processed} fichier(s) ajouté(s)";
                if ($errors > 0) $this->message .= " ({$errors} ignoré(s))";
                $this->savePathsToSession();
                $this->dispatch('documents-updated');
            } elseif ($errors > 0 && empty($this->message)) {
                $this->message = "⚠️ Aucun fichier valide (PDF, JPG, PNG — max 400 Mo)";
            }
        } catch (\Exception $e) {
            $this->message = "❌ " . $e->getMessage();
        } finally {
            $this->uploadFiles = [];
        }
    }

    // ══════════════════════════════════════════════════════════════
    // GESTION DES DOCUMENTS
    // ══════════════════════════════════════════════════════════════

    public function removeDocument($docId)
    {
        foreach (['scannedDocuments', 'uploadedDocuments'] as $list) {
            foreach ($this->{$list} as $i => $doc) {
                if ($doc['id'] === $docId) {
                    if (Storage::disk('public')->exists($doc['path'])) {
                        Storage::disk('public')->delete($doc['path']);
                    }
                    unset($this->{$list}[$i]);
                    $this->{$list} = array_values($this->{$list});
                    $this->savePathsToSession();
                    $this->dispatch('documents-updated');
                    return;
                }
            }
        }
    }

    public function clearAll()
    {
        foreach ($this->getAllDocuments() as $doc) {
            if (Storage::disk('public')->exists($doc['path'])) {
                Storage::disk('public')->delete($doc['path']);
            }
        }

        $this->scannedDocuments = [];
        $this->uploadedDocuments = [];
        $this->message = "🗑️ Tous les documents supprimés";
        $this->savePathsToSession();
        $this->dispatch('documents-updated');
    }

    public function getAllDocuments(): array
    {
        return array_merge($this->scannedDocuments, $this->uploadedDocuments);
    }

    public function getAllPaths(): array
    {
        return array_map(fn($d) => $d['path'], $this->getAllDocuments());
    }

    public function getTotalSize(): int
    {
        return array_reduce($this->getAllDocuments(), fn($sum, $d) => $sum + ($d['size'] ?? 0), 0);
    }

    // ══════════════════════════════════════════════════════════════
    // MÉTHODES INTERNES
    // ══════════════════════════════════════════════════════════════

    /**
     * Exécute un scan unique et ajoute le résultat à la liste.
     * NE crée AUCUN dossier. Retourne le résultat ou null.
     */
    private function executeSingleScan(): ?array
    {
        $service = app(ScannerLarascanService::class);

        $options = [
            'scanner_id'   => $this->selectedScanner,
            'scanner_type' => $this->selectedScannerType,
            'resolution'   => (int) $this->resolution,
            'mode'         => $this->colorMode,
            'color_mode'   => $this->colorMode,
            'format'       => 'pdf',
        ];

        $result = $service->scanDocument($options);

        if ($result['success'] ?? false) {
            $this->addScannedDocument($result);
            return $result;
        }

        Log::warning("❌ Scan échoué", ['message' => $result['message'] ?? '']);
        return null;
    }

    /**
     * Ajoute un document scanné à la liste interne (sans créer de dossier).
     */
    private function addScannedDocument(array $result): void
    {
        $this->scannedDocuments[] = [
            'id'         => uniqid('scan_'),
            'name'       => $result['filename'] ?? basename($result['path']),
            'path'       => $result['path'],
            'size'       => $result['size'] ?? 0,
            'type'       => 'scanned',
            'created_at' => now()->format('H:i:s'),
        ];

        $this->savePathsToSession();
        $this->dispatch('documents-updated');
    }

    /**
     * Sauvegarde les chemins en session PHP.
     * CreateDossier les récupère au clic sur "Créer" pour fusionner le tout.
     */
    public function savePathsToSession(): void
    {
        $paths = $this->getAllPaths();
        session(['larascan_pdf_paths' => $paths]);
        Log::info('💾 Session sauvegardée', ['count' => count($paths)]);
    }

    private function formatSize(int $bytes): string
    {
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' Go';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 1) . ' Mo';
        if ($bytes >= 1024)       return round($bytes / 1024, 1) . ' Ko';
        return $bytes . ' o';
    }

    public function render()
    {
        return view('livewire.larascan-scanner', [
            'totalDocuments'     => count($this->getAllDocuments()),
            'totalSize'          => $this->getTotalSize(),
            'totalSizeFormatted' => $this->formatSize($this->getTotalSize()),
        ]);
    }
}
