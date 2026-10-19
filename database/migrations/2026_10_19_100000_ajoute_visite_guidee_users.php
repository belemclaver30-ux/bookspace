<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Visite guidée de l'interface proposée une fois au nouvel utilisateur.
 * Les comptes existants sont considérés comme l'ayant déjà vue.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->timestamp('visite_terminee_at')->nullable()->after('notif_rappels');
        });

        DB::table('users')->update(['visite_terminee_at' => now()]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('visite_terminee_at');
        });
    }
};
