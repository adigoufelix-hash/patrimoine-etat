<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('affectations', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('bien_id')->index();   // référence au service Biens, sans clé étrangère
        $table->foreignId('service_id')->constrained('services');
        $table->date('date_debut');
        $table->date('date_fin')->nullable();             // vide = affectation active
        $table->string('motif')->nullable();
        $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('affectations');
    }
};
