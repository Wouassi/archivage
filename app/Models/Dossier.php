<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Dossier extends Model
{
    use HasFactory;

    protected $fillable = [
        'depense_id', 'exercice_id', 'imputation_id', 'ordre_paiement',
        'date_dossier', 'beneficiaire', 'montant_engage', 'observations',
        'fichier_path', 'cloud_path', 'cloud_synced_at', 'created_by',
    ];

    protected function casts(): array
    {
        return [
            'date_dossier' => 'date',
            'montant_engage' => 'decimal:2',
            'cloud_synced_at' => 'datetime',
        ];
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($d) {
            if (auth()->check() && !$d->created_by) {
                $d->created_by = auth()->id();
            }
        });

        static::deleting(function ($d) {
            if ($d->fichier_path && Storage::disk('public')->exists($d->fichier_path)) {
                Storage::disk('public')->delete($d->fichier_path);
            }
            ActivityLog::logActivity('suppression', "Dossier {$d->ordre_paiement} supprimé", $d, $d->toArray());
        });
    }

    // Accesseurs
    public function getPdfExistsAttribute(): bool
    {
        return $this->fichier_path && Storage::disk('public')->exists($this->fichier_path);
    }

    public function getPdfUrlAttribute(): ?string
    {
        return $this->pdf_exists ? Storage::disk('public')->url($this->fichier_path) : null;
    }

    public function getPdfSizeAttribute(): ?string
    {
        if (!$this->pdf_exists) return null;
        $bytes = Storage::disk('public')->size($this->fichier_path);
        $units = ['o', 'Ko', 'Mo', 'Go'];
        $i = 0;
        while ($bytes >= 1024 && $i < count($units) - 1) { $bytes /= 1024; $i++; }
        return round($bytes, 2) . ' ' . $units[$i];
    }

    public function getPdfNameAttribute(): ?string
    {
        return $this->fichier_path ? basename($this->fichier_path) : null;
    }

    public function getCloudSyncedAttribute(): bool { return !is_null($this->cloud_synced_at); }

    public function getMontantFormateAttribute(): string
    {
        return number_format($this->montant_engage, 0, ',', ' ') . ' FCFA';
    }

    // Scopes
    public function scopeAvecPdf($q) { return $q->whereNotNull('fichier_path'); }
    public function scopeSansPdf($q) { return $q->whereNull('fichier_path'); }
    public function scopeCloudSynced($q) { return $q->whereNotNull('cloud_synced_at'); }
    public function scopeCloudPending($q) { return $q->whereNotNull('fichier_path')->whereNull('cloud_synced_at'); }
    public function scopeParExercice($q, $id) { return $q->where('exercice_id', $id); }
    public function scopeParType($q, string $type) { return $q->whereHas('depense', fn ($q2) => $q2->where('type', $type)); }

    // Relations
    public function depense() { return $this->belongsTo(Depense::class); }
    public function exercice() { return $this->belongsTo(Exercice::class); }
    public function imputation() { return $this->belongsTo(Imputation::class); }
    public function creator() { return $this->belongsTo(User::class, 'created_by'); }
}
