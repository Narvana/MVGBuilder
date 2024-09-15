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
            // $table->integer('aadhaar_card')->default(0)->after('pancard_no');
            $table->string('aadhaar_card',12)->default(0)->after('pancard_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_registers', function (Blueprint $table) {
            //
        });
    }
};
