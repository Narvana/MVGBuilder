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
        Schema::table('client_controllers', function (Blueprint $table) {
            //
            $table->unsignedBigInteger('plot_id')->change();
            $table->unsignedBigInteger('agent_id')->change();

            $table->foreign('plot_id')->references('id')->on('plots')->onDelete('cascade');
            $table->foreign('agent_id')->references('id')->on('agent_registers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_controllers', function (Blueprint $table) {
            //
        });
    }
};
