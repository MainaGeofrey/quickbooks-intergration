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
        //
        Schema::create('Bills', function (Blueprint $table) {
            $table->id();
            $table->string('VendorName');
            $table->char('VendorCode');
            $table->integer('RefrenceNo');
            $table->date('DueDate');
            $table->float('Amount');
            $table->string('ItemName');
            $table->string('ItemDescription');
            $table->char('ItemCode');
            $table->integer('Quantity');
            $table->float('UnitPrice');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
