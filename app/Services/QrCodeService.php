<?php

namespace App\Services;

use App\Models\Dossier;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Génération de QR Code pour chaque dossier.
 *
 * Le QR Code contient une URL vers la page de consultation du dossier.
 * Il peut être affiché sur la page View ou imprimé avec le PDF.
 *
 * Utilise l'API Google Charts (zéro dépendance) ou
 * la lib PHP endroid/qr-code si installée.
 *
 * Installation optionnelle : composer require endroid/qr-code
 */
class QrCodeService
{
    /**
     * Générer l'URL du QR code pour un dossier.
     * Retourne une URL d'image (soit locale, soit Google Charts).
     */
    public static function getQrUrl(Dossier $dossier): string
    {
        $viewUrl = url("/admin/dossiers/{$dossier->id}");

        // Données encodées dans le QR
        $data = implode("\n", [
            "ArchiCompta Pro",
            "OP: {$dossier->ordre_paiement}",
            "Bénéficiaire: {$dossier->beneficiaire}",
            "Montant: " . number_format($dossier->montant_engage, 0, ',', ' ') . " FCFA",
            "Date: " . ($dossier->date_dossier?->format('d/m/Y') ?? 'N/A'),
            $viewUrl,
        ]);

        // Méthode 1 : endroid/qr-code (si installé)
        if (class_exists(\Endroid\QrCode\QrCode::class)) {
            return self::generateWithEndroid($dossier, $data);
        }

        // Méthode 2 : chillerlan/php-qrcode (si installé)
        if (class_exists(\chillerlan\QRCode\QRCode::class)) {
            return self::generateWithChillerlan($dossier, $data);
        }

        // Méthode 3 : Google Charts API (zéro dépendance, nécessite internet)
        return self::generateWithGoogleApi($data);
    }

    /**
     * Générer une image QR en base64 (pour inclusion inline dans HTML/PDF).
     */
    public static function getQrBase64(Dossier $dossier): ?string
    {
        $viewUrl = url("/admin/dossiers/{$dossier->id}");
        $data = $viewUrl; // QR compact → juste l'URL

        if (class_exists(\Endroid\QrCode\QrCode::class)) {
            try {
                $qr = new \Endroid\QrCode\QrCode($data);
                $qr->setSize(200);
                $qr->setMargin(10);
                $writer = new \Endroid\QrCode\Writer\PngWriter();
                $result = $writer->write($qr);
                return 'data:image/png;base64,' . base64_encode($result->getString());
            } catch (\Throwable $e) {
                Log::debug("[QR] endroid échoué: " . $e->getMessage());
            }
        }

        // Fallback Google Charts
        return self::generateWithGoogleApi($data);
    }

    /**
     * Sauver le QR Code comme fichier PNG dans storage.
     */
    public static function saveQrFile(Dossier $dossier): ?string
    {
        $url = url("/admin/dossiers/{$dossier->id}");
        $relDir = "QR_CODES";
        $absDir = Storage::disk('public')->path($relDir);
        if (!is_dir($absDir)) mkdir($absDir, 0755, true);

        $filename = "qr_dossier_{$dossier->id}.png";
        $absPath = $absDir . DIRECTORY_SEPARATOR . $filename;
        $relPath = "{$relDir}/{$filename}";

        // Essai endroid
        if (class_exists(\Endroid\QrCode\QrCode::class)) {
            try {
                $qr = new \Endroid\QrCode\QrCode($url);
                $qr->setSize(300);
                $qr->setMargin(15);
                $writer = new \Endroid\QrCode\Writer\PngWriter();
                $result = $writer->write($qr);
                file_put_contents($absPath, $result->getString());
                if (file_exists($absPath)) return $relPath;
            } catch (\Throwable) {}
        }

        // Essai Google Charts download
        $googleUrl = "https://chart.googleapis.com/chart?cht=qr&chs=300x300&chl=" . urlencode($url);
        $img = @file_get_contents($googleUrl);
        if ($img) {
            file_put_contents($absPath, $img);
            if (file_exists($absPath)) return $relPath;
        }

        return null;
    }

    private static function generateWithEndroid(Dossier $dossier, string $data): string
    {
        try {
            $qr = new \Endroid\QrCode\QrCode($data);
            $qr->setSize(200);
            $writer = new \Endroid\QrCode\Writer\PngWriter();
            $result = $writer->write($qr);
            return 'data:image/png;base64,' . base64_encode($result->getString());
        } catch (\Throwable) {
            return self::generateWithGoogleApi($data);
        }
    }

    private static function generateWithChillerlan(Dossier $dossier, string $data): string
    {
        try {
            $options = new \chillerlan\QRCode\QROptions(['outputType' => \chillerlan\QRCode\QRCode::OUTPUT_IMAGE_PNG]);
            $qr = new \chillerlan\QRCode\QRCode($options);
            return $qr->render($data);
        } catch (\Throwable) {
            return self::generateWithGoogleApi($data);
        }
    }

    private static function generateWithGoogleApi(string $data): string
    {
        return "https://chart.googleapis.com/chart?cht=qr&chs=200x200&chl=" . urlencode($data) . "&choe=UTF-8";
    }
}
