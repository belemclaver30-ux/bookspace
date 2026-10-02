<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Yenga (agrégateur multi-opérateurs) devient le mode de paiement Mobile
 * Money utilisé par l'app. orange_money/moov_money restent dans l'enum
 * pour l'historique, mais ne sont plus proposés côté client.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->enum('mode', ['orange_money', 'moov_money', 'yenga'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('paiements', function (Blueprint $table) {
            $table->enum('mode', ['orange_money', 'moov_money'])->change();
        });
    }
};
