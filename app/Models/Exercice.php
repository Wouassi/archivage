<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exercice extends Model
{
    use HasFactory;

    protected $fillable = ['annee', 'date_debut', 'date_fin', 'statut', 'description'];

    protected function casts(): array
    {
        return ['date_debut' => 'date', 'date_fin' => 'date'];
    }

    public function scopeActif($query) { return $query->where('statut', 'actif'); }

    public static function getActif(): ?self { return static::actif()->first(); }

    public static function getStatuts(): array
    {
        return ['actif' => 'Actif', 'clos' => 'Clos'];
    }

    public function dossiers() { return $this->hasMany(Dossier::class); }
}
