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
        Schema::table('agent_incomes', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('final_agent')->after('plot_sale_id');

            $table->foreign('final_agent')->references('id')->on('agent_registers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_incomes', function (Blueprint $table) {
            //
        });
    }
};
