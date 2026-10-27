<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Formules d'abonnement proposées par le gérant sur un espace : nombre de
 * séances (répétitions hebdomadaires) et prix forfaitaire — fixé librement
 * par le gérant, indépendamment du tarif horaire normal.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('espace_abonnement_formules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('espace_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('nombre_seances');
            $table->decimal('prix', 10, 2);
            $table->boolean('actif')->default(true);
            $table->timestamps();

            $table->index(['espace_id', 'actif']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('espace_abonnement_formules');
    }
};
