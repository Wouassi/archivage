<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Exception;

/**
 * Service de numérisation optimisé pour Windows WIA et Linux SANE
 * Privilégie WIA sur Windows pour une meilleure détection
 */
class ScannerLarascanService
{
    private string $driver;
    private string $disk;
    private string $storagePath;
    private array $config;
    private bool $isWindows;

    public function __construct()
    {
        $this->config = config('larascan', [
            'driver' => 'auto',
            'output' => [
                'disk' => 'public',
                'path' => 'scanner/temp',
                'compression' => true,
            ],
            'wia' => [
                'default_resolution' => 150,
                'default_mode' => 'Gray',
            ],
            'sane' => [
                'scanimage_path' => '/usr/bin/scanimage',
                'default_resolution' => 150,
                'default_mode' => 'Gray',
            ],
            'pdf' => [
                'quality' => 50,
            ],
            'limits' => [
                'max_documents' => 500,
            ],
        ]);

        $this->isWindows = PHP_OS_FAMILY === 'Windows';
        
        // Déterminer le driver automatiquement
        if ($this->config['driver'] === 'auto') {
            $this->driver = $this->isWindows ? 'wia' : 'sane';
        } else {
            $this->driver = $this->config['driver'];
        }

        $this->disk = $this->config['output']['disk'];
        $this->storagePath = $this->config['output']['path'];
        
        $this->ensureStorageExists();
        
        Log::info("📷 ScannerLarascanService initialisé", [
            'os' => PHP_OS_FAMILY,
            'driver' => $this->driver,
            'storage' => $this->storagePath
        ]);
    }

    /**
     * Créer le répertoire de stockage
     */
    private function ensureStorageExists(): void
    {
        $fullPath = Storage::disk($this->disk)->path($this->storagePath);
        
        if (!File::exists($fullPath)) {
            File::makeDirectory($fullPath, 0755, true);
            Log::info("📁 Répertoire créé: {$fullPath}");
        }
    }

    /**
     * Détecter tous les scanners disponibles
     * Privilégie WIA sur Windows
     */
    public function detectScanners(): array
    {
        Log::info('🔍 Détection des scanners (OS: ' . PHP_OS_FAMILY . ')');

        $scanners = [];

        // WINDOWS : Priorité à WIA
        if ($this->isWindows) {
            $wiaScanners = $this->detectWiaScanners();
            if (!empty($wiaScanners)) {
                $scanners = $wiaScanners;
                Log::info("✅ WIA (Windows): " . count($wiaScanners) . " scanner(s) détecté(s)");
            }
        } 
        // LINUX/MAC : SANE
        else {
            $saneScanners = $this->detectSaneScanners();
            if (!empty($saneScanners)) {
                $scanners = $saneScanners;
                Log::info("✅ SANE (Linux/Mac): " . count($saneScanners) . " scanner(s) détecté(s)");
            }
        }

        // Fallback : essayer l'autre méthode si aucun scanner détecté
        if (empty($scanners)) {
            Log::warning("⚠️ Aucun scanner avec driver principal, tentative fallback...");
            
            if ($this->isWindows) {
                // Essayer SANE sur Windows (WSL)
                $saneScanners = $this->detectSaneScanners();
                if (!empty($saneScanners)) {
                    $scanners = $saneScanners;
                    Log::info("✅ Fallback SANE: " . count($saneScanners) . " scanner(s)");
                }
            }
        }

        return $scanners;
    }

