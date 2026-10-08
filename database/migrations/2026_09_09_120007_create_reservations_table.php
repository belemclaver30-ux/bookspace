<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Classe Reservation (TDR §7-8). Une réservation « concerne » un créneau
 * (date_debut/date_fin) sur un espace. Le créneau réservé bloque toute autre
 * réservation qui chevauche la même plage (critère de réception : « aucune
 * double réservation possible sur un même créneau »).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reservations', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('espace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gerant_id')->constrained()->cascadeOnDelete();

            $table->dateTime('date_debut');
            $table->dateTime('date_fin');
            $table->decimal('duree_heures', 4, 2);

            $table->decimal('prix_total', 10, 2);
            $table->decimal('taux_acompte', 5, 2);       // % figé au moment de la résa
            $table->decimal('montant_acompte', 10, 2);
            $table->decimal('taux_commission', 5, 2);      // % figé au moment de la résa
            $table->decimal('montant_commission', 10, 2);
            $table->decimal('montant_reverse_gerant', 10, 2); // acompte - commission

            $table->enum('statut', ['en_attente', 'confirmee', 'annulee', 'terminee'])
                ->default('en_attente');
            $table->foreignId('annulee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motif_annulation')->nullable();
            $table->timestamp('confirmee_at')->nullable();
            $table->timestamp('annulee_at')->nullable();

            $table->timestamps();

            $table->index(['espace_id', 'date_debut', 'date_fin']);
            $table->index(['client_id', 'statut']);
            $table->index(['gerant_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reservations');
    }
};
