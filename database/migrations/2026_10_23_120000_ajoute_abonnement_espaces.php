<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Réservations récurrentes (abonnement) : le gérant décide, espace par
 * espace, s'il propose cette formule à ses clients (TDR §8 — amélioration).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('espaces', function (Blueprint $table) {
            $table->boolean('abonnement_actif')->default(false)->after('mis_en_avant');
        });
    }

    public function down(): void
    {
        Schema::table('espaces', function (Blueprint $table) {
            $table->dropColumn('abonnement_actif');
        });
    }
};
