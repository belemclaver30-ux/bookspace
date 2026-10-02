<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Paramètres plateforme modifiables par l'administrateur (TDR §5.3
 * « paramétrer le taux de commission prélevé sur chaque réservation »).
 * Stockage clé/valeur simple.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parametres', function (Blueprint $table) {
            $table->string('cle')->primary();
            $table->text('valeur')->nullable();
            $table->string('libelle')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('parametres');
    }
};
