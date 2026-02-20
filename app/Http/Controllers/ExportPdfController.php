<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Depense;
use App\Models\Dossier;
use App\Models\Exercice;
use App\Models\Imputation;
use App\Models\Parametre;
use App\Models\User;
use App\Services\WorkContextService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;

class ExportPdfController extends Controller
{
    /**
     * Route: /export/pdf/{type}
     * Génère un PDF pour la liste demandée
     */
    public function export(Request $request, string $type)
    {
        $data = match ($type) {
            'dossiers' => $this->exportDossiers(),
            'depenses' => $this->exportDepenses(),
            'exercices' => $this->exportExercices(),
            'imputations' => $this->exportImputations(),
            'users' => $this->exportUsers(),
            'parametres' => $this->exportParametres(),
            'activity_logs' => $this->exportActivityLogs(),
            default => abort(404, 'Type d\'export inconnu'),
        };

        $html = $this->buildHtml($data['title'], $data['headers'], $data['rows'], $data['summary'] ?? null);

        // Utiliser wkhtmltopdf si disponible, sinon HTML direct
        $filename = "export_{$type}_" . date('Y-m-d_His') . '.pdf';
        $tmpHtml = storage_path("app/private/export_{$type}.html");
        $tmpPdf = storage_path("app/private/{$filename}");

        file_put_contents($tmpHtml, $html);

        // Essayer wkhtmltopdf
        $wk = $this->findWkhtmltopdf();
        if ($wk) {
            exec("\"{$wk}\" --page-size A4 --orientation Landscape --margin-top 10 --margin-bottom 10 --margin-left 10 --margin-right 10 --encoding utf-8 \"{$tmpHtml}\" \"{$tmpPdf}\" 2>&1", $out, $code);
            if ($code === 0 && file_exists($tmpPdf)) {
                @unlink($tmpHtml);
                return response()->download($tmpPdf, $filename)->deleteFileAfterSend();
            }
        }

        // Fallback : retourner le HTML en téléchargement
        @unlink($tmpHtml);
        $htmlFilename = "export_{$type}_" . date('Y-m-d_His') . '.html';
        return Response::make($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
            'Content-Disposition' => "attachment; filename=\"{$htmlFilename}\"",
        ]);
    }

    // ═══════════ EXPORTS PAR TYPE ═══════════

    private function exportDossiers(): array
    {
        $query = Dossier::with(['depense', 'exercice', 'imputation'])->orderByDesc('date_dossier');

        $exId = WorkContextService::getExerciceId();
        if ($exId) $query->where('exercice_id', $exId);

        $depId = WorkContextService::getDepenseId();
        if ($depId) $query->where('depense_id', $depId);

        $dossiers = $query->get();
        $total = $dossiers->sum('montant_engage');

        return [
            'title' => 'Liste des Dossiers Comptables — ' . WorkContextService::getSummary(),
            'headers' => ['N° OP', 'Type', 'Exercice', 'Imputation', 'Bénéficiaire', 'Date', 'Montant (FCFA)', 'PDF'],
            'rows' => $dossiers->map(fn ($d) => [
                $d->ordre_paiement,
                $d->depense?->type ?? '-',
                $d->exercice?->annee ?? '-',
                $d->imputation ? $d->imputation->compte . ' - ' . substr($d->imputation->libelle, 0, 20) : '-',
                $d->beneficiaire,
                $d->date_dossier?->format('d/m/Y') ?? '-',
                number_format($d->montant_engage, 0, ',', ' '),
                $d->fichier_path ? '✓' : '✗',
            ])->toArray(),
            'summary' => 'Total : ' . number_format($total, 0, ',', ' ') . ' FCFA — ' . $dossiers->count() . ' dossier(s)',
        ];
    }

    private function exportDepenses(): array
    {
        $depenses = Depense::withCount(['imputations', 'dossiers'])->orderBy('type')->get();
        return [
            'title' => 'Liste des Catégories de Dépenses',
            'headers' => ['Libellé', 'Type', 'Classe', 'Imputations', 'Dossiers'],
            'rows' => $depenses->map(fn ($d) => [$d->libelle, $d->type, $d->classe, $d->imputations_count, $d->dossiers_count])->toArray(),
        ];
    }

    private function exportExercices(): array
    {
        $exercices = Exercice::withCount('dossiers')->orderByDesc('annee')->get();
        return [
            'title' => 'Liste des Exercices Budgétaires',
            'headers' => ['Année', 'Statut', 'Début', 'Fin', 'Dossiers'],
            'rows' => $exercices->map(fn ($e) => [$e->annee, $e->statut, $e->date_debut?->format('d/m/Y'), $e->date_fin?->format('d/m/Y'), $e->dossiers_count])->toArray(),
        ];
    }

    private function exportImputations(): array
    {
        $imputations = Imputation::with('depense')->withCount('dossiers')->orderBy('compte')->get();
        return [
            'title' => 'Liste des Imputations Budgétaires',
            'headers' => ['Compte', 'Libellé', 'Dépense', 'Type', 'Dossiers'],
            'rows' => $imputations->map(fn ($i) => [$i->compte, $i->libelle, $i->depense?->libelle ?? '-', $i->depense?->type ?? '-', $i->dossiers_count])->toArray(),
        ];
    }

    private function exportUsers(): array
    {
        $users = User::with('roles')->get();
        return [
            'title' => 'Liste des Utilisateurs',
            'headers' => ['Nom', 'Email', 'Rôle', 'Actif', 'Créé le'],
            'rows' => $users->map(fn ($u) => [$u->name, $u->email, $u->roles->pluck('name')->join(', '), $u->active ? 'Oui' : 'Non', $u->created_at?->format('d/m/Y')])->toArray(),
        ];
    }

    private function exportParametres(): array
    {
        $params = Parametre::all();
        return [
            'title' => 'Paramètres de l\'application',
            'headers' => ['Clé', 'Valeur', 'Type', 'Description'],
            'rows' => $params->map(fn ($p) => [$p->cle, substr($p->valeur, 0, 50), $p->type, substr($p->description ?? '', 0, 40)])->toArray(),
        ];
    }

    private function exportActivityLogs(): array
    {
        $logs = ActivityLog::with('user')->latest()->limit(200)->get();
        return [
            'title' => 'Journal d\'activité (200 dernières entrées)',
            'headers' => ['Date', 'Utilisateur', 'Action', 'Description', 'Résultat'],
            'rows' => $logs->map(fn ($l) => [$l->created_at?->format('d/m/Y H:i'), $l->user?->name ?? '-', $l->action, substr($l->description, 0, 50), $l->resultat])->toArray(),
        ];
    }

    // ═══════════ HTML BUILDER ═══════════

    private function buildHtml(string $title, array $headers, array $rows, ?string $summary = null): string
    {
        $cabinet = Parametre::get('app.nom_cabinet', 'Cabinet Comptable');
        $date = now()->format('d/m/Y à H:i');
        $headerHtml = implode('', array_map(fn ($h) => "<th>{$h}</th>", $headers));
        $rowsHtml = '';
        foreach ($rows as $idx => $row) {
            $bg = $idx % 2 === 0 ? '#ffffff' : '#f8fafc';
            $cells = implode('', array_map(fn ($c) => "<td>{$c}</td>", $row));
            $rowsHtml .= "<tr style=\"background:{$bg}\">{$cells}</tr>";
        }
        $summaryHtml = $summary ? "<div class=\"summary\">{$summary}</div>" : '';

        return <<<HTML
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<title>{$title}</title>
<style>
@page{size:A4 landscape;margin:10mm}
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:'Segoe UI',Arial,sans-serif;font-size:10px;color:#1e293b;padding:15px}
.header{display:flex;justify-content:space-between;align-items:flex-end;border-bottom:3px solid #4f46e5;padding-bottom:10px;margin-bottom:15px}
.header h1{font-size:14px;color:#312e81;font-weight:700}
.header .meta{text-align:right;font-size:8px;color:#64748b}
.cameroun-bar{height:3px;background:linear-gradient(90deg,#009639 33.33%,#CE1126 33.33%,#CE1126 66.66%,#FCD116 66.66%);margin-bottom:12px}
table{width:100%;border-collapse:collapse;margin-bottom:12px}
th{background:#4f46e5;color:white;padding:6px 8px;font-size:9px;text-transform:uppercase;letter-spacing:0.05em;text-align:left;font-weight:600}
td{padding:5px 8px;border-bottom:1px solid #e2e8f0;font-size:9px}
tr:hover td{background:#eef2ff !important}
.summary{margin-top:10px;padding:8px 12px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:6px;font-weight:700;font-size:10px;color:#166534}
.footer{margin-top:15px;text-align:center;font-size:7px;color:#94a3b8;border-top:1px solid #e2e8f0;padding-top:8px}
</style>
</head>
<body>
<div class="cameroun-bar"></div>
<div class="header">
    <div>
        <h1>📋 {$title}</h1>
        <div style="font-size:9px;color:#64748b;margin-top:3px">{$cabinet}</div>
    </div>
    <div class="meta">Exporté le {$date}<br>ArchiCompta Pro</div>
</div>
<table>
<thead><tr>{$headerHtml}</tr></thead>
<tbody>{$rowsHtml}</tbody>
</table>
{$summaryHtml}
<div class="footer">{$cabinet} — Document généré par ArchiCompta Pro — {$date} — Page 1</div>
</body>
</html>
HTML;
    }

    private function findWkhtmltopdf(): ?string
    {
        $paths = [
            'C:\\Program Files\\wkhtmltopdf\\bin\\wkhtmltopdf.exe',
            'C:\\wkhtmltopdf\\bin\\wkhtmltopdf.exe',
            '/usr/local/bin/wkhtmltopdf',
            '/usr/bin/wkhtmltopdf',
        ];
        foreach ($paths as $p) {
            if (file_exists($p)) return $p;
        }
        // Check PATH
        $which = PHP_OS_FAMILY === 'Windows' ? 'where wkhtmltopdf 2>NUL' : 'which wkhtmltopdf 2>/dev/null';
        $result = trim(shell_exec($which) ?? '');
        return $result ?: null;
    }
}
