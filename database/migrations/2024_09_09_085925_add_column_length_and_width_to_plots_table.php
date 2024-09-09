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
            $table->decimal('plot_length', 8, 2)->nullable()->default(0)->after('plot_type');
            $table->decimal('plot_width', 8, 2)->nullable()->default(0)->after('plot_length');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plots', function (Blueprint $table) {
            //
        });
    }
};
