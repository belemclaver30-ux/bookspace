<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Compte Mobile Money sur lequel le gérant souhaite recevoir ses revenus. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gerants', function (Blueprint $table) {
            $table->string('momo_operateur', 30)->nullable()->after('ville');
            $table->string('momo_numero', 30)->nullable()->after('momo_operateur');
            $table->string('momo_titulaire')->nullable()->after('momo_numero');
        });
    }

    public function down(): void
    {
        Schema::table('gerants', function (Blueprint $table) {
            $table->dropColumn(['momo_operateur', 'momo_numero', 'momo_titulaire']);
        });
    }
};
