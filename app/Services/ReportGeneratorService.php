<?php

namespace App\Services;

use App\Models\Dossier;
use App\Models\Exercice;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

/**
 * Génération automatique de rapports PDF.
 *
 * Génère un rapport complet par exercice contenant :
 *   - Résumé global (nombre de dossiers, montant total, taux archivage)
 *   - Ventilation par type (Investissement vs Fonctionnement)
 *   - Top 10 bénéficiaires
 *   - Dossiers sans PDF (à compléter)
 *   - Évolution mensuelle
 *
 * Utilise Ghostscript ou PHP pur — aucune dépendance externe.
 */
class ReportGeneratorService
{
    public static function generate(int $exerciceId): ?string
    {
        $exercice = Exercice::find($exerciceId);
        if (!$exercice) return null;

        $dossiers = Dossier::where('exercice_id', $exerciceId)
            ->with(['depense', 'imputation', 'exercice'])
            ->orderBy('date_dossier')
            ->get();

        if ($dossiers->isEmpty()) return null;

        // ═══ CALCULS ═══
        $stats = self::computeStats($dossiers);

        // ═══ GÉNÉRER LE HTML DU RAPPORT ═══
        $html = self::buildHtml($exercice, $dossiers, $stats);

        // ═══ CONVERTIR EN PDF ═══
        $relDir = "RAPPORTS/{$exercice->annee}";
        $absDir = Storage::disk('public')->path($relDir);
        if (!is_dir($absDir)) mkdir($absDir, 0755, true);

        $filename = "Rapport_Exercice_{$exercice->annee}_" . now()->format('Ymd_Hi') . ".pdf";
        $absPath = $absDir . DIRECTORY_SEPARATOR . $filename;
        $relPath = "{$relDir}/{$filename}";

        // Méthode 1 : wkhtmltopdf
        $wk = self::findBin(
            ['/usr/bin/wkhtmltopdf', '/usr/local/bin/wkhtmltopdf', 'C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe'],
            PHP_OS_FAMILY === 'Windows' ? 'where wkhtmltopdf 2>NUL' : 'which wkhtmltopdf 2>/dev/null'
        );
        if ($wk) {
            $tmpHtml = $absDir . '/tmp_report.html';
            file_put_contents($tmpHtml, $html);
            exec("\"{$wk}\" --page-size A4 --margin-top 15 --margin-bottom 15 --encoding utf-8 \"{$tmpHtml}\" \"{$absPath}\" 2>&1", $out, $code);
            @unlink($tmpHtml);
            if ($code === 0 && file_exists($absPath) && filesize($absPath) > 0) {
                Log::info("[Rapport] PDF généré via wkhtmltopdf : {$relPath}");
                return $relPath;
            }
        }

        // Méthode 2 : Chrome/Chromium headless
        $chrome = self::findBin(
            ['/usr/bin/chromium-browser', '/usr/bin/google-chrome', '/usr/bin/chromium'],
            'which chromium-browser 2>/dev/null || which google-chrome 2>/dev/null'
        );
        if ($chrome) {
            $tmpHtml = $absDir . '/tmp_report.html';
            file_put_contents($tmpHtml, $html);
            exec("\"{$chrome}\" --headless --disable-gpu --print-to-pdf=\"{$absPath}\" \"{$tmpHtml}\" 2>&1", $out, $code);
            @unlink($tmpHtml);
            if ($code === 0 && file_exists($absPath) && filesize($absPath) > 0) return $relPath;
        }

        // Méthode 3 : sauver en HTML (fallback)
        $htmlPath = str_replace('.pdf', '.html', $absPath);
        $htmlRel  = str_replace('.pdf', '.html', $relPath);
        file_put_contents($htmlPath, $html);
        Log::info("[Rapport] HTML généré (installez wkhtmltopdf pour PDF) : {$htmlRel}");
        return $htmlRel;
    }

