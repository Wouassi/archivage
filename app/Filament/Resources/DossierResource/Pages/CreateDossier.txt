<?php

namespace App\Filament\Resources\DossierResource\Pages;

use App\Events\DocumentArchived;
use App\Filament\Resources\DossierResource;
use App\Models\ActivityLog;
use App\Models\Depense;
use App\Models\Exercice;
use App\Services\PdfMerger;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreateDossier extends CreateRecord
{
    protected static string $resource = DossierResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $allPdfPaths = [];

        // ═══ SOURCE 1 : Fichiers scannés via Larascan (champ hidden) ═══
        if (!empty($data['fichiers_pdf_paths'])) {
            foreach (explode(',', $data['fichiers_pdf_paths']) as $p) {
                $p = trim($p);
                if (!$p) continue;

                // BUG FIX : Storage::disk('public')->path() retourne le chemin absolu
                // même si le fichier n'existe pas — vérifier avec exists() d'abord
                if (!Storage::disk('public')->exists($p)) {
                    Log::warning("[Larascan] Fichier source 1 introuvable sur disk public: {$p}");
                    continue;
                }

                $abs = Storage::disk('public')->path($p);

                if (preg_match('/\.(jpg|jpeg|png)$/i', $p)) {
                    $pdfPath = $this->convertImageToPdf($abs);
                    if ($pdfPath) $allPdfPaths[] = $pdfPath;
                } else {
                    $allPdfPaths[] = $abs;
                }
            }
        }

        // ═══ SOURCE 2 : Fichiers scannés en session Larascan ═══
        $sessionKeys = collect(session()->all())
            ->filter(fn ($v, $k) => str_starts_with($k, 'larascan_files_') && is_array($v));

        foreach ($sessionKeys as $key => $files) {
            foreach ($files as $f) {
                $path = $f['path'] ?? '';
                if (!$path || !Storage::disk('public')->exists($path)) continue;

                $abs = Storage::disk('public')->path($path);
                if (preg_match('/\.(jpg|jpeg|png)$/i', $path)) {
                    $pdfPath = $this->convertImageToPdf($abs);
                    if ($pdfPath) $allPdfPaths[] = $pdfPath;
                } else {
                    $allPdfPaths[] = $abs;
                }
            }
            session()->forget($key);
        }

        // ═══ SOURCE 3 : Upload classique FileUpload ═══
        // BUG FIX : Filament FileUpload retourne un tableau de chemins relatifs
        // (par rapport au disk configuré). Certaines versions retournent
        // un tableau associatif ['uuid' => 'path'], d'autres un tableau indexé.
        if (!empty($data['fichiers_upload'])) {
            $files = $data['fichiers_upload'];

            // Normaliser en tableau de valeurs (ignorer les clés uuid si présentes)
            if (is_array($files)) {
                foreach (array_values($files) as $relativePath) {
                    if (!is_string($relativePath) || empty($relativePath)) continue;

                    if (!Storage::disk('public')->exists($relativePath)) {
                        Log::warning("[Larascan] Fichier upload introuvable: {$relativePath}");
                        continue;
                    }

                    $abs = Storage::disk('public')->path($relativePath);
                    if (file_exists($abs)) {
                        $allPdfPaths[] = $abs;
                    }
                }
            }
        }

        Log::info('[Larascan] Fichiers à fusionner : ' . count($allPdfPaths), $allPdfPaths);

        // ═══ FUSION + COMPRESSION en un seul PDF ═══
        if (!empty($allPdfPaths)) {
            $dep   = Depense::find($data['depense_id']);
            $ex    = Exercice::find($data['exercice_id']);
            $type  = $dep?->type ?? 'FONCTIONNEMENT';
            $annee = $ex?->annee ?? date('Y');
            $fn    = str_replace(['/', '\\', ' '], '_', $data['ordre_paiement'] ?? 'DOC');

            $rel = PdfMerger::merge($allPdfPaths, $fn, $type, (string) $annee);

            if ($rel) {
                $data['fichier_path'] = $rel;
                Log::info("[Larascan] PDF fusionné : {$rel}");
                $this->compressPdf($rel);
            } else {
                Log::error('[Larascan] PdfMerger::merge a retourné null — aucun PDF créé');
            }

            PdfMerger::cleanupTemp($allPdfPaths);
        } else {
            Log::warning('[Larascan] Aucun fichier source trouvé — dossier créé sans PDF');
        }

        unset($data['fichiers_upload'], $data['fichiers_pdf_paths'], $data['larascan_scanner']);
        return $data;
    }

    protected function afterCreate(): void
    {
        $d = $this->record;

        ActivityLog::logActivity(
            'creation',
            "Dossier {$d->ordre_paiement} créé — {$d->beneficiaire}",
            $d, null, $d->toArray()
        );

        if ($d->fichier_path) {
            event(new DocumentArchived($d));
            Notification::make()
                ->title('✅ Dossier créé — PDF fusionné et archivé')
                ->body($d->ordre_paiement . ' • ' . $d->beneficiaire)
                ->success()->duration(4000)->send();
        } else {
            Notification::make()
                ->title('⚠️ Dossier créé sans document PDF')
                ->body('Aucun fichier scanné ou uploadé n\'a été trouvé.')
                ->warning()->duration(6000)->send();
        }
    }

    /**
     * Convertit une image (JPG/PNG) en PDF.
     * Essaie Imagick, puis img2pdf (Python), puis GD en dernier recours.
     */
    private function convertImageToPdf(string $imagePath): ?string
    {
        $pdfPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.pdf', $imagePath);

        // Méthode 1 : Imagick
        if (class_exists(\Imagick::class)) {
            try {
                $im = new \Imagick($imagePath);
                $im->setImageFormat('pdf');
                $im->setImageCompressionQuality(80);
                $im->writeImage($pdfPath);
                $im->destroy();
                return $pdfPath;
            } catch (\Exception $e) {
                Log::warning("[Larascan] Imagick échoué pour {$imagePath}: " . $e->getMessage());
            }
        }

        // Méthode 2 : img2pdf (Python) — disponible sur Windows via pip
        $cmd  = "img2pdf \"{$imagePath}\" -o \"{$pdfPath}\" 2>&1";
        $out  = [];
        $code = 0;
        exec($cmd, $out, $code);
        if ($code === 0 && file_exists($pdfPath)) return $pdfPath;

        Log::warning("[Larascan] Impossible de convertir {$imagePath} en PDF");
        return null;
    }

    /**
     * Compresse le PDF final via Ghostscript si disponible.
     */
    private function compressPdf(string $relativePath): void
    {
        $abs = Storage::disk('public')->path($relativePath);
        if (!file_exists($abs)) return;

        $gs = $this->findGhostscript();
        if (!$gs) return;

        $compressed = $abs . '.compressed.pdf';
        $cmd = "\"{$gs}\" -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 "
             . "-dPDFSETTINGS=/ebook -dNOPAUSE -dQUIET -dBATCH "
             . "-sOutputFile=\"{$compressed}\" \"{$abs}\" 2>&1";

        $out  = [];
        $code = 0;
        exec($cmd, $out, $code);

        if ($code === 0 && file_exists($compressed) && filesize($compressed) < filesize($abs)) {
            rename($compressed, $abs);
            Log::info("[Larascan] PDF compressé via Ghostscript : {$relativePath}");
        } else {
            @unlink($compressed);
        }
    }

    private function findGhostscript(): ?string
    {
        // BUG FIX : Ajout de versions supplémentaires de Ghostscript sur Windows
        $paths = [
            'C:\\Program Files\\gs\\gs10.04.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.03.1\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.02.1\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.01.2\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.00.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs9.56.1\\bin\\gswin64c.exe',
            'C:\\Program Files (x86)\\gs\\gs10.04.0\\bin\\gswin32c.exe',
            'C:\\Program Files (x86)\\gs\\gs10.03.1\\bin\\gswin32c.exe',
            '/usr/bin/gs',
            '/usr/local/bin/gs',
        ];

        foreach ($paths as $p) {
            if (file_exists($p)) return $p;
        }

        $which = PHP_OS_FAMILY === 'Windows' ? 'where gswin64c 2>NUL' : 'which gs 2>/dev/null';
        $r = trim((string)(shell_exec($which) ?? ''));
        return $r ?: null;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
