<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Préférences de notification de l'utilisateur (écran Profil > Préférences).
 * Le fil in-app est toujours alimenté ; ces réglages portent sur les SMS et
 * sur les rappels avant le créneau.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notif_sms')->default(true)->after('actif');
            $table->boolean('notif_rappels')->default(true)->after('notif_sms');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['notif_sms', 'notif_rappels']);
        });
    }
};
