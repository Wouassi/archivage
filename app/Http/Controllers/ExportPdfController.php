<?php

namespace App\Http\Controllers;

use App\Models\Dossier;
use App\Models\Depense;
use App\Models\Exercice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ExportPdfController extends Controller
{
    /**
     * Exporte une liste en PDF.
     *
     * @param Request $request
     * @param string  $type    Type de liste à exporter (dossiers, depenses, exercices, etc.)
     */
    public function export(Request $request, string $type)
    {
        return match ($type) {
            'dossiers'    => $this->exportDossiers($request),
            'depenses'    => $this->exportDepenses($request),
            'exercices'   => $this->exportExercices($request),
            'imputations' => $this->exportImputations($request),
            default       => abort(404, "Type d'export inconnu : {$type}"),
        };
    }

    // ══════════════════════════════════════════════════════════════
    // EXPORT DOSSIERS
    // ══════════════════════════════════════════════════════════════
    private function exportDossiers(Request $request)
    {
        $query = Dossier::with(['depense', 'exercice', 'imputation'])
            ->orderBy('created_at', 'desc');

        // Filtres optionnels
        if ($exerciceId = $request->get('exercice_id')) {
            $query->where('exercice_id', $exerciceId);
        }
        if ($depenseId = $request->get('depense_id')) {
            $query->where('depense_id', $depenseId);
        }

        $dossiers = $query->get();

        // Informations pour le titre
        $exercice = $exerciceId ? Exercice::find($exerciceId) : null;
        $depense  = $depenseId  ? Depense::find($depenseId)   : null;

        $titre = 'Liste des Dossiers Comptables';
        $sousTitre = '';

        if ($exercice) {
            $sousTitre .= "Exercice : {$exercice->annee}";
        }
        if ($depense) {
            $sousTitre .= ($sousTitre ? ' — ' : '') . "Type : {$depense->libelle}";
        }

        // Total des montants
        $totalMontant = $dossiers->sum('montant_engage');

        $html = $this->buildHtml(
            titre: $titre,
            sousTitre: $sousTitre,
            content: $this->buildDossiersTable($dossiers, $totalMontant),
            count: $dossiers->count()
        );

        return $this->renderPdf($html, "dossiers_" . date('Ymd_His'));
    }

    /**
     * Construit le tableau HTML des dossiers.
     */
    private function buildDossiersTable($dossiers, float $totalMontant): string
    {
        $rows = '';
        $i = 0;

        foreach ($dossiers as $d) {
            $i++;
            $bg       = $i % 2 === 0 ? '#f9fafb' : '#ffffff';
            $montant  = $d->montant_engage
                ? number_format((float) $d->montant_engage, 0, ',', ' ') . ' FCFA'
                : '—';
            $type     = $d->depense?->type ?? '—';
            $annee    = $d->exercice?->annee ?? '—';
            $date     = $d->date_dossier
                ? date('d/m/Y', strtotime($d->date_dossier))
                : '—';
            $pdf      = $d->fichier_path ? '✅' : '❌';
            $imputation = $d->imputation?->compte ?? '—';

            $rows .= "
            <tr style='background-color: {$bg};'>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: center;'>{$i}</td>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-weight: bold;'>{$d->ordre_paiement}</td>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb;'>{$type}</td>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: center;'>{$annee}</td>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb;'>{$imputation}</td>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb;'>{$d->beneficiaire}</td>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: center;'>{$date}</td>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: right; font-weight: bold;'>{$montant}</td>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: center;'>{$pdf}</td>
            </tr>";
        }

        $totalFormatted = number_format($totalMontant, 0, ',', ' ') . ' FCFA';

        return "
        <table style='width: 100%; border-collapse: collapse; font-size: 11px;'>
            <thead>
                <tr style='background-color: #1e40af; color: white;'>
                    <th style='padding: 8px 6px; text-align: center; width: 30px;'>N°</th>
                    <th style='padding: 8px 6px; text-align: left;'>Ordre Paiement</th>
                    <th style='padding: 8px 6px; text-align: left;'>Type</th>
                    <th style='padding: 8px 6px; text-align: center;'>Exercice</th>
                    <th style='padding: 8px 6px; text-align: left;'>Imputation</th>
                    <th style='padding: 8px 6px; text-align: left;'>Bénéficiaire</th>
                    <th style='padding: 8px 6px; text-align: center;'>Date</th>
                    <th style='padding: 8px 6px; text-align: right;'>Montant</th>
                    <th style='padding: 8px 6px; text-align: center; width: 30px;'>PDF</th>
                </tr>
            </thead>
            <tbody>
                {$rows}
            </tbody>
            <tfoot>
                <tr style='background-color: #1e40af; color: white; font-weight: bold;'>
                    <td colspan='7' style='padding: 8px 6px; text-align: right;'>TOTAL :</td>
                    <td style='padding: 8px 6px; text-align: right;'>{$totalFormatted}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>";
    }

    // ══════════════════════════════════════════════════════════════
    // EXPORTS GÉNÉRIQUES (dépenses, exercices, imputations)
    // ══════════════════════════════════════════════════════════════
    private function exportDepenses(Request $request)
    {
        $items = Depense::orderBy('type')->orderBy('libelle')->get();

        $rows = '';
        $i = 0;
        foreach ($items as $d) {
            $i++;
            $bg = $i % 2 === 0 ? '#f9fafb' : '#ffffff';
            $rows .= "
            <tr style='background-color: {$bg};'>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: center;'>{$i}</td>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-weight: bold;'>{$d->type}</td>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb;'>{$d->libelle}</td>
            </tr>";
        }

        $table = "
        <table style='width: 100%; border-collapse: collapse; font-size: 12px;'>
            <thead>
                <tr style='background-color: #1e40af; color: white;'>
                    <th style='padding: 8px; text-align: center; width: 40px;'>N°</th>
                    <th style='padding: 8px; text-align: left;'>Type</th>
                    <th style='padding: 8px; text-align: left;'>Libellé</th>
                </tr>
            </thead>
            <tbody>{$rows}</tbody>
        </table>";

        $html = $this->buildHtml('Liste des Dépenses', '', $table, $items->count());
        return $this->renderPdf($html, "depenses_" . date('Ymd_His'));
    }

    private function exportExercices(Request $request)
    {
        $items = Exercice::orderByDesc('annee')->get();

        $rows = '';
        $i = 0;
        foreach ($items as $e) {
            $i++;
            $bg = $i % 2 === 0 ? '#f9fafb' : '#ffffff';
            $statut = ucfirst($e->statut ?? '—');
            $rows .= "
            <tr style='background-color: {$bg};'>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: center;'>{$i}</td>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-weight: bold;'>{$e->annee}</td>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb;'>{$statut}</td>
            </tr>";
        }

        $table = "
        <table style='width: 100%; border-collapse: collapse; font-size: 12px;'>
            <thead>
                <tr style='background-color: #1e40af; color: white;'>
                    <th style='padding: 8px; text-align: center; width: 40px;'>N°</th>
                    <th style='padding: 8px; text-align: left;'>Année</th>
                    <th style='padding: 8px; text-align: left;'>Statut</th>
                </tr>
            </thead>
            <tbody>{$rows}</tbody>
        </table>";

        $html = $this->buildHtml('Liste des Exercices', '', $table, $items->count());
        return $this->renderPdf($html, "exercices_" . date('Ymd_His'));
    }

    private function exportImputations(Request $request)
    {
        $items = \App\Models\Imputation::with('depense')
            ->orderBy('compte')
            ->get();

        $rows = '';
        $i = 0;
        foreach ($items as $imp) {
            $i++;
            $bg = $i % 2 === 0 ? '#f9fafb' : '#ffffff';
            $rows .= "
            <tr style='background-color: {$bg};'>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb; text-align: center;'>{$i}</td>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb; font-weight: bold;'>{$imp->compte}</td>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb;'>{$imp->libelle}</td>
                <td style='padding: 6px 8px; border-bottom: 1px solid #e5e7eb;'>" . ($imp->depense?->libelle ?? '—') . "</td>
            </tr>";
        }

        $table = "
        <table style='width: 100%; border-collapse: collapse; font-size: 12px;'>
            <thead>
                <tr style='background-color: #1e40af; color: white;'>
                    <th style='padding: 8px; text-align: center; width: 40px;'>N°</th>
                    <th style='padding: 8px; text-align: left;'>Compte</th>
                    <th style='padding: 8px; text-align: left;'>Libellé</th>
                    <th style='padding: 8px; text-align: left;'>Dépense</th>
                </tr>
            </thead>
            <tbody>{$rows}</tbody>
        </table>";

        $html = $this->buildHtml('Liste des Imputations', '', $table, $items->count());
        return $this->renderPdf($html, "imputations_" . date('Ymd_His'));
    }

    // ══════════════════════════════════════════════════════════════
    // CONSTRUCTION DU HTML + RENDU PDF
    // ══════════════════════════════════════════════════════════════

    /**
     * Construit le document HTML complet avec en-tête et pied de page.
     */
    private function buildHtml(string $titre, string $sousTitre, string $content, int $count): string
    {
        $date = date('d/m/Y à H:i');
        $user = auth()->user()->name ?? 'Système';

        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <title>{$titre}</title>
            <style>
                body {
                    font-family: 'DejaVu Sans', Arial, Helvetica, sans-serif;
                    font-size: 12px;
                    color: #1f2937;
                    margin: 0;
                    padding: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 20px;
                    border-bottom: 3px solid #1e40af;
                    padding-bottom: 15px;
                }
                .header h1 {
                    font-size: 20px;
                    color: #1e40af;
                    margin: 0 0 5px 0;
                }
                .header .subtitle {
                    font-size: 13px;
                    color: #6b7280;
                }
                .meta {
                    display: flex;
                    justify-content: space-between;
                    margin-bottom: 15px;
                    font-size: 10px;
                    color: #9ca3af;
                }
                .meta-left { text-align: left; }
                .meta-right { text-align: right; }
                .footer {
                    position: fixed;
                    bottom: 0;
                    left: 0;
                    right: 0;
                    text-align: center;
                    font-size: 9px;
                    color: #9ca3af;
                    border-top: 1px solid #e5e7eb;
                    padding: 8px 20px;
                }
                @media print {
                    body { margin: 0; padding: 15px; }
                }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>📋 {$titre}</h1>
                " . ($sousTitre ? "<div class='subtitle'>{$sousTitre}</div>" : '') . "
            </div>

            <table style='width: 100%; margin-bottom: 15px;'>
                <tr>
                    <td style='font-size: 10px; color: #6b7280;'>
                        📊 Total : <strong>{$count} enregistrement(s)</strong>
                    </td>
                    <td style='font-size: 10px; color: #6b7280; text-align: right;'>
                        📅 Généré le {$date} par {$user}
                    </td>
                </tr>
            </table>

            {$content}

            <div class='footer'>
                Système d'Archivage Comptable — Export généré le {$date}
            </div>
        </body>
        </html>";
    }

    /**
     * Rendu PDF à partir du HTML.
     * Utilise DomPDF si disponible, sinon retourne du HTML imprimable.
     */
    private function renderPdf(string $html, string $filename)
    {
        // Méthode 1 : DomPDF (paquet barryvdh/laravel-dompdf)
        if (class_exists(\Barryvdh\DomPDF\Facade\Pdf::class)) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)
                ->setPaper('a4', 'landscape')
                ->setOptions([
                    'isHtml5ParserEnabled' => true,
                    'isRemoteEnabled'      => false,
                    'defaultFont'          => 'DejaVu Sans',
                ]);

            return $pdf->download("{$filename}.pdf");
        }

        // Méthode 2 : Snappy PDF (paquet barryvdh/laravel-snappy)
        if (class_exists(\Barryvdh\Snappy\Facades\SnappyPdf::class)) {
            $pdf = \Barryvdh\Snappy\Facades\SnappyPdf::loadHTML($html)
                ->setOrientation('landscape')
                ->setPaper('a4');

            return $pdf->download("{$filename}.pdf");
        }

        // Méthode 3 : Fallback HTML imprimable
        Log::warning('[ExportPdf] Aucune bibliothèque PDF disponible — fallback HTML');

        return response($html, 200, [
            'Content-Type' => 'text/html; charset=utf-8',
        ]);
    }
}
