<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Classe Utilisateur (TDR §7) : socle commun Client / Gérant / Administrateur.
 * On enrichit la table `users` par défaut de Laravel plutôt que de la recréer.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('telephone', 30)->unique()->after('name');
            $table->string('email')->nullable()->change();
            $table->enum('role', ['client', 'gerant', 'admin'])->default('client')->after('telephone');
            $table->string('avatar')->nullable()->after('role');
            $table->boolean('actif')->default(true)->after('avatar');
            $table->timestamp('derniere_connexion_at')->nullable()->after('actif');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['telephone', 'role', 'avatar', 'actif', 'derniere_connexion_at']);
        });
    }
};
