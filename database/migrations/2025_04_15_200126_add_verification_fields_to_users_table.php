<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Ajoutez ces nouveaux champs
            $table->string('verification_code')->nullable()->after('email_verified_at');
            $table->timestamp('verification_code_expires_at')->nullable()->after('verification_code');

            // Supprimez l'ancien champ si vous ne l'utilisez plus
            $table->dropColumn('email_verification_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Pour annuler les changements
            $table->string('email_verification_token')->nullable();
            $table->dropColumn(['verification_code', 'verification_code_expires_at']);
        });
    }
};
