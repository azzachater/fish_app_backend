<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        if (!Schema::hasTable('password_reset_codes')) {
            Schema::create('password_reset_codes', function (Blueprint $table) {
                $table->id();
                $table->string('email')->index();
                $table->string('code', 6);  // Code à 6 chiffres
                $table->timestamp('expires_at');  // Ajout recommandé
                $table->timestamps();
            });
        }
    }

    public function down()
    {
        Schema::dropIfExists('password_reset_codes');
    }
};
