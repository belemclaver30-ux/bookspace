<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Classe Notification (TDR §7-8) : communication automatique client <-> gérant
 * (confirmation de réservation, nouvelle réservation reçue, annulation…).
 * `canal` : push (in-app), sms (passerelle SMS), email.
 * Table nommée `app_notifications` pour ne pas entrer en conflit avec la table
 * `notifications` native de Laravel.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('canal', ['push', 'sms', 'email'])->default('push');
            $table->string('type');
            $table->string('titre');
            $table->text('contenu');
            $table->json('data')->nullable();
            $table->timestamp('envoye_at')->nullable();
            $table->timestamp('lu_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'lu_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_notifications');
    }
};
