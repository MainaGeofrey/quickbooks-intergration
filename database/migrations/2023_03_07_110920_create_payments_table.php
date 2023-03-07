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
        Schema::create('payments', function (Blueprint $table) {
            $table->increments('payment_id');
            $table->integer('client_id');
            $table->string("account_name")->varchar(200);
            $table->string("mobile_number")->varchar(200);
            $table->string("reference_number")->nullable();
            $table->decimal('amount');
            $table->text('notes')->nullable();
            $table->json('response')->nullable();
            $table->boolean('processed')->default(0);
            $table->integer("status")->default(1);
            $table->timestamp("date_time")->nullable();
            $table->integer('created_by')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
