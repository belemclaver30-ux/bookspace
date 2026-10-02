<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Litiges entre client et gérant (TDR §5.3 « gérer les éventuels litiges »).
 * Ouvert par un client ou un gérant, tranché par l'administrateur.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('litiges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('reservation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('ouvert_par')->constrained('users')->cascadeOnDelete();
            $table->string('sujet');
            $table->text('description');
            $table->enum('statut', ['ouvert', 'en_cours', 'resolu', 'ferme'])->default('ouvert');
            $table->text('resolution')->nullable();
            $table->foreignId('resolu_par')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolu_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('litiges');
    }
};
