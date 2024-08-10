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
        Schema::table('agent_d_g_sales', function (Blueprint $table) {
            //
            $table->integer('salary')->default(0)->after('final_incentive');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_d_g_sales', function (Blueprint $table) {
            //
            $table->dropColumn('salary');
        });
    }
};
