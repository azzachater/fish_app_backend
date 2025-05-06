<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Création table users avec vérification conditionnelle
        if (!Schema::hasTable('users')) {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();

                // Ajout conditionnel des colonnes
                if (!Schema::hasColumn('users', 'verification_code')) {
                    $table->string('verification_code', 6)->nullable();
                }

                if (!Schema::hasColumn('users', 'verification_code_expires_at')) {
                    $table->timestamp('verification_code_expires_at')->nullable();
                }

                $table->string('password');
                $table->rememberToken();
                $table->timestamps();
            });
        }

        // Remplacement des tokens par des codes (à supprimer si existe déjà)
        Schema::dropIfExists('password_reset_tokens');

        if (!Schema::hasTable('password_reset_codes')) {
            Schema::create('password_reset_codes', function (Blueprint $table) {
                $table->id();
                $table->string('email')->index();
                $table->string('code', 6);
                $table->timestamp('expires_at');
                $table->timestamps();
            });
        }

        // Session classique
        if (!Schema::hasTable('sessions')) {
            Schema::create('sessions', function (Blueprint $table) {
                $table->string('id')->primary();
                $table->foreignId('user_id')->nullable()->index();
                $table->string('ip_address', 45)->nullable();
                $table->text('user_agent')->nullable();
                $table->longText('payload');
                $table->integer('last_activity')->index();
            });
        }
    }

    public function down(): void
    {
        // Ordre important pour les contraintes de clé étrangère
        Schema::dropIfExists('sessions');
        Schema::dropIfExists('password_reset_codes');
        Schema::dropIfExists('password_reset_tokens'); // Pour compatibilité
        Schema::dropIfExists('users');
    }
};
