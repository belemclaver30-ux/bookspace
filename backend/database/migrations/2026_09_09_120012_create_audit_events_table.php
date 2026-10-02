<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Historisation des événements (TDR §10.2 : « logique d'historisation des
 * événements — créations, modifications de réservation — afin de garantir la
 * traçabilité »). Journal append-only : une ligne par changement d'état
 * significatif d'une entité métier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('auteur_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('sujet_type');
            $table->unsignedBigInteger('sujet_id');
            $table->string('action'); // creee, modifiee, confirmee, annulee, paiement_valide...
            $table->json('avant')->nullable();
            $table->json('apres')->nullable();
            $table->string('ip', 45)->nullable();
            $table->timestamp('created_at')->nullable();

            $table->index(['sujet_type', 'sujet_id']);
            $table->index('action');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_events');
    }
};
