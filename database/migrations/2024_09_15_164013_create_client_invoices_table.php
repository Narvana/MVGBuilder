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
        Schema::create('client_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('Invoice_no', 10);
            $table->string('Client_name');
            $table->string('Client_contact', 10);
            $table->string('Client_address');
            $table->string('Site_Name');
            $table->string('Plot_No');
            $table->string('Plot_Area');
            $table->string('Transaction_id');        
            $table->string('Amount');
            $table->string('Transaction_date');
            $table->string('Agent_name');
            $table->string('Payment_Method');
            $table->string('Payment_Detail');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('client_invoices');
    }
};
