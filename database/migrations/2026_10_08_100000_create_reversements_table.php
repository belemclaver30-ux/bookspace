<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reversements : BookSpace encaisse les acomptes en ligne et reverse au gérant
 * sa part (acompte − commission). Un reversement regroupe les réservations
 * terminées payées en une fois ; les coordonnées Mobile Money sont figées au
 * moment du versement.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reversements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gerant_id')->constrained()->restrictOnDelete();
            $table->decimal('montant', 12, 2);
            $table->unsignedInteger('nb_reservations');
            $table->string('momo_operateur', 30);
            $table->string('momo_numero', 30);
            $table->string('momo_titulaire')->nullable();
            $table->string('reference_operateur', 100);
            $table->string('note', 500)->nullable();
            $table->foreignId('effectue_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('reservations', function (Blueprint $table) {
            $table->foreignId('reversement_id')->nullable()->after('montant_reverse_gerant')
                ->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversement_id');
        });
        Schema::dropIfExists('reversements');
    }
};
