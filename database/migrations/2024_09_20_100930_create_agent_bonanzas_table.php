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
        Schema::create('agent_bonanzas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->integer('Area_Sold')->default(0);
            $table->string('Bonanza_Place')->default('null');
            $table->string('Bonanza_Days')->default('null');
            $table->boolean('Bonanza_Received')->default(0);
            $table->foreign('agent_id')->references('id')->on('agent_registers')->delete('onCascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_bonanzas');
    }
};
