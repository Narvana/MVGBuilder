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
        Schema::table('plots', function (Blueprint $table) {
            //
            $table->dropColumn('price_from');
            $table->dropColumn('price_to');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plots', function (Blueprint $table) {
            //
            $table->integer('price_from')->nullable(); // Add the columns back in case of rollback
            $table->integer('price_to')->nullable();
        });
    }
};