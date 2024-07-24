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
            $table->unsignedBigInteger('plot_id')->change();
            $table->unsignedBigInteger('client_id')->change();
            $table->unsignedBigInteger('agent_id')->change();

            $table->foreign('plot_id')->references('id')->on('plots')->onDelete('cascade');
            $table->foreign('agent_id')->references('id')->on('agent_registers')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('client_controllers')->onDelete('cascade');
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
