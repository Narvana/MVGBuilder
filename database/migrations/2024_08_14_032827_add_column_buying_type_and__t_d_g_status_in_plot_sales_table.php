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
        Schema::table('plot_sales', function (Blueprint $table) {
            //
            $table->string('buying_type')->after('agent_id');
            $table->boolean('TDG_status')->default(0)->after('plot_value');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('plot_sales', function (Blueprint $table) {
            //
        });
    }
};
