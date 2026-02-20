<?php

namespace App\Services;

use App\Models\Depense;
use App\Models\Dossier;
use App\Models\Exercice;
use Illuminate\Support\Facades\Cache;

class FinancialAnalyticsService
{
    private int $cacheTtl = 300;

    public function getTotalByType(int $exerciceId, string $type): float
    {
        return Cache::remember("analytics.total.{$exerciceId}.{$type}", $this->cacheTtl, fn () =>
            Dossier::parExercice($exerciceId)
                ->whereHas('depense', fn ($q) => $q->where('type', $type))
                ->sum('montant_engage')
        );
    }

    public function getMonthlyTrend(int $exerciceId): array
    {
        return Cache::remember("analytics.monthly.{$exerciceId}", $this->cacheTtl, function () use ($exerciceId) {
            $data = Dossier::parExercice($exerciceId)
                ->selectRaw('MONTH(date_dossier) as mois, SUM(montant_engage) as total')
                ->groupBy('mois')->orderBy('mois')->pluck('total', 'mois')->toArray();

            $noms = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
            $result = [];
            for ($m = 1; $m <= 12; $m++) {
                $result[] = ['mois' => $noms[$m-1], 'total' => (float)($data[$m] ?? 0)];
            }
            return $result;
        });
    }

    public function getTopImputations(int $exerciceId, int $limit = 10): array
    {
        return Cache::remember("analytics.top_imp.{$exerciceId}.{$limit}", $this->cacheTtl, fn () =>
            Dossier::parExercice($exerciceId)
                ->join('imputations', 'dossiers.imputation_id', '=', 'imputations.id')
                ->selectRaw('imputations.compte, imputations.libelle, SUM(dossiers.montant_engage) as total')
                ->groupBy('imputations.id', 'imputations.compte', 'imputations.libelle')
                ->orderByDesc('total')->limit($limit)->get()
                ->map(fn ($r) => ['label' => "{$r->compte} - {$r->libelle}", 'total' => (float)$r->total])
                ->toArray()
        );
    }

    public function getComparisonNvsN1(int $exerciceId): array
    {
        return Cache::remember("analytics.comp.{$exerciceId}", $this->cacheTtl, function () use ($exerciceId) {
            $ex = Exercice::find($exerciceId);
            if (!$ex) return [];
            $prev = Exercice::where('annee', $ex->annee - 1)->first();

            $cur = $this->getMonthlySums($exerciceId);
            $prv = $prev ? $this->getMonthlySums($prev->id) : [];

            $noms = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
            $result = [];
            for ($m = 1; $m <= 12; $m++) {
                $result[] = ['mois' => $noms[$m-1], 'courant' => (float)($cur[$m] ?? 0), 'precedent' => (float)($prv[$m] ?? 0)];
            }
            return $result;
        });
    }

    public function getCompletionRate(int $exerciceId): float
    {
        $total = Dossier::parExercice($exerciceId)->count();
        if ($total === 0) return 0;
        return round((Dossier::parExercice($exerciceId)->avecPdf()->count() / $total) * 100, 1);
    }

    public function getCreationTrend(int $exerciceId): array
    {
        return Cache::remember("analytics.creation.{$exerciceId}", $this->cacheTtl, function () use ($exerciceId) {
            $data = Dossier::parExercice($exerciceId)
                ->selectRaw('MONTH(created_at) as mois, COUNT(*) as total')
                ->groupBy('mois')->orderBy('mois')->pluck('total', 'mois')->toArray();

            $noms = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc'];
            $result = [];
            for ($m = 1; $m <= 12; $m++) {
                $result[] = ['mois' => $noms[$m-1], 'total' => (int)($data[$m] ?? 0)];
            }
            return $result;
        });
    }

    public function getKpiSummary(int $exerciceId): array
    {
        $total = Dossier::parExercice($exerciceId)->count();
        $avecPdf = Dossier::parExercice($exerciceId)->avecPdf()->count();

        return [
            'total_dossiers' => $total,
            'avec_pdf' => $avecPdf,
            'sans_pdf' => Dossier::parExercice($exerciceId)->sansPdf()->count(),
            'montant_total' => Dossier::parExercice($exerciceId)->sum('montant_engage'),
            'montant_investissement' => $this->getTotalByType($exerciceId, Depense::TYPE_INVESTISSEMENT),
            'montant_fonctionnement' => $this->getTotalByType($exerciceId, Depense::TYPE_FONCTIONNEMENT),
            'taux_completude' => $total > 0 ? round(($avecPdf / $total) * 100, 1) : 0,
            'cloud_synced' => Dossier::parExercice($exerciceId)->cloudSynced()->count(),
            'cloud_total' => $avecPdf,
            'dernier_dossier' => Dossier::parExercice($exerciceId)->latest()->first(),
        ];
    }

    public function getVariation(float $current, float $previous): array
    {
        if ($previous == 0) return ['pourcentage' => $current > 0 ? 100 : 0, 'direction' => $current > 0 ? 'up' : 'stable', 'couleur' => $current > 0 ? 'success' : 'gray'];
        $v = round((($current - $previous) / $previous) * 100, 1);
        return ['pourcentage' => abs($v), 'direction' => $v > 0 ? 'up' : ($v < 0 ? 'down' : 'stable'), 'couleur' => $v > 0 ? 'success' : ($v < 0 ? 'danger' : 'gray')];
    }

    private function getMonthlySums(int $exerciceId): array
    {
        return Dossier::parExercice($exerciceId)
            ->selectRaw('MONTH(date_dossier) as mois, SUM(montant_engage) as total')
            ->groupBy('mois')->pluck('total', 'mois')->toArray();
    }
}
