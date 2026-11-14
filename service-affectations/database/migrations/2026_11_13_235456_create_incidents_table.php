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
        Schema::create('incidents', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('bien_id')->index();
        $table->unsignedBigInteger('signale_par')->nullable();
        $table->text('description');
        $table->string('statut')->default('ouvert');
        $table->date('date_signalement');
        $table->timestamps();
});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
