<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Vue immersive d'un espace :
 * - `angle` : 4 photos prises du centre en tournant d'un quart de tour
 *   (position 0 = face, 1 = droite, 2 = arrière, 3 = gauche) ;
 * - `panorama` : une photo 360° facultative (format 2:1, ou panorama du téléphone).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('espace_vues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('espace_id')->constrained()->cascadeOnDelete();
            $table->enum('type', ['angle', 'panorama']);
            $table->unsignedTinyInteger('position')->default(0);
            $table->string('chemin');
            $table->unsignedInteger('largeur');
            $table->unsignedInteger('hauteur');
            $table->timestamps();

            $table->unique(['espace_id', 'type', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('espace_vues');
    }
};
