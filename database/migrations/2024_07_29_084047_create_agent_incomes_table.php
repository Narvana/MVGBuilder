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
        Schema::create('agent_incomes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plot_sale_id');
            $table->string('total_income');
            $table->string('tds_income');
            $table->boolean('pancard_status');
            $table->boolean('transaction_status')->nullable()->default(0);
            $table->timestamps();

            $table->foreign('plot_sale_id')->references('id')->on('plot_sales')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('agent_incomes');
    }
};
