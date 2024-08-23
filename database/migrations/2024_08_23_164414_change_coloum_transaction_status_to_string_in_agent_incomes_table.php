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
        Schema::table('agent_incomes', function (Blueprint $table) {
            //
            $table->string('transaction_status')->default('PENDING')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_incomes', function (Blueprint $table) {
            //
            $table->boolean('transaction_status')->default(false)->change();
        });
    }
};
