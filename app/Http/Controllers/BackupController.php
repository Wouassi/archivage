<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

/**
 * BackupController — Export BDD + DOSSIER_ARCHIVE en ZIP
 *
 * Route : GET /admin/backup/download
 *
 * Crée un ZIP contenant :
 *   1. dump_archivage_YYYY-MM-DD.sql  (export mysqldump de la BDD)
 *   2. DOSSIER_ARCHIVE/               (tous les PDF archivés)
 *
 * Le ZIP est streamé directement au navigateur (pas stocké sur le serveur).
 */
class BackupController extends Controller
{
    public function download(Request $request): StreamedResponse|\Illuminate\Http\RedirectResponse
    {
        // Vérifier que ZipArchive est disponible
        if (!class_exists(ZipArchive::class)) {
            return back()->with('notification', [
                'title' => '❌ Extension ZIP manquante',
                'body'  => 'Installez l\'extension PHP zip : sudo apt install php-zip',
                'type'  => 'danger',
            ]);
        }

        $timestamp = now()->format('Y-m-d_H-i');
        $zipFilename = "ArchiCompta_Backup_{$timestamp}.zip";
        $tmpZipPath  = storage_path("app/temp_{$timestamp}.zip");

        try {
            $zip = new ZipArchive();

            if ($zip->open($tmpZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new \Exception("Impossible de créer le fichier ZIP");
            }

            // ═══════════════════════════════════════════════════════════
            // 1. EXPORT BASE DE DONNÉES (mysqldump)
            // ═══════════════════════════════════════════════════════════
            $sqlFile = $this->exportDatabase($timestamp);
            if ($sqlFile && file_exists($sqlFile)) {
                $zip->addFile($sqlFile, basename($sqlFile));
                Log::info("[Backup] BDD exportée : " . basename($sqlFile));
            } else {
                // Fallback : exporter via PHP si mysqldump n'est pas disponible
                $sqlContent = $this->exportDatabasePhp();
                if ($sqlContent) {
                    $zip->addFromString("dump_archivage_{$timestamp}.sql", $sqlContent);
                    Log::info("[Backup] BDD exportée via PHP");
                } else {
                    $zip->addFromString("ERREUR_BDD.txt",
                        "L'export de la base de données a échoué.\n"
                        . "Installez mysqldump ou vérifiez la configuration.\n"
                        . "Date : " . now()->format('d/m/Y H:i')
                    );
                }
            }

            // ═══════════════════════════════════════════════════════════
            // 2. DOSSIER ARCHIVE (tous les PDF)
            // ═══════════════════════════════════════════════════════════
            $archiveDir = Storage::disk('public')->path('DOSSIER_ARCHIVE');

            if (is_dir($archiveDir)) {
                $this->addDirectoryToZip($zip, $archiveDir, 'DOSSIER_ARCHIVE');
                Log::info("[Backup] DOSSIER_ARCHIVE ajouté au ZIP");
            } else {
                $zip->addFromString("DOSSIER_ARCHIVE/VIDE.txt", "Aucun dossier archivé trouvé.");
            }

            // ═══════════════════════════════════════════════════════════
            // 3. MÉTADONNÉES
            // ═══════════════════════════════════════════════════════════
            $meta = "ArchiCompta Pro — Backup\n"
                  . "========================\n"
                  . "Date : " . now()->format('d/m/Y à H:i') . "\n"
                  . "Utilisateur : " . (auth()->user()->name ?? 'N/A') . "\n"
                  . "Base de données : " . config('database.connections.mysql.database', '?') . "\n"
                  . "Serveur : " . config('database.connections.mysql.host', '?') . "\n";

            $zip->addFromString("_INFO_BACKUP.txt", $meta);

            $zip->close();

            // Nettoyer le dump SQL temporaire
            if (isset($sqlFile) && file_exists($sqlFile)) {
                @unlink($sqlFile);
            }

            // ═══ Streamer le ZIP au navigateur ═══
            return response()->streamDownload(function () use ($tmpZipPath) {
                $stream = fopen($tmpZipPath, 'rb');
                while (!feof($stream)) {
                    echo fread($stream, 8192);
                    flush();
                }
                fclose($stream);
                @unlink($tmpZipPath); // Supprimer après envoi
            }, $zipFilename, [
                'Content-Type' => 'application/zip',
                'Content-Disposition' => "attachment; filename=\"{$zipFilename}\"",
            ]);

        } catch (\Throwable $e) {
            Log::error("[Backup] Erreur : " . $e->getMessage());
            @unlink($tmpZipPath);

            return back()->with('notification', [
                'title' => '❌ Erreur de backup',
                'body'  => $e->getMessage(),
                'type'  => 'danger',
            ]);
        }
    }

    /**
     * Export BDD via mysqldump
     */
    private function exportDatabase(string $timestamp): ?string
    {
        $config = config('database.connections.mysql');

        $host     = $config['host'] ?? '127.0.0.1';
        $port     = $config['port'] ?? '3306';
        $database = $config['database'] ?? '';
        $username = $config['username'] ?? '';
        $password = $config['password'] ?? '';

        if (empty($database) || empty($username)) {
            return null;
        }

        $outputFile = storage_path("app/dump_{$database}_{$timestamp}.sql");

        // Trouver mysqldump
        $mysqldump = $this->findBin([
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
            'C:\\xampp\\mysql\\bin\\mysqldump.exe',
            'C:\\wamp64\\bin\\mysql\\mysql8.0.31\\bin\\mysqldump.exe',
            'C:\\laragon\\bin\\mysql\\mysql-8.0.30-winx64\\bin\\mysqldump.exe',
        ], PHP_OS_FAMILY === 'Windows' ? 'where mysqldump 2>NUL' : 'which mysqldump 2>/dev/null');

        if (!$mysqldump) {
            Log::warning("[Backup] mysqldump non trouvé");
            return null;
        }

        // Construire la commande
        $passOption = !empty($password) ? "-p\"{$password}\"" : '';
        $cmd = "\"{$mysqldump}\" -h \"{$host}\" -P {$port} -u \"{$username}\" {$passOption} "
             . "--single-transaction --routines --triggers --add-drop-table "
             . "\"{$database}\" > \"{$outputFile}\" 2>&1";

        exec($cmd, $output, $code);

        if ($code === 0 && file_exists($outputFile) && filesize($outputFile) > 0) {
            return $outputFile;
        }

        @unlink($outputFile);
        Log::warning("[Backup] mysqldump code={$code}", ['output' => implode("\n", $output)]);
        return null;
    }

    /**
     * Fallback : export BDD via PHP PDO (structure + données basiques)
     */
    private function exportDatabasePhp(): ?string
    {
        try {
            $pdo = \DB::connection()->getPdo();
            $database = config('database.connections.mysql.database');
            $sql = "-- ArchiCompta Pro — Dump PHP\n";
            $sql .= "-- Date : " . now()->format('Y-m-d H:i:s') . "\n";
            $sql .= "-- Base : {$database}\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            // Lister les tables
            $tables = [];
            $result = $pdo->query("SHOW TABLES");
            while ($row = $result->fetch(\PDO::FETCH_NUM)) {
                $tables[] = $row[0];
            }

            foreach ($tables as $table) {
                // Structure
                $create = $pdo->query("SHOW CREATE TABLE `{$table}`")->fetch(\PDO::FETCH_NUM);
                $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
                $sql .= $create[1] . ";\n\n";

                // Données
                $rows = $pdo->query("SELECT * FROM `{$table}`");
                while ($row = $rows->fetch(\PDO::FETCH_ASSOC)) {
                    $values = array_map(function ($v) use ($pdo) {
                        return $v === null ? 'NULL' : $pdo->quote($v);
                    }, $row);
                    $cols = implode('`, `', array_keys($row));
                    $vals = implode(', ', $values);
                    $sql .= "INSERT INTO `{$table}` (`{$cols}`) VALUES ({$vals});\n";
                }
                $sql .= "\n";
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            return $sql;

        } catch (\Throwable $e) {
            Log::error("[Backup] Export PHP échoué : " . $e->getMessage());
            return null;
        }
    }

    /**
     * Ajouter un dossier récursivement au ZIP
     */
    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $zipPath): void
    {
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file->isFile()) {
                $filePath = $file->getRealPath();
                $relativePath = $zipPath . '/' . substr($filePath, strlen($dir) + 1);
                $relativePath = str_replace('\\', '/', $relativePath);
                $zip->addFile($filePath, $relativePath);
            }
        }
    }

    private function findBin(array $paths, string $whichCmd): ?string
    {
        foreach ($paths as $p) {
            if (file_exists($p)) return $p;
        }
        $r = trim((string)(shell_exec($whichCmd) ?? ''));
        return ($r && file_exists($r)) ? $r : null;
    }
}
