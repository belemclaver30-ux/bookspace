<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Classe Paiement (TDR §7-8) : paiement de l'acompte via Mobile Money
 * (Orange Money / Moov Money). `payload` conserve la réponse brute de la
 * passerelle pour la traçabilité des transactions (TDR §9 sécurité).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->decimal('montant', 10, 2);
            $table->enum('mode', ['orange_money', 'moov_money']);
            $table->string('telephone', 30);
            $table->string('reference_operateur')->nullable()->index();
            $table->enum('statut', ['initie', 'en_cours', 'valide', 'echoue', 'rembourse'])
                ->default('initie');
            $table->json('payload')->nullable();
            $table->timestamp('paye_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paiements');
    }
};
