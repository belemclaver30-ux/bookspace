<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Photos d'un espace (TDR §5.1 « photos », §10.3 stockage cloud).
 * `chemin` référence le disque de fichiers configuré (local en dev, S3 en prod).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('espace_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('espace_id')->constrained()->cascadeOnDelete();
            $table->string('chemin');
            $table->unsignedSmallInteger('ordre')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('espace_photos');
    }
};
