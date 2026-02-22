<?php

namespace App\Services;

use App\Models\Dossier;
use Illuminate\Support\Collection;

/**
 * Détection de doublons de dossiers.
 *
 * Vérifie 3 critères :
 *   1. Même N° ordre de paiement (exact)
 *   2. Même bénéficiaire + montant + date (combinaison)
 *   3. Montant identique + même imputation dans les 7 derniers jours (suspect)
 */
class DuplicateDetectionService
{
    /**
     * Recherche les doublons potentiels AVANT création.
     *
     * @return Collection  Liste de dossiers existants suspects
     */
    public static function detect(
        ?string $ordrePaiement,
        ?string $beneficiaire,
        ?float $montant,
        ?string $dateDossier,
        ?int $imputationId = null,
        ?int $excludeId = null
    ): Collection {
        $duplicates = collect();

        // ═══ 1. Même N° OP (le plus grave) ═══
        if (!empty($ordrePaiement)) {
            $opDupes = Dossier::where('ordre_paiement', $ordrePaiement)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->with(['depense:id,type,libelle', 'exercice:id,annee'])
                ->get()
                ->map(fn ($d) => [
                    'dossier'  => $d,
                    'raison'   => "⚠️ Même N° OP : {$d->ordre_paiement}",
                    'gravite'  => 'haute',
                ]);
            $duplicates = $duplicates->merge($opDupes);
        }

        // ═══ 2. Même bénéficiaire + montant + date ═══
        if (!empty($beneficiaire) && $montant > 0 && !empty($dateDossier)) {
            $comboDupes = Dossier::where('beneficiaire', 'LIKE', $beneficiaire)
                ->where('montant_engage', $montant)
                ->where('date_dossier', $dateDossier)
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->with(['depense:id,type,libelle', 'exercice:id,annee'])
                ->get()
                ->map(fn ($d) => [
                    'dossier'  => $d,
                    'raison'   => "🔄 Même bénéficiaire + montant + date",
                    'gravite'  => 'haute',
                ]);
            $duplicates = $duplicates->merge($comboDupes);
        }

        // ═══ 3. Même montant + imputation dans les 7 derniers jours ═══
        if ($montant > 0 && $imputationId && !empty($dateDossier)) {
            $suspectDupes = Dossier::where('montant_engage', $montant)
                ->where('imputation_id', $imputationId)
                ->whereBetween('date_dossier', [
                    date('Y-m-d', strtotime($dateDossier . ' -7 days')),
                    date('Y-m-d', strtotime($dateDossier . ' +7 days')),
                ])
                ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                ->with(['depense:id,type,libelle', 'exercice:id,annee'])
                ->get()
                ->map(fn ($d) => [
                    'dossier'  => $d,
                    'raison'   => "🟡 Même montant + imputation (±7 jours)",
                    'gravite'  => 'moyenne',
                ]);
            $duplicates = $duplicates->merge($suspectDupes);
        }

        // Dédupliquer par ID
        return $duplicates->unique(fn ($item) => $item['dossier']->id)->values();
    }

    /**
     * Formater les doublons pour affichage dans une notification.
     */
    public static function formatWarning(Collection $duplicates): ?string
    {
        if ($duplicates->isEmpty()) return null;

        $lines = $duplicates->map(function ($item) {
            $d = $item['dossier'];
            $montant = number_format($d->montant_engage, 0, ',', ' ');
            return "{$item['raison']}\n   → OP: {$d->ordre_paiement} | {$d->beneficiaire} | {$montant} FCFA | " .
                   ($d->date_dossier?->format('d/m/Y') ?? '');
        });

        return "⚠️ DOUBLONS POTENTIELS DÉTECTÉS :\n\n" . $lines->implode("\n\n");
    }
}
