<?php

namespace App\Filament\Widgets;

use App\Models\Dossier;
use App\Models\Exercice;
use Filament\Widgets\Widget;

/**
 * Widget de comparaison de 2 exercices budgétaires.
 *
 * Affiche côte à côte : nombre de dossiers, montants,
 * taux d'archivage, et la variation en %.
 */
class ExerciceComparisonWidget extends Widget
{
    protected static string $view = 'filament.widgets.exercice-comparison';

    protected int | string | array $columnSpan = 'full';

    protected static ?int $sort = 7;

    public ?int $exercice1Id = null;
    public ?int $exercice2Id = null;
    public array $comparison = [];

    public function mount(): void
    {
        // Par défaut : les 2 derniers exercices
        $exercices = Exercice::orderByDesc('annee')->take(2)->get();

        if ($exercices->count() >= 2) {
            $this->exercice2Id = $exercices[0]->id; // Plus récent
            $this->exercice1Id = $exercices[1]->id; // Précédent
            $this->loadComparison();
        }
    }

    public function updatedExercice1Id(): void { $this->loadComparison(); }
    public function updatedExercice2Id(): void { $this->loadComparison(); }

    public function loadComparison(): void
    {
        if (!$this->exercice1Id || !$this->exercice2Id) {
            $this->comparison = [];
            return;
        }

        $this->comparison = [
            'ex1' => $this->getStats($this->exercice1Id),
            'ex2' => $this->getStats($this->exercice2Id),
        ];
    }

    private function getStats(int $exerciceId): array
    {
        $exercice = Exercice::find($exerciceId);
        if (!$exercice) return [];

        $dossiers = Dossier::where('exercice_id', $exerciceId);
        $total = $dossiers->count();
        $montant = $dossiers->sum('montant_engage');
        $avecPdf = (clone $dossiers)->whereNotNull('fichier_path')->count();
        $taux = $total > 0 ? round(($avecPdf / $total) * 100, 1) : 0;

        // Par type
        $invest = Dossier::where('exercice_id', $exerciceId)
            ->whereHas('depense', fn ($q) => $q->where('type', 'INVESTISSEMENT'));
        $fonct = Dossier::where('exercice_id', $exerciceId)
            ->whereHas('depense', fn ($q) => $q->where('type', 'FONCTIONNEMENT'));

        return [
            'annee'         => $exercice->annee,
            'statut'        => $exercice->statut,
            'total'         => $total,
            'montant'       => $montant,
            'avec_pdf'      => $avecPdf,
            'sans_pdf'      => $total - $avecPdf,
            'taux_archivage'=> $taux,
            'invest_count'  => $invest->count(),
            'invest_montant'=> $invest->sum('montant_engage'),
            'fonct_count'   => $fonct->count(),
            'fonct_montant' => $fonct->sum('montant_engage'),
        ];
    }

    public static function getExerciceOptions(): array
    {
        return Exercice::orderByDesc('annee')
            ->pluck('annee', 'id')
            ->map(fn ($a) => "Exercice {$a}")
            ->toArray();
    }
}
