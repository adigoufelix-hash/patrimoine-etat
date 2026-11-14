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
        Schema::create('biens', function (Blueprint $table) {
        $table->id();
        $table->string('code_inventaire')->unique();   // RG1
        $table->string('designation');
        $table->text('description')->nullable();
        $table->date('date_acquisition')->nullable();
        $table->decimal('valeur', 12, 2)->nullable();
        $table->string('etat')->default('bon');        // neuf, bon, en_panne, reforme
        $table->foreignId('categorie_id')->constrained('categories');
        $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biens');
    }
};