    /**
     * Détecter les scanners WIA (Windows)
     * OPTIMISÉ pour Windows 10/11
     */
    private function detectWiaScanners(): array
    {
        $scanners = [];

        if (!$this->isWindows) {
            return $scanners;
        }

        try {
            // Script PowerShell optimisé pour WIA 2.0
            $psScript = <<<'POWERSHELL'
try {
    # Charger l'assemblage WIA
    Add-Type -AssemblyName "Microsoft.VisualBasic"
    
    # Créer le Device Manager WIA
    $deviceManager = New-Object -ComObject WIA.DeviceManager
    
    # Énumérer tous les périphériques
    $devices = $deviceManager.DeviceInfos
    
    if ($devices.Count -eq 0) {
        Write-Output "NO_DEVICES"
        exit 0
    }
    
    foreach ($device in $devices) {
        try {
            $deviceType = $device.Type
            
            # Type 1 = Scanner
            if ($deviceType -eq 1) {
                $deviceId = $device.DeviceID
                $deviceName = $device.Properties("Name").Value
                $manufacturer = ""
                
                try {
                    $manufacturer = $device.Properties("Manufacturer").Value
                } catch {
                    $manufacturer = "Unknown"
                }
                
                # Format de sortie : ID|||NAME|||MANUFACTURER
                Write-Output "$deviceId|||$deviceName|||$manufacturer"
            }
        } catch {
            # Ignorer les erreurs pour ce périphérique
            continue
        }
    }
    
    exit 0
} catch {
    Write-Error $_.Exception.Message
    exit 1
}
POWERSHELL;

            // Créer un fichier temporaire pour le script
            $tempScript = tempnam(sys_get_temp_dir(), 'wia_detect_') . '.ps1';
            file_put_contents($tempScript, $psScript);

            // Exécuter PowerShell avec politique d'exécution bypass
            $command = sprintf(
                'powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%s" 2>&1',
                $tempScript
            );

            exec($command, $output, $returnCode);

            // Supprimer le fichier temporaire
            @unlink($tempScript);

            Log::debug("WIA PowerShell output", [
                'code' => $returnCode,
                'output' => $output
            ]);

            if ($returnCode === 0 && !empty($output)) {
                foreach ($output as $line) {
                    $line = trim($line);
                    
                    if ($line === 'NO_DEVICES') {
                        Log::info("WIA: Aucun scanner détecté");
                        break;
                    }
                    
                    if (strpos($line, '|||') !== false) {
                        $parts = explode('|||', $line);
                        
                        if (count($parts) >= 2) {
                            $deviceId = trim($parts[0]);
                            $deviceName = trim($parts[1]);
                            $manufacturer = isset($parts[2]) ? trim($parts[2]) : 'Unknown';
                            
                            $scanners[] = [
                                'id' => $deviceId,
                                'name' => $deviceName,
                                'description' => $manufacturer . ' Scanner',
                                'type' => 'WIA',
                                'driver' => 'wia',
                                'available' => true,
                                'supports_color' => true,
                                'supports_gray' => true,
                                'supports_lineart' => true,
                                'max_resolution' => 600,
                                'manufacturer' => $manufacturer,
                            ];
                            
                            Log::info("✅ Scanner WIA détecté: {$deviceName}", [
                                'id' => $deviceId,
                                'manufacturer' => $manufacturer
                            ]);
                        }
                    }
                }
            }
        } catch (Exception $e) {
            Log::error("❌ Erreur détection WIA: " . $e->getMessage());
        }

        return $scanners;
    }

    /**
     * Détecter les scanners SANE (Linux/Mac)
     */
    private function detectSaneScanners(): array
    {
        $scanners = [];
        
        try {
            $scanImagePath = $this->config['sane']['scanimage_path'] ?? 'scanimage';
            
            // Vérifier si scanimage existe
            exec("which {$scanImagePath} 2>&1", $whichOutput, $whichCode);
            
            if ($whichCode !== 0) {
                Log::debug("SANE scanimage non trouvé");
                return $scanners;
            }

            // Lister les scanners
            exec("{$scanImagePath} -L 2>&1", $output, $returnCode);
            
            if ($returnCode === 0 && !empty($output)) {
                foreach ($output as $line) {
                    // Format: device `device_name' is a Description
                    if (preg_match("/device `([^']+)' is a (.+)$/", $line, $matches)) {
                        $deviceId = trim($matches[1]);
                        $description = trim($matches[2]);
                        
                        // Extraire le nom du modèle
                        $modelName = $this->extractModelName($description);
                        
                        $scanners[] = [
                            'id' => $deviceId,
                            'name' => $modelName,
                            'description' => $description,
                            'type' => 'SANE',
                            'driver' => 'sane',
                            'available' => true,
                            'supports_color' => true,
                            'supports_gray' => true,
                            'supports_lineart' => true,
                            'max_resolution' => 600,
                        ];
                        
                        Log::info("✅ Scanner SANE détecté: {$modelName}", ['device' => $deviceId]);
                    }
                }
            }
        } catch (Exception $e) {
            Log::error("❌ Erreur détection SANE: " . $e->getMessage());
        }

        return $scanners;
    }

