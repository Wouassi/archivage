<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Depense extends Model
{
    use HasFactory;

    const TYPE_INVESTISSEMENT = 'INVESTISSEMENT';
    const TYPE_FONCTIONNEMENT = 'FONCTIONNEMENT';
    const CLASSE_INVESTISSEMENT = '2';
    const CLASSE_FONCTIONNEMENT = '6';

    protected $fillable = ['libelle', 'type', 'classe', 'description'];

    protected static function boot()
    {
        parent::boot();
        static::creating(fn ($d) => $d->classe = self::getClasseForType($d->type));
        static::updating(fn ($d) => $d->classe = self::getClasseForType($d->type));
    }

    public static function getClasseForType(string $type): string
    {
        return match ($type) {
            self::TYPE_INVESTISSEMENT => self::CLASSE_INVESTISSEMENT,
            self::TYPE_FONCTIONNEMENT => self::CLASSE_FONCTIONNEMENT,
            default => throw new \InvalidArgumentException("Type invalide : {$type}"),
        };
    }

    public static function getTypes(): array
    {
        return [
            self::TYPE_INVESTISSEMENT => 'Investissement (Classe 2)',
            self::TYPE_FONCTIONNEMENT => 'Fonctionnement (Classe 6)',
        ];
    }

    public function imputations() { return $this->hasMany(Imputation::class); }
    public function dossiers() { return $this->hasMany(Dossier::class); }
}
