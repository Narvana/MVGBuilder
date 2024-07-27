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
        Schema::create('agent_levels', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->unsignedBigInteger('agent_id');
            $table->string('level');
            $table->timestamps();

            // $table->foreign('parent_id')->references('id')->on('admin_registers')->onDelete('cascade');
            $table->foreign('parent_id')->references('id')->on('agent_registers')->onDelete('cascade');
            $table->foreign('agent_id')->references('id')->on('agent_registers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_levels');
    }
};
