<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('dossiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('depense_id')->constrained('depenses');
            $table->foreignId('exercice_id')->constrained('exercices');
            $table->foreignId('imputation_id')->constrained('imputations');
            $table->string('ordre_paiement', 50)->unique();
            $table->date('date_dossier');
            $table->string('beneficiaire');
            $table->decimal('montant_engage', 15, 2);
            $table->text('observations')->nullable();
            $table->string('fichier_path', 500)->nullable();
            $table->string('cloud_path', 500)->nullable();
            $table->timestamp('cloud_synced_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('date_dossier');
            $table->index('beneficiaire');
            $table->index(['depense_id', 'exercice_id']);
        });
    }
    public function down(): void { Schema::dropIfExists('dossiers'); }
};
