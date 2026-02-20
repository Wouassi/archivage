<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id', 'action', 'description', 'model_type', 'model_id',
        'old_values', 'new_values', 'ip_address', 'user_agent', 'resultat',
    ];

    protected function casts(): array
    {
        return ['old_values' => 'array', 'new_values' => 'array'];
    }

    public static function logActivity(
        string $action, string $description, ?Model $model = null,
        ?array $oldValues = null, ?array $newValues = null, string $resultat = 'succes'
    ): self {
        return self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->id,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'resultat' => $resultat,
        ]);
    }

    public function scopeByUser($q, int $id) { return $q->where('user_id', $id); }
    public function scopeByAction($q, string $a) { return $q->where('action', $a); }
    public function scopeSucces($q) { return $q->where('resultat', 'succes'); }
    public function scopeEchec($q) { return $q->where('resultat', 'echec'); }
    public function scopeRecent($q, int $limit = 10) { return $q->latest('created_at')->limit($limit); }

    public function user() { return $this->belongsTo(User::class); }
    public function model() { return $this->morphTo(); }
}