    private static function computeStats($dossiers): array
    {
        $total = $dossiers->count();
        $montantTotal = $dossiers->sum('montant_engage');
        $avecPdf = $dossiers->filter(fn ($d) => $d->pdf_exists)->count();
        $sansPdf = $total - $avecPdf;
        $tauxArchivage = $total > 0 ? round(($avecPdf / $total) * 100, 1) : 0;

        // Par type
        $parType = $dossiers->groupBy(fn ($d) => $d->depense?->type ?? 'INCONNU')->map(fn ($group) => [
            'count'   => $group->count(),
            'montant' => $group->sum('montant_engage'),
        ]);

        // Top 10 bénéficiaires
        $topBenef = $dossiers->groupBy('beneficiaire')
            ->map(fn ($group, $name) => [
                'nom'     => $name,
                'count'   => $group->count(),
                'montant' => $group->sum('montant_engage'),
            ])
            ->sortByDesc('montant')
            ->take(10)
            ->values();

        // Par mois
        $parMois = $dossiers->groupBy(fn ($d) => $d->date_dossier?->format('Y-m') ?? 'N/A')
            ->map(fn ($group, $mois) => [
                'mois'    => $mois,
                'count'   => $group->count(),
                'montant' => $group->sum('montant_engage'),
            ])
            ->sortKeys()
            ->values();

        // Par imputation (top 10)
        $parImputation = $dossiers->groupBy(fn ($d) => $d->imputation?->compte ?? 'N/A')
            ->map(fn ($group, $compte) => [
                'compte'  => $compte,
                'libelle' => $group->first()->imputation?->libelle ?? '',
                'count'   => $group->count(),
                'montant' => $group->sum('montant_engage'),
            ])
            ->sortByDesc('montant')
            ->take(10)
            ->values();

        // Dossiers sans PDF
        $sansPdfList = $dossiers->filter(fn ($d) => !$d->pdf_exists)->take(20);

        return compact('total', 'montantTotal', 'avecPdf', 'sansPdf', 'tauxArchivage',
                        'parType', 'topBenef', 'parMois', 'parImputation', 'sansPdfList');
    }

