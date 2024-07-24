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
            // Rename the column first
            $table->renameColumn('price_status', 'plot_status');
        });

        Schema::table('plots', function (Blueprint $table) {
            // Then set the default value
            $table->string('plot_status')->default('VACANT')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plots', function (Blueprint $table) {
            //
            $table->string('plot_status')->default(null)->change();

            // Revert the column rename
            $table->renameColumn('plot_status', 'price_status');
        });
    }
};
