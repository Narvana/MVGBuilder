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
            $table->string('designation')->default('ASSOCIATION')->after('group');
            $table->decimal('incentive',20,2)->default(0)->after('designation');
            $table->decimal('tds_deduction',20,2)->default(0)->after('incentive');
            $table->decimal('final_incentive',20,2)->default(0)->after('tds_deduction');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_d_g_sales', function (Blueprint $table) {
            //
        });
    }
};
