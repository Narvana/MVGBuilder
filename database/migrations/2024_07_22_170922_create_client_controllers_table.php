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
        Schema::create('client_controllers', function (Blueprint $table) {
            $table->id();
            $table->string('client_name');
            $table->string('client_contact',10);
            $table->string('client_address',600);
            $table->string('client_city')->nullable()->default('');
            $table->string('client_state');
            $table->string('plot_id');
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_controllers');
    }
};
