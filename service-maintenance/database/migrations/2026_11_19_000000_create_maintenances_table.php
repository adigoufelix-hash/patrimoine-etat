<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maintenances', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bien_id')->index();       // référence logique vers le service Biens
            $table->string('type', 20);                            // entretien, reparation, controle
            $table->text('description')->nullable();
            $table->date('date_prevue')->index();
            $table->date('date_realisee')->nullable();
            $table->string('statut', 20)->default('planifiee')->index(); // planifiee, realisee, annulee
            $table->text('remarque')->nullable();
            $table->unsignedBigInteger('cree_par')->nullable();    // référence logique vers le service Auth
            $table->date('alerte_le')->nullable();                 // dernière alerte envoyée (RG16)
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenances');
    }
};
