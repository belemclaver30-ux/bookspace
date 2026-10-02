<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Plages bloquées manuellement par le gérant (TDR §5.2 « bloquer manuellement
 * certaines plages ») : maintenance, événement privé, indisponibilité.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('espace_blocages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('espace_id')->constrained()->cascadeOnDelete();
            $table->dateTime('date_debut');
            $table->dateTime('date_fin');
            $table->string('motif')->nullable();
            $table->timestamps();

            $table->index(['espace_id', 'date_debut', 'date_fin']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('espace_blocages');
    }
};
