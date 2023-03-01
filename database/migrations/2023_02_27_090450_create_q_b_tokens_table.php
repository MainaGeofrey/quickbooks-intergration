<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('q_b_tokens', function (Blueprint $table) {
            $table->id();
            $table->integer("user_id");
            $table->text("access_token");
            $table->text("refresh_token");
            $table->string('realm_id');
            $table->string('client_id');
            $table->string('client_secret');
            $table->string("expires_in")->timestamps();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('q_b_tokens');
    }
};
