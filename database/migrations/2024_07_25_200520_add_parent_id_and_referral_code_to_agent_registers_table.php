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
        Schema::table('agent_registers', function (Blueprint $table) {
            $table->string('referral_code', 20)->unique()->after('id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_registers', function (Blueprint $table) {
            //
            $table->dropColumn('referral_code');
        });
    }
};
