<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Imputation extends Model
{
    use HasFactory;

    protected $fillable = ['depense_id', 'libelle', 'compte'];
    protected $appends = ['formatted_compte'];

    protected static function boot()
    {
        parent::boot();

        $validate = function ($imputation) {
            // Contrainte : le compte doit faire exactement 6 chiffres
            if (!preg_match('/^\d{6}$/', $imputation->compte)) {
                throw new \InvalidArgumentException(
                    "Le compte budgétaire doit être composé de exactement 6 chiffres. Reçu : {$imputation->compte}"
                );
            }

            // Validation croisée : le 1er chiffre doit correspondre à la classe de la dépense
            if ($imputation->depense_id && $imputation->compte) {
                $depense = Depense::find($imputation->depense_id);
                if ($depense && substr($imputation->compte, 0, 1) !== $depense->classe) {
                    throw new \InvalidArgumentException(
                        "Incohérence : le compte {$imputation->compte} commence par " . substr($imputation->compte, 0, 1)
                        . " mais la dépense \"{$depense->libelle}\" exige la classe {$depense->classe}."
                    );
                }
            }
        };

        static::creating($validate);
        static::updating($validate);
    }

    public function getFormattedCompteAttribute(): string
    {
        return "{$this->compte} — {$this->libelle}";
    }

    public function depense() { return $this->belongsTo(Depense::class); }
    public function dossiers() { return $this->hasMany(Dossier::class); }
}
