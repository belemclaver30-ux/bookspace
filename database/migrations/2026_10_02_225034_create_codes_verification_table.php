<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Codes à usage unique envoyés par SMS : réinitialisation du mot de passe et
 * vérification d'un nouveau numéro de téléphone. Le code n'est stocké que haché.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('codes_verification', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('objet', ['reinitialisation', 'changement_telephone']);
            $table->string('telephone', 30); // numéro auquel le code a été envoyé
            $table->string('code_hash');
            $table->unsignedTinyInteger('tentatives')->default(0);
            $table->timestamp('expire_at');
            $table->timestamp('utilise_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'objet']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('codes_verification');
    }
};
