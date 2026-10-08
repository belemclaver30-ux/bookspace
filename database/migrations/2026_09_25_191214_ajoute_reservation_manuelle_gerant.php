<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Permet au gérant de créer une réservation manuellement (client au comptoir
 * ou par téléphone, sans compte BookSpace) : client_id devient facultatif,
 * remplacé par un nom/téléphone libres quand la réservation vient du gérant.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('client_nom')->nullable()->after('client_id');
            $table->string('client_telephone', 30)->nullable()->after('client_nom');
            $table->enum('origine', ['client', 'gerant'])->default('client')->after('client_telephone');
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('client_id')->nullable(false)->change();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn(['client_nom', 'client_telephone', 'origine']);
        });
    }
};
