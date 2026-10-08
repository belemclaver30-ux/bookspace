<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Horaires d'ouverture récurrents d'un espace (TDR §5.2 « définir les créneaux
 * disponibles »). Une ligne = une plage hebdomadaire pour un jour donné.
 * jour_semaine : 0 = dimanche … 6 = samedi (ISO Carbon::dayOfWeek).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('espace_disponibilites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('espace_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('jour_semaine');
            $table->time('heure_ouverture');
            $table->time('heure_fermeture');
            $table->timestamps();

            $table->unique(['espace_id', 'jour_semaine', 'heure_ouverture'], 'dispo_espace_jour_heure_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('espace_disponibilites');
    }
};