    private static function buildHtml(Exercice $exercice, $dossiers, array $s): string
    {
        $fmt = fn ($n) => number_format($n, 0, ',', ' ');
        $date = now()->format('d/m/Y à H:i');

        $typeRows = '';
        foreach ($s['parType'] as $type => $data) {
            $pct = $s['total'] > 0 ? round(($data['count'] / $s['total']) * 100, 1) : 0;
            $typeRows .= "<tr><td>{$type}</td><td>{$data['count']}</td><td>{$fmt($data['montant'])} FCFA</td><td>{$pct}%</td></tr>";
        }

        $benefRows = '';
        foreach ($s['topBenef'] as $i => $b) {
            $benefRows .= "<tr><td>" . ($i + 1) . "</td><td>{$b['nom']}</td><td>{$b['count']}</td><td>{$fmt($b['montant'])} FCFA</td></tr>";
        }

        $moisRows = '';
        $moisNames = ['01'=>'Janvier','02'=>'Février','03'=>'Mars','04'=>'Avril','05'=>'Mai','06'=>'Juin',
                      '07'=>'Juillet','08'=>'Août','09'=>'Septembre','10'=>'Octobre','11'=>'Novembre','12'=>'Décembre'];
        foreach ($s['parMois'] as $m) {
            $parts = explode('-', $m['mois']);
            $moisLabel = ($moisNames[$parts[1] ?? ''] ?? $m['mois']) . ' ' . ($parts[0] ?? '');
            $moisRows .= "<tr><td>{$moisLabel}</td><td>{$m['count']}</td><td>{$fmt($m['montant'])} FCFA</td></tr>";
        }

        $imputRows = '';
        foreach ($s['parImputation'] as $imp) {
            $imputRows .= "<tr><td>{$imp['compte']}</td><td>{$imp['libelle']}</td><td>{$imp['count']}</td><td>{$fmt($imp['montant'])} FCFA</td></tr>";
        }

        $sansPdfRows = '';
        foreach ($s['sansPdfList'] as $d) {
            $sansPdfRows .= "<tr><td>{$d->ordre_paiement}</td><td>{$d->beneficiaire}</td><td>{$fmt($d->montant_engage)} FCFA</td><td>" . ($d->date_dossier?->format('d/m/Y') ?? '') . "</td></tr>";
        }

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>Rapport Exercice {$exercice->annee}</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Segoe UI', Arial, sans-serif; color: #1e293b; font-size: 11px; padding: 20px 30px; }
  .header { text-align: center; border-bottom: 3px solid; border-image: linear-gradient(90deg, #009639, #CE1126, #FCD116) 1; padding-bottom: 15px; margin-bottom: 20px; }
  .header h1 { font-size: 22px; color: #0f1d3a; margin-bottom: 4px; }
  .header .sub { color: #64748b; font-size: 12px; }
  .kpi-grid { display: flex; gap: 10px; margin-bottom: 20px; }
  .kpi { flex: 1; background: #f8fafc; border-radius: 8px; padding: 12px; text-align: center; border-top: 3px solid #6366f1; }
  .kpi:nth-child(2) { border-top-color: #10b981; }
  .kpi:nth-child(3) { border-top-color: #f43f5e; }
  .kpi:nth-child(4) { border-top-color: #d4a853; }
  .kpi .val { font-size: 20px; font-weight: 800; color: #0f172a; }
  .kpi .lbl { font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
  h2 { font-size: 14px; color: #0f1d3a; margin: 18px 0 8px; padding-bottom: 4px; border-bottom: 2px solid #e2e8f0; }
  table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
  th { background: #0f1d3a; color: #fff; padding: 6px 8px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.05em; }
  td { padding: 5px 8px; border-bottom: 1px solid #e2e8f0; font-size: 10.5px; }
  tr:nth-child(even) { background: #f8fafc; }
  .footer { margin-top: 20px; text-align: center; color: #94a3b8; font-size: 9px; border-top: 1px solid #e2e8f0; padding-top: 8px; }
  .badge { display: inline-block; padding: 2px 8px; border-radius: 4px; font-size: 9px; font-weight: 700; }
  .badge-danger { background: #fef2f2; color: #ef4444; }
  .badge-success { background: #f0fdf4; color: #10b981; }
  @media print { body { padding: 10px; } .kpi-grid { display: flex; } }
</style>
</head>
<body>
<div class="header">
  <h1>📊 RAPPORT D'EXERCICE {$exercice->annee}</h1>
  <div class="sub">ArchiCompta Pro — Généré le {$date}</div>
</div>

<div class="kpi-grid">
  <div class="kpi"><div class="val">{$s['total']}</div><div class="lbl">Dossiers</div></div>
  <div class="kpi"><div class="val">{$fmt($s['montantTotal'])} FCFA</div><div class="lbl">Montant total</div></div>
  <div class="kpi"><div class="val">{$s['tauxArchivage']}%</div><div class="lbl">Archivés (PDF)</div></div>
  <div class="kpi"><div class="val">{$s['sansPdf']}</div><div class="lbl">Sans PDF</div></div>
</div>

<h2>📁 Ventilation par type de dépense</h2>
<table><tr><th>Type</th><th>Dossiers</th><th>Montant</th><th>%</th></tr>{$typeRows}</table>

<h2>🏆 Top 10 bénéficiaires</h2>
<table><tr><th>#</th><th>Bénéficiaire</th><th>Dossiers</th><th>Montant</th></tr>{$benefRows}</table>

<h2>📅 Évolution mensuelle</h2>
<table><tr><th>Mois</th><th>Dossiers</th><th>Montant</th></tr>{$moisRows}</table>

<h2>🔢 Top 10 imputations</h2>
<table><tr><th>Compte</th><th>Libellé</th><th>Dossiers</th><th>Montant</th></tr>{$imputRows}</table>

<h2>⚠️ Dossiers sans PDF ({$s['sansPdf']})</h2>
<table><tr><th>N° OP</th><th>Bénéficiaire</th><th>Montant</th><th>Date</th></tr>{$sansPdfRows}</table>

<div class="footer">
  ArchiCompta Pro v1.0 — Rapport automatique — Exercice {$exercice->annee}
  <br>Période : {$exercice->date_debut?->format('d/m/Y')} au {$exercice->date_fin?->format('d/m/Y')}
</div>
</body>
</html>
HTML;
    }

    private static function findBin(array $paths, string $whichCmd): ?string
    {
        foreach ($paths as $p) { if (file_exists($p)) return $p; }
        $r = trim((string)(shell_exec($whichCmd) ?? ''));
        return ($r && file_exists($r)) ? $r : null;
    }
}
