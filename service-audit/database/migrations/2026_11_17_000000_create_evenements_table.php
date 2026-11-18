<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evenements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('utilisateur_id')->nullable(); // référence logique vers le service Auth
            $table->string('utilisateur_nom')->nullable();
            $table->string('role', 30)->nullable();
            $table->string('action', 120)->index();
            $table->string('objet', 120)->nullable();
            $table->text('detail')->nullable();
            $table->string('adresse_ip', 45)->nullable();
            $table->timestamp('created_at')->useCurrent()->index(); // pas de updated_at : journal en ajout seul
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evenements');
    }
};
