<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Chaque séance d'un abonnement est une Reservation normale, rattachée à
 * son abonnement : elle garde tout le cycle de vie standard (paiement,
 * report, annulation, clôture, avis…) sans dupliquer de règle métier.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('abonnement_id')->nullable()->after('gerant_id')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('abonnement_id');
        });
    }
};