    /**
     * Numériser un document et convertir en PDF
     */
    public function scanDocument(array $options = []): array
    {
        $scannerId = $options['scanner_id'] ?? null;
        $scannerType = $options['scanner_type'] ?? $this->driver;
        $resolution = $options['resolution'] ?? 150;
        $mode = $options['mode'] ?? 'Gray';

        if (!$scannerId) {
            throw new Exception("ID du scanner requis");
        }

        Log::info("🖨️ Numérisation Larascan", [
            'scanner' => $scannerId,
            'type' => $scannerType,
            'resolution' => $resolution,
            'mode' => $mode
        ]);

        try {
            // Numériser l'image
            $imagePath = $this->scanImage($scannerId, $scannerType, $resolution, $mode);
            
            Log::info("✅ Image numérisée: " . basename($imagePath));

            // Convertir en PDF
            $pdfPath = $this->convertImageToPdf($imagePath);
            
            Log::info("✅ Conversion PDF: " . basename($pdfPath));

            // Compresser le PDF
            $compressedPath = $this->compressPdf($pdfPath);
            
            // Supprimer l'image temporaire
            if (file_exists($imagePath)) {
                @unlink($imagePath);
            }

            // Préparer les métadonnées
            $relativePath = str_replace(
                Storage::disk($this->disk)->path(''),
                '',
                $compressedPath
            );

            $fileSize = filesize($compressedPath);

            $result = [
                'success' => true,
                'filename' => basename($compressedPath),
                'path' => $relativePath,
                'url' => Storage::disk($this->disk)->url($relativePath),
                'size' => $fileSize,
                'size_formatted' => $this->formatBytes($fileSize),
                'timestamp' => now()->toDateTimeString(),
                'type' => 'scanned',
                'scanner' => $scannerId,
                'resolution' => $resolution,
                'mode' => $mode,
            ];

            Log::info("✅ Numérisation réussie", [
                'file' => $result['filename'],
                'size' => $result['size_formatted']
            ]);

            return $result;

        } catch (Exception $e) {
            Log::error("❌ Erreur numérisation: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Scan en lot ADF — scanne page par page jusqu'à épuisement du bac.
     * Retourne un tableau de résultats (un par page scannée).
     *
     * @param array $options [scanner_id, scanner_type, resolution, mode, max_pages]
     * @return array Tableau de résultats ['success' => bool, 'path' => ..., ...]
     */
    public function scanBatchAdf(array $options = []): array
    {
        $scannerId  = $options['scanner_id'] ?? null;
        $scannerType = $options['scanner_type'] ?? $this->driver;
        $resolution = $options['resolution'] ?? 75;
        $mode       = $options['mode'] ?? 'Gray';
        $maxPages   = $options['max_pages'] ?? 500;

        if (!$scannerId) {
            throw new Exception("ID du scanner requis pour le scan ADF");
        }

        Log::info("📚 [ScannerService] Scan ADF batch", [
            'scanner'    => $scannerId,
            'max_pages'  => $maxPages,
            'resolution' => $resolution,
        ]);

        $results = [];
        $consecutiveErrors = 0;

        for ($page = 0; $page < $maxPages; $page++) {
            try {
                // Réutiliser la méthode scanDocument existante pour chaque page
                $result = $this->scanDocument([
                    'scanner_id'   => $scannerId,
                    'scanner_type' => $scannerType,
                    'resolution'   => $resolution,
                    'mode'         => $mode,
                ]);

                $results[] = $result;
                $consecutiveErrors = 0;

                Log::info("📄 [ADF] Page " . ($page + 1) . " OK", [
                    'file' => $result['filename'] ?? '',
                ]);

            } catch (Exception $e) {
                $consecutiveErrors++;
                $errorMsg = strtolower($e->getMessage());

                // Détecter les erreurs de bac vide
                $bacVidePatterns = [
                    'no documents', 'paper empty', 'out of paper',
                    'no more pages', 'feeder empty', 'adf empty',
                    'document feeder', 'bac vide', 'wia_error_paper_empty',
                    'no paper', 'aucun document',
                ];

                $bacVide = false;
                foreach ($bacVidePatterns as $pattern) {
                    if (str_contains($errorMsg, $pattern)) {
                        $bacVide = true;
                        break;
                    }
                }

                if ($bacVide) {
                    Log::info("📭 [ADF] Bac vide — arrêt après " . count($results) . " page(s)");
                    break;
                }

                // 2 erreurs consécutives non-bac-vide = problème, on arrête
                if ($consecutiveErrors >= 2) {
                    Log::warning("⛔ [ADF] Arrêt après {$consecutiveErrors} erreurs", [
                        'pages' => count($results),
                        'error' => $e->getMessage(),
                    ]);
                    break;
                }
            }
        }

        Log::info("📚 [ADF] Batch terminé : " . count($results) . " page(s)");

        return $results;
    }

    /**
     * Numériser une image
     */
    private function scanImage(string $scannerId, string $scannerType, int $resolution, string $mode): string
    {
        return match(strtoupper($scannerType)) {
            'WIA' => $this->scanWithWia($scannerId, $resolution, $mode),
            'SANE' => $this->scanWithSane($scannerId, $resolution, $mode),
            default => throw new Exception("Type de scanner non supporté: {$scannerType}")
        };
    }

    /**
     * Numériser avec WIA (Windows) - VERSION OPTIMISÉE
     */
    private function scanWithWia(string $deviceId, int $resolution, string $mode): string
    {
        $timestamp = now()->format('YmdHis_') . uniqid();
        $filename = "scan_temp_{$timestamp}.jpg";
        $outputPath = Storage::disk($this->disk)->path("{$this->storagePath}/{$filename}");

        // Convertir le mode
        $colorMode = match($mode) {
            'Color' => 1,
            'Gray' => 2,
            'Lineart' => 4,
            default => 2
        };

        // Script PowerShell optimisé pour numérisation WIA
        $psScript = <<<POWERSHELL
try {
    \$deviceId = '{$deviceId}'
    \$outputPath = '{$outputPath}'
    \$resolution = {$resolution}
    \$colorMode = {$colorMode}
    
    # Créer Device Manager
    \$deviceManager = New-Object -ComObject WIA.DeviceManager
    
    # Trouver le scanner
    \$scanner = \$null
    foreach (\$dev in \$deviceManager.DeviceInfos) {
        if (\$dev.DeviceID -eq \$deviceId) {
            \$scanner = \$dev.Connect()
            break
        }
    }
    
    if (\$scanner -eq \$null) {
        throw "Scanner non trouvé: \$deviceId"
    }
    
    # Obtenir l'item à scanner (généralement le premier)
    \$item = \$scanner.Items(1)
    
    # Configurer les propriétés
    try {
        # Résolution horizontale
        \$item.Properties("Horizontal Resolution").Value = \$resolution
        # Résolution verticale
        \$item.Properties("Vertical Resolution").Value = \$resolution
        # Mode couleur
        \$item.Properties("Current Intent").Value = \$colorMode
    } catch {
        Write-Warning "Impossible de définir certaines propriétés"
    }
    
    # Numériser
    \$image = \$item.Transfer("{B96B3CAE-0728-11D3-9D7B-0000F81EF32E}")
    
    # Sauvegarder
    \$image.SaveFile(\$outputPath)
    
    Write-Output "SUCCESS:\$outputPath"
    exit 0
} catch {
    Write-Error \$_.Exception.Message
    exit 1
}
POWERSHELL;

        $tempScript = tempnam(sys_get_temp_dir(), 'wia_scan_') . '.ps1';
        file_put_contents($tempScript, $psScript);

        $command = sprintf(
            'powershell.exe -NoProfile -ExecutionPolicy Bypass -File "%s" 2>&1',
            $tempScript
        );

        exec($command, $output, $returnCode);

        @unlink($tempScript);

        if ($returnCode !== 0) {
            $error = implode("\n", $output);
            throw new Exception("Échec numérisation WIA: {$error}");
        }

        if (!file_exists($outputPath)) {
            throw new Exception("Fichier WIA non créé");
        }

        return $outputPath;
    }

    /**
     * Numériser avec SANE
     */
    private function scanWithSane(string $deviceId, int $resolution, string $mode): string
    {
        $scanImagePath = $this->config['sane']['scanimage_path'] ?? 'scanimage';
        $timestamp = now()->format('YmdHis_') . uniqid();
        $filename = "scan_temp_{$timestamp}.jpg";
        $outputPath = Storage::disk($this->disk)->path("{$this->storagePath}/{$filename}");

        $command = sprintf(
            '%s --device-name=%s --resolution=%d --mode=%s --format=jpeg > %s 2>&1',
            $scanImagePath,
            escapeshellarg($deviceId),
            (int)$resolution,
            escapeshellarg($mode),
            escapeshellarg($outputPath)
        );

        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            $error = implode("\n", $output);
            throw new Exception("Échec numérisation SANE: {$error}");
        }

        if (!file_exists($outputPath) || filesize($outputPath) === 0) {
            throw new Exception("Fichier scanné invalide");
        }

        return $outputPath;
    }

    /**
     * Convertir image en PDF
     *
     * Méthodes (par priorité) :
     *   1. Imagick PHP (extension)
     *   2. GD PHP (natif, toujours disponible) — crée un PDF minimal
     *   3. img2pdf (binaire Python, très fiable)
     *   4. ImageMagick CLI (convert / magick)
     */
    private function convertImageToPdf(string $imagePath): string
    {
        $pdfPath = preg_replace('/\.(jpg|jpeg|png)$/i', '.pdf', $imagePath);

        $methods = [
            'imagick_php'    => fn() => $this->convertWithImagickPhp($imagePath, $pdfPath),
            'gd_raw_pdf'     => fn() => $this->convertWithGdRawPdf($imagePath, $pdfPath),
            'img2pdf'        => fn() => $this->convertWithImg2pdf($imagePath, $pdfPath),
            'imagemagick_cli'=> fn() => $this->convertWithImageMagickCli($imagePath, $pdfPath),
        ];

        foreach ($methods as $method => $converter) {
            try {
                if ($converter() && file_exists($pdfPath) && filesize($pdfPath) > 0) {
                    Log::info("✅ Conversion image→PDF avec {$method}");
                    return $pdfPath;
                }
            } catch (Exception $e) {
                Log::debug("Méthode {$method} non disponible: " . $e->getMessage());
            }
        }

        throw new Exception("Impossible de convertir l'image en PDF. Installez Imagick (PHP) ou img2pdf (pip install img2pdf).");
    }

    /**
     * Méthode 1 : Imagick PHP extension
     */
    private function convertWithImagickPhp(string $input, string $output): bool
    {
        if (!class_exists(\Imagick::class)) {
            return false;
        }

        $im = new \Imagick($input);
        $im->setImageFormat('pdf');
        $im->setImageCompressionQuality(80);
        $im->writeImage($output);
        $im->destroy();

        return file_exists($output) && filesize($output) > 0;
    }

    /**
     * Méthode 2 : GD PHP — génère un PDF minimal avec l'image JPEG embarquée
     *
     * Cette méthode utilise GD (toujours disponible en PHP) pour lire l'image
     * et génère manuellement un fichier PDF valide contenant l'image.
     * Pas besoin de bibliothèque externe (ni FPDF, ni TCPDF, ni Imagick).
     */
    private function convertWithGdRawPdf(string $input, string $output): bool
    {
        if (!function_exists('imagecreatefromjpeg') && !function_exists('imagecreatefrompng')) {
            return false;
        }

        $imageInfo = @getimagesize($input);
        if (!$imageInfo) return false;

        $imgWidth  = $imageInfo[0];
        $imgHeight = $imageInfo[1];
        $imgType   = $imageInfo[2];

        // Convertir en JPEG temporaire (le format le plus simple pour PDF)
        $tmpJpeg = $input . '.tmp.jpg';
        $needsCleanup = false;

        if ($imgType === IMAGETYPE_JPEG) {
            $tmpJpeg = $input;
        } elseif ($imgType === IMAGETYPE_PNG) {
            $img = @imagecreatefrompng($input);
            if (!$img) return false;
            // Fond blanc pour les PNG transparents
            $white = imagecreatetruecolor($imgWidth, $imgHeight);
            $bg = imagecolorallocate($white, 255, 255, 255);
            imagefilledrectangle($white, 0, 0, $imgWidth, $imgHeight, $bg);
            imagecopy($white, $img, 0, 0, 0, 0, $imgWidth, $imgHeight);
            imagejpeg($white, $tmpJpeg, 85);
            imagedestroy($img);
            imagedestroy($white);
            $needsCleanup = true;
        } else {
            return false;
        }

        $jpegData = @file_get_contents($tmpJpeg);
        if ($needsCleanup) @unlink($tmpJpeg);
        if (!$jpegData) return false;

        $jpegLength = strlen($jpegData);

        // Dimensions page A4 en points (72 DPI)
        $pageW = 595.28;
        $pageH = 841.89;

        // Calculer la taille de l'image sur la page (avec marge)
        $margin = 28.35; // 10mm
        $maxW = $pageW - 2 * $margin;
        $maxH = $pageH - 2 * $margin;

        $scale = min($maxW / $imgWidth, $maxH / $imgHeight);
        $drawW = $imgWidth * $scale;
        $drawH = $imgHeight * $scale;
        $drawX = ($pageW - $drawW) / 2;
        $drawY = ($pageH - $drawH) / 2;

        // Générer le PDF manuellement (PDF 1.4 minimal)
        $offsets = [];
        $pdf = "%PDF-1.4\n";

        // Objet 1 : Catalogue
        $offsets[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

        // Objet 2 : Pages
        $offsets[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

        // Objet 3 : Page
        $offsets[3] = strlen($pdf);
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pageW} {$pageH}] "
              . "/Contents 4 0 R /Resources << /XObject << /Img0 5 0 R >> >> >>\nendobj\n";

        // Objet 4 : Contenu (dessiner l'image)
        // Note : PDF y=0 est en bas, donc on inverse
        $drawYpdf = $pageH - $drawY - $drawH;
        $stream = sprintf("q %.2f 0 0 %.2f %.2f %.2f cm /Img0 Do Q", $drawW, $drawH, $drawX, $drawYpdf);
        $streamLen = strlen($stream);
        $offsets[4] = strlen($pdf);
        $pdf .= "4 0 obj\n<< /Length {$streamLen} >>\nstream\n{$stream}\nendstream\nendobj\n";

        // Objet 5 : Image JPEG
        $offsets[5] = strlen($pdf);
        $pdf .= "5 0 obj\n<< /Type /XObject /Subtype /Image /Width {$imgWidth} /Height {$imgHeight} "
              . "/ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$jpegLength} >>\n"
              . "stream\n";
        $pdf .= $jpegData;
        $pdf .= "\nendstream\nendobj\n";

        // Table de références croisées
        $xrefOffset = strlen($pdf);
        $pdf .= "xref\n0 6\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefOffset}\n%%EOF\n";

        $written = @file_put_contents($output, $pdf);

        return $written > 0 && file_exists($output);
    }

    /**
     * Méthode 3 : img2pdf (Python, très fiable)
     */
    private function convertWithImg2pdf(string $input, string $output): bool
    {
        $bin = $this->findBin(
            ['/usr/bin/img2pdf', '/usr/local/bin/img2pdf'],
            $this->isWindows ? 'where img2pdf 2>NUL' : 'which img2pdf 2>/dev/null'
        );

        if (!$bin) return false;

        $cmd = sprintf('"%s" "%s" -o "%s" 2>&1', $bin, $input, $output);
        exec($cmd, $out, $code);

        return $code === 0 && file_exists($output) && filesize($output) > 0;
    }

    /**
     * Méthode 4 : ImageMagick CLI
     */
    private function convertWithImageMagickCli(string $input, string $output): bool
    {
        // Trouver le binaire
        if ($this->isWindows) {
            $bin = $this->findBin(
                ['C:\\Program Files\\ImageMagick-7.1.1-Q16-HDRI\\magick.exe'],
                'where magick 2>NUL'
            );
            $cmd = $bin ? "\"{$bin}\" convert" : 'magick convert';
        } else {
            $bin = $this->findBin(
                ['/usr/bin/convert', '/usr/local/bin/convert'],
                'which convert 2>/dev/null'
            );
            if (!$bin) return false;
            $cmd = "\"{$bin}\"";
        }

        $command = sprintf(
            '%s "%s" -quality 80 -compress jpeg -density 150 "%s" 2>&1',
            $cmd, $input, $output
        );

        exec($command, $out, $code);

        return $code === 0 && file_exists($output) && filesize($output) > 0;
    }

    /**
     * Trouver un binaire système
     */
    private function findBin(array $paths, string $whichCmd): ?string
    {
        foreach ($paths as $p) {
            if (file_exists($p)) return $p;
        }
        $r = trim((string)(shell_exec($whichCmd) ?? ''));
        return $r ?: null;
    }

    /**
     * Compresser un PDF
     */
    private function compressPdf(string $pdfPath): string
    {
        if (!($this->config['output']['compression'] ?? true)) {
            return $pdfPath;
        }

        try {
            $gs = $this->findGhostscript();
            if (!$gs) {
                Log::debug("Ghostscript non trouvé — compression ignorée");
                return $pdfPath;
            }

            $compressedPath = $pdfPath . '.compressed.pdf';

            $command = sprintf(
                '"%s" -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/ebook '
                . '-dNOPAUSE -dQUIET -dBATCH -sOutputFile="%s" "%s" 2>&1',
                $gs,
                $compressedPath,
                $pdfPath
            );

            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($compressedPath)) {
                $originalSize   = filesize($pdfPath);
                $compressedSize = filesize($compressedPath);

                if ($compressedSize > 0 && $compressedSize < $originalSize) {
                    @unlink($pdfPath);
                    rename($compressedPath, $pdfPath);
                    $reduction = round((1 - $compressedSize / $originalSize) * 100);
                    Log::info("✅ PDF compressé via Ghostscript: -{$reduction}%");
                } else {
                    @unlink($compressedPath);
                }
            } else {
                @unlink($compressedPath);
            }
        } catch (Exception $e) {
            Log::warning("Compression PDF échouée: " . $e->getMessage());
        }

        return $pdfPath;
    }

    /**
     * Trouve le binaire Ghostscript sur le système.
     */
    private function findGhostscript(): ?string
    {
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

        $which = $this->isWindows ? 'where gswin64c 2>NUL' : 'which gs 2>/dev/null';
        $r = trim((string) (shell_exec($which) ?? ''));
        return $r ?: null;
    }

    /**
     * Uploader et compresser des fichiers PDF
     */
    public function uploadAndCompressFiles(array $uploadedFiles): array
    {
        $results = [];

        foreach ($uploadedFiles as $file) {
            try {
                $timestamp = now()->format('YmdHis_') . uniqid();
                $filename = "upload_{$timestamp}.pdf";
                
                $path = $file->storeAs($this->storagePath, $filename, $this->disk);
                $absolutePath = Storage::disk($this->disk)->path($path);

                $compressedPath = $this->compressPdf($absolutePath);
                
                $relativePath = str_replace(
                    Storage::disk($this->disk)->path(''),
                    '',
                    $compressedPath
                );

                $fileSize = filesize($compressedPath);

                $results[] = [
                    'filename' => basename($compressedPath),
                    'path' => $relativePath,
                    'url' => Storage::disk($this->disk)->url($relativePath),
                    'size' => $fileSize,
                    'size_formatted' => $this->formatBytes($fileSize),
                    'timestamp' => now()->toDateTimeString(),
                    'type' => 'uploaded',
                ];

            } catch (Exception $e) {
                Log::error("Erreur upload fichier: " . $e->getMessage());
            }
        }

        return $results;
    }

    /**
     * Extraire le nom du modèle
     */
    private function extractModelName(string $description): string
    {
        $name = trim($description);
        $name = preg_replace('/(flatbed|scanner|device)$/i', '', $name);
        return trim($name) ?: 'Scanner';
    }

    /**
     * Formater la taille
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= pow(1024, $pow);

        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    /**
     * Obtenir tous les fichiers scannés
     */
    public function getAllScannedFiles(): array
    {
        try {
            $files = Storage::disk($this->disk)->files($this->storagePath);
            $result = [];

            foreach ($files as $file) {
                if (pathinfo($file, PATHINFO_EXTENSION) === 'pdf') {
                    $fullPath = Storage::disk($this->disk)->path($file);
                    
                    $result[] = [
                        'filename' => basename($file),
                        'name' => basename($file),
                        'path' => $file,
                        'url' => Storage::disk($this->disk)->url($file),
                        'size' => filesize($fullPath),
                        'timestamp' => Storage::disk($this->disk)->lastModified($file),
                    ];
                }
            }

            usort($result, fn($a, $b) => $b['timestamp'] <=> $a['timestamp']);

            return $result;

        } catch (Exception $e) {
            Log::error('Erreur récupération fichiers: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Nettoyer les fichiers temporaires
     */
    public function cleanupTempFiles(int $olderThanHours = null): int
    {
        $olderThanHours = $olderThanHours ?? 24;
        
        try {
            $files = Storage::disk($this->disk)->files($this->storagePath);
            $deleted = 0;
            $threshold = now()->subHours($olderThanHours)->timestamp;

            foreach ($files as $file) {
                $lastModified = Storage::disk($this->disk)->lastModified($file);
                
                if ($lastModified < $threshold) {
                    Storage::disk($this->disk)->delete($file);
                    $deleted++;
                }
            }

            if ($deleted > 0) {
                Log::info("🧹 Nettoyage: {$deleted} fichiers supprimés");
            }

            return $deleted;

        } catch (Exception $e) {
            Log::error("Erreur nettoyage: " . $e->getMessage());
            return 0;
        }
    }
}
