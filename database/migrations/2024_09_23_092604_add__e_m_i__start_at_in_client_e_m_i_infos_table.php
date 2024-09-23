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
        Schema::table('client_e_m_i_infos', function (Blueprint $table) {
            //
            $table->integer('EMI_Start_at')->default(0)->after('EMI_Date');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('client_e_m_i_infos', function (Blueprint $table) {
            //
        });
    }
};
