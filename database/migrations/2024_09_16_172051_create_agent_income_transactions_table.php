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
        Schema::create('agent_income_transactions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('agent_id');
            $table->string('income_type');
            $table->string('transaction_status');
            $table->string('transaction_amount');
            $table->unsignedBigInteger('plot_sale_id');
            $table->string('Payment_Mode');
            $table->timestamps();

            $table->foreign('agent_id')->references('id')->on('agent_registers')->delete('onCascade');
            $table->foreign('plot_sale_id')->references('id')->on('plot_sales')->delete('onCascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_income_transactions');
    }
};
