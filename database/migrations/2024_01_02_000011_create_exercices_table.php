<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('exercices', function (Blueprint $table) {
            $table->id();
            $table->integer('annee');
            $table->date('date_debut');
            $table->date('date_fin');
            $table->string('statut')->default('actif');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->index('annee');
            $table->index('statut');
        });
    }
    public function down(): void { Schema::dropIfExists('exercices'); }
};
