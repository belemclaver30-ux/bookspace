<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Profil Gérant (TDR §7) : un utilisateur `role = gerant` possède une fiche
 * gérant soumise à validation par l'administrateur de la plateforme.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gerants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('raison_sociale');
            $table->string('telephone_pro', 30)->nullable();
            $table->string('ville')->nullable();
            $table->enum('statut_validation', ['en_attente', 'valide', 'rejete'])->default('en_attente');
            $table->text('motif_rejet')->nullable();
            $table->foreignId('valide_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('valide_at')->nullable();
            $table->boolean('abonnement_premium')->default(false);
            $table->timestamp('abonnement_expire_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gerants');
    }
};
