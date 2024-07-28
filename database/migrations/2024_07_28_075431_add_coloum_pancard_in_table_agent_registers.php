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
            //
            $table->string('pancard_no')->after('referral_code')->unique();
            $table->string('contact_no')->after('pancard_no')->unique();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_registers', function (Blueprint $table) {
            //
            $table->dropColumn('pancard');
        });
    }
};
