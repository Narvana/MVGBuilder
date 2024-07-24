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
        Schema::create('plot_sales', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plot_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('agent_id');
            $table->string('totalAmount');
            $table->string('plot_status')->default('PENDING');
            $table->decimal('plot_value', 5, 2);
            $table->timestamps();

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
        Schema::dropIfExists('plot_sales');
    }
};
