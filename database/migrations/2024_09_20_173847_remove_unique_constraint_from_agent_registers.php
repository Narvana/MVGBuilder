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
            $table->dropUnique(['email']);        
            $table->dropUnique(['pancard_no']);  
            $table->dropUnique(['contact_no']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('agent_registers', function (Blueprint $table) {
            // //
            // $table->unique('email');
            // $table->unique('pancard_no');
            // $table->unique('contact_no');
        });
    }
};
