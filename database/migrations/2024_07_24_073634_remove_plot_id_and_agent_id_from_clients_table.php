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
            $table->dropForeign(['plot_id']);
            $table->dropColumn('plot_id');
            $table->dropForeign(['agent_id']);
            $table->dropColumn('agent_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_controllers', function (Blueprint $table) {
            //
            $table->bigInteger('plot_id')->unsigned()->index();
            $table->foreign('plot_id')->references('id')->on('plots')->onDelete('cascade');
            $table->bigInteger('agent_id')->unsigned()->index();
            $table->foreign('agent_id')->references('id')->on('agents')->onDelete('cascade');
        });
    }
};
