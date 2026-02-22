<?php

namespace App\Filament\Resources\DossierResource\Pages;

use App\Filament\Resources\DossierResource;
use App\Models\ActivityLog;
use App\Models\Depense;
use App\Models\Exercice;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CreateDossier extends CreateRecord
{
    protected static string $resource = DossierResource::class;

    private array $_tempPaths = [];

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        Log::info('━━━ [CreateDossier] Bouton Créer cliqué', [
            'op' => $data['ordre_paiement'] ?? 'NULL',
        ]);

        $allPdfPaths = [];

        // ═══ SOURCE 1 : Session PHP (scans LarascanScanner) ═══
        $sessionPaths = session('larascan_pdf_paths', []);
        session()->forget('larascan_pdf_paths');

        if (!empty($sessionPaths)) {
            Log::info('📦 [CreateDossier] Session : ' . count($sessionPaths) . ' fichier(s)');
        }

        foreach ($sessionPaths as $rel) {
            $abs = $this->resolveAndConvert($rel);
            if ($abs) {
                $allPdfPaths[] = $abs;
                $this->_tempPaths[] = $rel;
            }
        }

        // ═══ SOURCE 2 : FileUpload Filament ═══
        $uploadData = $data['fichiers_upload'] ?? null;
        if (!empty($uploadData)) {
            $uploadPaths = is_array($uploadData) ? array_values($uploadData) : [];
            Log::info('📤 [CreateDossier] FileUpload : ' . count($uploadPaths) . ' fichier(s)');

            foreach ($uploadPaths as $rel) {
                $abs = $this->resolveAndConvert($rel);
                if ($abs) $allPdfPaths[] = $abs;
            }
        }

        Log::info('🔢 [CreateDossier] Total à fusionner : ' . count($allPdfPaths));

        // ═══ FUSION ═══
        if (!empty($allPdfPaths)) {
            $depense  = Depense::find($data['depense_id']);
            $exercice = Exercice::find($data['exercice_id']);

            if (!$depense || !$exercice) {
                Log::error('❌ Dépense ou Exercice introuvable');
                $this->cleanupFields($data);
                return $data;
            }

            $op    = strtoupper(trim($data['ordre_paiement'] ?? 'DOC'));
            $fn    = preg_replace('/[^A-Za-z0-9_-]/', '_', $op);
            $type  = strtoupper($depense->type);
            $annee = (string) $exercice->annee;

            $fichierPath = $this->fusionnerPdfs($allPdfPaths, $fn, $type, $annee);

            if ($fichierPath) {
                $data['fichier_path'] = $fichierPath;
                Log::info("✅ PDF fusionné : {$fichierPath}");
                $this->compresserPdf($fichierPath);
            } else {
                Log::error('❌ Fusion échouée');
            }
        }

        $this->cleanupFields($data);
        return $data;
    }

    // ══════════════════════════════════════════════════════════════
    // APRÈS CRÉATION — nettoie les fichiers ET le scanner
    // ══════════════════════════════════════════════════════════════
    protected function afterCreate(): void
    {
        $record = $this->record;

        // Nettoyer les fichiers temporaires
        foreach ($this->_tempPaths as $path) {
            try {
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
            } catch (\Throwable) {}
        }

        // ═══ VIDER LE SCANNER pour "Créer et ajouter une autre" ═══
        // Vider la session (au cas où)
        session()->forget('larascan_pdf_paths');

        // Dispatch un événement navigateur pour que le composant
        // LarascanScanner se vide automatiquement
        $this->dispatch('scanner-reset');

        // ActivityLog
        if (class_exists(ActivityLog::class) && method_exists(ActivityLog::class, 'logActivity')) {
            ActivityLog::logActivity('creation', "Dossier {$record->ordre_paiement} créé", $record, null, $record->toArray());
        }

        // Cloud sync
        if ($record->fichier_path && class_exists(\App\Events\DocumentArchived::class)) {
            event(new \App\Events\DocumentArchived($record));
        }

        // Notification
        if ($record->pdf_exists) {
            Notification::make()->title('✅ Dossier créé — PDF fusionné')
                ->body(basename($record->fichier_path) . " ({$record->pdf_size})")
                ->success()->duration(6000)->send();
        } else {
            Notification::make()->title('⚠️ Dossier créé sans PDF')
                ->body("Aucun fichier trouvé.")->warning()->duration(8000)->send();
        }
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('view', ['record' => $this->record]);
    }

    // ══════════════════════════════════════════════════════════════
    // RÉSOUDRE CHEMIN → ABSOLU + CONVERSION IMAGE
    // ══════════════════════════════════════════════════════════════
    private function resolveAndConvert($rel): ?string
    {
        if (!is_string($rel) || empty(trim($rel))) return null;
        $rel = trim($rel);

        if (!Storage::disk('public')->exists($rel)) {
            Log::warning("⚠️ Fichier introuvable : {$rel}");
            return null;
        }

        $abs = Storage::disk('public')->path($rel);
        if (!file_exists($abs)) return null;

        if (preg_match('/\.(jpg|jpeg|png)$/i', $rel)) {
            return $this->imageToPdf($abs);
        }

        return $abs;
    }

    // ══════════════════════════════════════════════════════════════
    // FUSION — Ghostscript → pdfunite → pdftk → copie
    // ══════════════════════════════════════════════════════════════
    private function fusionnerPdfs(array $pdfPaths, string $fn, string $type, string $annee): ?string
    {
        $valid = array_values(array_filter($pdfPaths, fn($p) => file_exists($p) && filesize($p) > 0));
        if (empty($valid)) return null;

        $relDir = "DOSSIER_ARCHIVE/{$type}/{$annee}";
        $absDir = Storage::disk('public')->path($relDir);
        if (!is_dir($absDir)) mkdir($absDir, 0755, true);

        $outputName = "{$fn}_{$type}_{$annee}.pdf";
        $outputAbs  = rtrim($absDir, '/\\') . DIRECTORY_SEPARATOR . $outputName;
        $outputRel  = "{$relDir}/{$outputName}";

        if (file_exists($outputAbs)) @unlink($outputAbs);

        if (count($valid) === 1) {
            copy($valid[0], $outputAbs);
            return file_exists($outputAbs) ? $outputRel : null;
        }

        $inputs = implode(' ', array_map(fn($p) => '"' . str_replace('"', '', $p) . '"', $valid));

        // Ghostscript
        $gs = $this->findBin([
            '/usr/bin/gs', '/usr/local/bin/gs',
            'C:\\Program Files\\gs\\gs10.04.0\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.03.1\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.02.1\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs9.56.1\\bin\\gswin64c.exe',
        ], PHP_OS_FAMILY === 'Windows' ? 'where gswin64c 2>NUL' : 'which gs 2>/dev/null');

        if ($gs) {
            exec("\"{$gs}\" -dBATCH -dNOPAUSE -dQUIET -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -sOutputFile=\"{$outputAbs}\" {$inputs} 2>&1", $out, $code);
            if ($code === 0 && file_exists($outputAbs) && filesize($outputAbs) > 0) return $outputRel;
            @unlink($outputAbs);
        }

        // pdfunite
        $pu = $this->findBin(['/usr/bin/pdfunite', '/usr/local/bin/pdfunite'], 'which pdfunite 2>/dev/null');
        if ($pu) {
            exec("\"{$pu}\" {$inputs} \"{$outputAbs}\" 2>&1", $out, $code);
            if ($code === 0 && file_exists($outputAbs) && filesize($outputAbs) > 0) return $outputRel;
            @unlink($outputAbs);
        }

        // pdftk
        $tk = $this->findBin(
            ['/usr/bin/pdftk', '/usr/local/bin/pdftk', 'C:\\Program Files\\PDFtk\\bin\\pdftk.exe'],
            PHP_OS_FAMILY === 'Windows' ? 'where pdftk 2>NUL' : 'which pdftk 2>/dev/null'
        );
        if ($tk) {
            exec("\"{$tk}\" {$inputs} cat output \"{$outputAbs}\" 2>&1", $out, $code);
            if ($code === 0 && file_exists($outputAbs) && filesize($outputAbs) > 0) return $outputRel;
            @unlink($outputAbs);
        }

        // Fallback
        Log::warning("[Fusion] ⚠️ Aucun outil — copie 1er fichier. Installez Ghostscript.");
        copy($valid[0], $outputAbs);
        return file_exists($outputAbs) ? $outputRel : null;
    }

    // ══════════════════════════════════════════════════════════════
    // IMAGE → PDF
    // ══════════════════════════════════════════════════════════════
    private function imageToPdf(string $imagePath): ?string
    {
        $pdfPath = $imagePath . '.pdf';

        if (class_exists(\Imagick::class)) {
            try {
                $im = new \Imagick($imagePath);
                $im->setImageFormat('pdf');
                $im->setImageCompressionQuality(80);
                $im->writeImage($pdfPath);
                $im->destroy();
                if (file_exists($pdfPath) && filesize($pdfPath) > 0) return $pdfPath;
            } catch (\Throwable) {}
        }

        $img2pdf = $this->findBin(['/usr/bin/img2pdf', '/usr/local/bin/img2pdf'],
            PHP_OS_FAMILY === 'Windows' ? 'where img2pdf 2>NUL' : 'which img2pdf 2>/dev/null');
        if ($img2pdf) {
            exec("\"{$img2pdf}\" \"{$imagePath}\" -o \"{$pdfPath}\" 2>&1", $o, $c);
            if ($c === 0 && file_exists($pdfPath) && filesize($pdfPath) > 0) return $pdfPath;
        }

        if ($this->gdImageToPdf($imagePath, $pdfPath)) return $pdfPath;

        return null;
    }

    private function gdImageToPdf(string $input, string $output): bool
    {
        $info = @getimagesize($input);
        if (!$info) return false;
        [$imgW, $imgH, $imgType] = $info;

        $tmpJpg = $input . '.tmp.jpg';
        $cleanup = false;

        if ($imgType === IMAGETYPE_JPEG) { $tmpJpg = $input; }
        elseif ($imgType === IMAGETYPE_PNG && function_exists('imagecreatefrompng')) {
            $img = @imagecreatefrompng($input);
            if (!$img) return false;
            $canvas = imagecreatetruecolor($imgW, $imgH);
            imagefilledrectangle($canvas, 0, 0, $imgW, $imgH, imagecolorallocate($canvas, 255, 255, 255));
            imagecopy($canvas, $img, 0, 0, 0, 0, $imgW, $imgH);
            imagejpeg($canvas, $tmpJpg, 85);
            imagedestroy($img); imagedestroy($canvas);
            $cleanup = true;
        } else { return false; }

        $jpegData = @file_get_contents($tmpJpg);
        if ($cleanup) @unlink($tmpJpg);
        if (!$jpegData) return false;

        $jpegLen = strlen($jpegData);
        $pageW = 595.28; $pageH = 841.89; $margin = 28.35;
        $scale = min(($pageW - 2*$margin)/$imgW, ($pageH - 2*$margin)/$imgH, 1.0);
        $dW = round($imgW*$scale,2); $dH = round($imgH*$scale,2);
        $dX = round(($pageW-$dW)/2,2); $dY = round($pageH-(($pageH-$dH)/2)-$dH,2);

        $o=[]; $pdf="%PDF-1.4\n";
        $o[1]=strlen($pdf); $pdf.="1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $o[2]=strlen($pdf); $pdf.="2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $o[3]=strlen($pdf); $pdf.="3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageW} {$pageH}] /Contents 4 0 R /Resources << /XObject << /Im0 5 0 R >> >> >>\nendobj\n";
        $stream=sprintf("q %.2f 0 0 %.2f %.2f %.2f cm /Im0 Do Q",$dW,$dH,$dX,$dY);
        $o[4]=strlen($pdf); $pdf.="4 0 obj\n<< /Length ".strlen($stream)." >>\nstream\n{$stream}\nendstream\nendobj\n";
        $o[5]=strlen($pdf); $pdf.="5 0 obj\n<< /Type /XObject /Subtype /Image /Width {$imgW} /Height {$imgH} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$jpegLen} >>\nstream\n";
        $pdf.=$jpegData."\nendstream\nendobj\n";
        $xref=strlen($pdf); $pdf.="xref\n0 6\n0000000000 65535 f \n";
        for($i=1;$i<=5;$i++) $pdf.=sprintf("%010d 00000 n \n",$o[$i]);
        $pdf.="trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        return (bool)@file_put_contents($output,$pdf);
    }

    private function compresserPdf(string $relativePath): void
    {
        $abs = Storage::disk('public')->path($relativePath);
        if (!file_exists($abs)) return;
        $gs = $this->findBin(['/usr/bin/gs','/usr/local/bin/gs',
            'C:\\Program Files\\gs\\gs10.04.0\\bin\\gswin64c.exe','C:\\Program Files\\gs\\gs10.03.1\\bin\\gswin64c.exe',
        ], PHP_OS_FAMILY === 'Windows' ? 'where gswin64c 2>NUL' : 'which gs 2>/dev/null');
        if (!$gs) return;
        $tmp=$abs.'.compressed.pdf';
        exec("\"{$gs}\" -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook -dNOPAUSE -dQUIET -dBATCH -sOutputFile=\"{$tmp}\" \"{$abs}\" 2>&1",$out,$code);
        if ($code===0 && file_exists($tmp) && filesize($tmp)>0 && filesize($tmp)<filesize($abs)) rename($tmp,$abs);
        else @unlink($tmp);
    }

    private function cleanupFields(array &$data): void
    {
        unset($data['fichiers_upload'],$data['fichiers_pdf_paths'],$data['larascan_scanner'],$data['larascan']);
    }

    private function findBin(array $paths, string $whichCmd): ?string
    {
        foreach ($paths as $p) { if (file_exists($p)) return $p; }
        $r=trim((string)(shell_exec($whichCmd)??''));
        return ($r && file_exists($r)) ? $r : null;
    }

    private function formatSize(int $bytes): string
    {
        $u=['o','Ko','Mo']; $i=0;
        while($bytes>=1024 && $i<2){$bytes/=1024;$i++;}
        return round($bytes,1).' '.$u[$i];
    }
}
