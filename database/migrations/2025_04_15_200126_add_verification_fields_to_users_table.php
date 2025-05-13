<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            // Ajout conditionnel
            if (!Schema::hasColumn('users', 'verification_code')) {
                $table->string('verification_code', 6)->nullable()->after('email_verified_at');
            }

            if (!Schema::hasColumn('users', 'verification_code_expires_at')) {
                $table->timestamp('verification_code_expires_at')->nullable()->after('verification_code');
            }
        });
    }

    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            // Suppression conditionnelle (optionnel)
            $table->dropColumn(['verification_code', 'verification_code_expires_at']);
        });
    }
};