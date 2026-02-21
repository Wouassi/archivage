<?php

namespace App\Filament\Resources\DossierResource\Pages;

use App\Filament\Resources\DossierResource;
use App\Models\ActivityLog;
use App\Models\Depense;
use App\Models\Exercice;
use Filament\Actions;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class EditDossier extends EditRecord
{
    protected static string $resource = DossierResource::class;

    private array $_tempPaths = [];

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $allPdfPaths = [];

        // SOURCE 1 : Session
        $sessionPaths = session('larascan_pdf_paths', []);
        session()->forget('larascan_pdf_paths');

        foreach ($sessionPaths as $rel) {
            $abs = $this->resolveAndConvert($rel);
            if ($abs) { $allPdfPaths[] = $abs; $this->_tempPaths[] = $rel; }
        }

        // SOURCE 2 : FileUpload
        $uploadData = $data['fichiers_upload'] ?? null;
        if (!empty($uploadData)) {
            foreach (is_array($uploadData) ? array_values($uploadData) : [] as $rel) {
                $abs = $this->resolveAndConvert($rel);
                if ($abs) $allPdfPaths[] = $abs;
            }
        }

        // FUSION
        if (!empty($allPdfPaths)) {
            $depense  = Depense::find($data['depense_id'] ?? $this->record->depense_id);
            $exercice = Exercice::find($data['exercice_id'] ?? $this->record->exercice_id);

            if ($depense && $exercice) {
                $op   = strtoupper(trim($data['ordre_paiement'] ?? $this->record->ordre_paiement ?? 'DOC'));
                $fn   = preg_replace('/[^A-Za-z0-9_-]/', '_', $op);
                $type = strtoupper($depense->type);
                $annee = (string) $exercice->annee;

                if ($this->record->fichier_path && Storage::disk('public')->exists($this->record->fichier_path)) {
                    Storage::disk('public')->delete($this->record->fichier_path);
                }

                $fichierPath = $this->fusionnerPdfs($allPdfPaths, $fn, $type, $annee);
                if ($fichierPath) {
                    $data['fichier_path'] = $fichierPath;
                    $data['cloud_synced_at'] = null;
                }
            }
        }

        unset($data['fichiers_upload'], $data['fichiers_pdf_paths'], $data['larascan_scanner'], $data['larascan']);
        return $data;
    }

    private function resolveAndConvert($rel): ?string
    {
        if (!is_string($rel) || empty(trim($rel))) return null;
        $rel = trim($rel);
        if (!Storage::disk('public')->exists($rel)) return null;
        $abs = Storage::disk('public')->path($rel);
        if (!file_exists($abs)) return null;

        if (preg_match('/\.(jpg|jpeg|png)$/i', $rel)) {
            return $this->imageToPdf($abs);
        }
        return $abs;
    }

    private function fusionnerPdfs(array $pdfPaths, string $fn, string $type, string $annee): ?string
    {
        $valid = array_values(array_filter($pdfPaths, fn($p) => file_exists($p) && filesize($p) > 0));
        if (empty($valid)) return null;

        $relDir = "DOSSIER_ARCHIVE/{$type}/{$annee}";
        $absDir = Storage::disk('public')->path($relDir);
        if (!is_dir($absDir)) mkdir($absDir, 0755, true);

        $outputAbs = rtrim($absDir, '/\\') . DIRECTORY_SEPARATOR . "{$fn}_{$type}_{$annee}.pdf";
        $outputRel = "{$relDir}/{$fn}_{$type}_{$annee}.pdf";
        if (file_exists($outputAbs)) @unlink($outputAbs);

        if (count($valid) === 1) {
            copy($valid[0], $outputAbs);
            return file_exists($outputAbs) ? $outputRel : null;
        }

        $inputs = implode(' ', array_map(fn($p) => '"' . str_replace('"', '', $p) . '"', $valid));

        // Ghostscript
        $gs = $this->findBin(['/usr/bin/gs', '/usr/local/bin/gs',
            'C:\\Program Files\\gs\\gs10.04.0\\bin\\gswin64c.exe', 'C:\\Program Files\\gs\\gs10.03.1\\bin\\gswin64c.exe',
            'C:\\Program Files\\gs\\gs10.02.1\\bin\\gswin64c.exe', 'C:\\Program Files\\gs\\gs9.56.1\\bin\\gswin64c.exe',
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
        $tk = $this->findBin(['/usr/bin/pdftk', '/usr/local/bin/pdftk', 'C:\\Program Files\\PDFtk\\bin\\pdftk.exe'],
            PHP_OS_FAMILY === 'Windows' ? 'where pdftk 2>NUL' : 'which pdftk 2>/dev/null');
        if ($tk) {
            exec("\"{$tk}\" {$inputs} cat output \"{$outputAbs}\" 2>&1", $out, $code);
            if ($code === 0 && file_exists($outputAbs) && filesize($outputAbs) > 0) return $outputRel;
            @unlink($outputAbs);
        }

        // Fallback
        copy($valid[0], $outputAbs);
        return file_exists($outputAbs) ? $outputRel : null;
    }

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

        // GD pur
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
        $scale = min(($pageW - 2 * $margin) / $imgW, ($pageH - 2 * $margin) / $imgH, 1.0);
        $dW = round($imgW * $scale, 2); $dH = round($imgH * $scale, 2);
        $dX = round(($pageW - $dW) / 2, 2); $dY = round($pageH - (($pageH - $dH) / 2) - $dH, 2);

        $o = []; $pdf = "%PDF-1.4\n";
        $o[1] = strlen($pdf); $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $o[2] = strlen($pdf); $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $o[3] = strlen($pdf); $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageW} {$pageH}] /Contents 4 0 R /Resources << /XObject << /Im0 5 0 R >> >> >>\nendobj\n";
        $stream = sprintf("q %.2f 0 0 %.2f %.2f %.2f cm /Im0 Do Q", $dW, $dH, $dX, $dY);
        $o[4] = strlen($pdf); $pdf .= "4 0 obj\n<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream\nendobj\n";
        $o[5] = strlen($pdf); $pdf .= "5 0 obj\n<< /Type /XObject /Subtype /Image /Width {$imgW} /Height {$imgH} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$jpegLen} >>\nstream\n";
        $pdf .= $jpegData . "\nendstream\nendobj\n";
        $xref = strlen($pdf); $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) $pdf .= sprintf("%010d 00000 n \n", $o[$i]);
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF\n";

        return (bool) @file_put_contents($output, $pdf);
    }

    protected function afterSave(): void
    {
        foreach ($this->_tempPaths as $path) {
            try { if (Storage::disk('public')->exists($path)) Storage::disk('public')->delete($path); } catch (\Throwable) {}
        }
        if (class_exists(ActivityLog::class) && method_exists(ActivityLog::class, 'logActivity')) {
            ActivityLog::logActivity('modification', "Dossier {$this->record->ordre_paiement} modifié", $this->record);
        }
        Notification::make()->title('✅ Dossier mis à jour')->success()->send();
    }

    protected function getHeaderActions(): array { return [Actions\ViewAction::make(), Actions\DeleteAction::make()]; }
    protected function getRedirectUrl(): string { return $this->getResource()::getUrl('view', ['record' => $this->record]); }

    private function findBin(array $paths, string $whichCmd): ?string
    {
        foreach ($paths as $p) { if (file_exists($p)) return $p; }
        $r = trim((string)(shell_exec($whichCmd) ?? ''));
        return ($r && file_exists($r)) ? $r : null;
    }
}
