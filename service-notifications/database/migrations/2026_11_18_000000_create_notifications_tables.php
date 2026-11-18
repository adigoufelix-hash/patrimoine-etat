<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('titre', 150);
            $table->text('message');
            $table->string('lien', 255)->nullable();
            $table->string('role_cible', 30)->nullable()->index();          // destinataires : un rôle...
            $table->unsignedBigInteger('utilisateur_id')->nullable()->index(); // ...ou un utilisateur (référence logique vers Auth)
            $table->timestamp('created_at')->useCurrent();
        });

        Schema::create('lectures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('notification_id')->constrained('notifications')->cascadeOnDelete();
            $table->unsignedBigInteger('utilisateur_id');
            $table->timestamp('lue_at')->useCurrent();
            $table->unique(['notification_id', 'utilisateur_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lectures');
        Schema::dropIfExists('notifications');
    }
};
