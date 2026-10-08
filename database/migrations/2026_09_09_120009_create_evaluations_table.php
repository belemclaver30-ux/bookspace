<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Classe Evaluation (TDR §7) : le client « rédige » un avis sur un espace
 * après une réservation terminée (TDR §5.1 « évaluer un espace après
 * utilisation »). Un avis par réservation.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('evaluations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('client_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('espace_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('note'); // 1..5
            $table->text('commentaire')->nullable();
            $table->timestamps();

            $table->index(['espace_id', 'note']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('evaluations');
    }
};
