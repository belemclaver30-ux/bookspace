<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Demandes du client sur une réservation confirmée (annulation ou report),
 * soumises à la validation du gérant (TDR §5.1 « annuler … selon les conditions
 * définies par le gérant »).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('demandes_modification', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('demandeur_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', ['annulation', 'report']);
            $table->enum('statut', ['en_attente', 'acceptee', 'refusee', 'retiree'])->default('en_attente');
            $table->text('motif')->nullable();                       // raison invoquée par le client
            $table->dateTime('nouvelle_date_debut')->nullable();     // créneau proposé (report)
            $table->decimal('nouvelle_duree_heures', 4, 2)->nullable();
            $table->text('reponse_gerant')->nullable();
            $table->foreignId('traite_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('traitee_at')->nullable();
            $table->timestamps();

            $table->index(['reservation_id', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('demandes_modification');
    }
};
