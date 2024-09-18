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
        Schema::create('client_e_m_i_infos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('plot_sale_id');
            $table->decimal('EMI_Amount', 15, 2)->nullable()->default(0);
            $table->date('EMI_Date')->nullable();
            $table->timestamps();

            $table->foreign('plot_sale_id')->references('id')->on('plot_sales')->delete('onCascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_e_m_i_infos');
    }
};
