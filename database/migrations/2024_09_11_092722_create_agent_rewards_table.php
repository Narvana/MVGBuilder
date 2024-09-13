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
        Schema::create('agent_rewards', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->integer('Direct')->default(0); 
            $table->integer('Group')->default(0);  
            $table->string('Reward_Achived')->default('null');
            $table->boolean('Reward_Received')->default(0);
            $table->string('Next_Reward')->default('null');
            $table->foreign('agent_id')->references('id')->on('agent_registers')->delete('onCascade');
            $table->timestamps();
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_rewards');
    }
};
