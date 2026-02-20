<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parametre extends Model
{
    use HasFactory;

    protected $fillable = ['cle', 'valeur', 'type', 'description'];

    public static function get(string $cle, mixed $default = null): mixed
    {
        $param = static::where('cle', $cle)->first();
        if (!$param) return $default;

        return match ($param->type) {
            'boolean' => filter_var($param->valeur, FILTER_VALIDATE_BOOLEAN),
            'json' => json_decode($param->valeur, true),
            'integer' => (int) $param->valeur,
            'float' => (float) $param->valeur,
            default => $param->valeur,
        };
    }

    public static function set(string $cle, mixed $valeur, ?string $type = null, ?string $description = null): self
    {
        if ($type === null) {
            $type = match (true) {
                is_bool($valeur) => 'boolean', is_array($valeur) => 'json',
                is_int($valeur) => 'integer', is_float($valeur) => 'float', default => 'string',
            };
        }

        $valeurStr = match ($type) {
            'boolean' => $valeur ? 'true' : 'false',
            'json' => json_encode($valeur),
            default => (string) $valeur,
        };

        return static::updateOrCreate(['cle' => $cle], array_filter([
            'valeur' => $valeurStr, 'type' => $type, 'description' => $description,
        ]));
    }

    public static function getTypes(): array
    {
        return ['string' => 'Texte', 'boolean' => 'Booléen', 'json' => 'JSON', 'integer' => 'Entier', 'float' => 'Décimal'];
    }
}
