<?php

namespace App\Services;

use App\Models\Depense;
use App\Models\Exercice;

class WorkContextService
{
    const SESSION_EXERCICE = 'work_context.exercice_id';
    const SESSION_DEPENSE = 'work_context.depense_id';

    public static function setExercice(int $id): void
    {
        session([self::SESSION_EXERCICE => $id]);
    }

    public static function setDepense(int $id): void
    {
        session([self::SESSION_DEPENSE => $id]);
    }

    public static function getExerciceId(): ?int
    {
        $id = session(self::SESSION_EXERCICE);
        if (!$id) {
            $actif = Exercice::getActif();
            if ($actif) {
                self::setExercice($actif->id);
                return $actif->id;
            }
        }
        return $id;
    }

    public static function getDepenseId(): ?int
    {
        return session(self::SESSION_DEPENSE);
    }

    public static function getExercice(): ?Exercice
    {
        $id = self::getExerciceId();
        return $id ? Exercice::find($id) : null;
    }

    public static function getDepense(): ?Depense
    {
        $id = self::getDepenseId();
        return $id ? Depense::find($id) : null;
    }

    public static function isSet(): bool
    {
        return self::getExerciceId() !== null && self::getDepenseId() !== null;
    }

    public static function clear(): void
    {
        session()->forget([self::SESSION_EXERCICE, self::SESSION_DEPENSE]);
    }

    public static function getSummary(): string
    {
        $ex = self::getExercice();
        $dep = self::getDepense();

        if (!$ex) return 'Aucun contexte défini';

        $label = "Exercice {$ex->annee}";
        if ($dep) $label .= " • {$dep->libelle} ({$dep->type})";

        return $label;
    }
}
