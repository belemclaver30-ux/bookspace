<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Classe Abonnement : réservation récurrente (groupe réservant le même
 * créneau hebdomadaire, plusieurs semaines de suite). Un abonnement génère
 * une Reservation par séance (table `reservations`, `abonnement_id`) ; tout
 * le cycle de vie par créneau (report, annulation unitaire, clôture…) reste
 * géré par ReservationService. L'abonnement porte le prix forfaitaire fixé
 * par le gérant et le paiement unique de l'acompte.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('abonnements', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 20)->unique();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('espace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('gerant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('formule_id')->nullable()->constrained('espace_abonnement_formules')->nullOnDelete();

            $table->unsignedSmallInteger('nombre_seances');
            $table->decimal('duree_heures', 4, 2);

            $table->decimal('prix_total', 10, 2);           // forfait fixé par le gérant
            $table->decimal('taux_acompte', 5, 2);
            $table->decimal('montant_acompte', 10, 2);
            $table->decimal('taux_commission', 5, 2);
            $table->decimal('montant_commission', 10, 2);
            $table->decimal('montant_reverse_gerant', 10, 2);

            $table->enum('statut', ['en_attente', 'confirmee', 'annulee', 'terminee'])
                ->default('en_attente');
            $table->foreignId('annulee_par')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motif_annulation')->nullable();
            $table->timestamp('confirmee_at')->nullable();
            $table->timestamp('annulee_at')->nullable();

            $table->timestamps();

            $table->index(['client_id', 'statut']);
            $table->index(['gerant_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('abonnements');
    }
};
