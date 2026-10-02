<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Classe abstraite Espace (TDR §7), spécialisée en SalleRepetition et TerrainFoot.
 * Héritage « single table » : les attributs des deux sous-types cohabitent,
 * seuls ceux du `type` courant sont renseignés.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('espaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gerant_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['salle_repetition', 'terrain_foot']);
            $table->string('nom');
            $table->text('description')->nullable();
            $table->string('adresse');
            $table->string('ville')->index();
            $table->decimal('tarif_horaire', 10, 2);
            $table->enum('statut', ['en_attente', 'valide', 'rejete', 'suspendu'])->default('en_attente');
            $table->text('motif_rejet')->nullable();

            // Équipements communs (liste libre) + géoloc optionnelle
            $table->json('equipements')->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Spécifique SalleRepetition
            $table->unsignedInteger('capacite')->nullable();
            $table->boolean('isolation_phonique')->default(false);

            // Spécifique TerrainFoot
            $table->string('type_pelouse')->nullable();
            $table->boolean('eclairage')->default(false);
            $table->string('dimensions')->nullable();

            // Agrégats d'évaluation (dénormalisés pour la performance de recherche)
            $table->decimal('note_moyenne', 3, 2)->default(0);
            $table->unsignedInteger('nb_avis')->default(0);

            // Modèle économique : mise en avant payante
            $table->boolean('mis_en_avant')->default(false);

            $table->timestamps();
            $table->softDeletes();

            $table->index(['type', 'statut']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('espaces');
    }
};
