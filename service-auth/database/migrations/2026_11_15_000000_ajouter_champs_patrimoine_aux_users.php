<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('responsable');      // admin, gestionnaire, responsable
            $table->unsignedBigInteger('service_id')->nullable(); // référence au service Affectations
            $table->boolean('actif')->default(true);
            $table->string('api_token', 64)->nullable()->unique(); // empreinte du jeton
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'service_id', 'actif', 'api_token']);
        });
    }
};
