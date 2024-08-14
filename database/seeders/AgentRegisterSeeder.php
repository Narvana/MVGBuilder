<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AgentRegisterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('agent_registers')->insert([
            'fullname' => 'MVG Super Agent',
            'email' => 'info@mvgbuilder.com',
            'password' => Hash::make('Info2mvg'), // Hash the password
            'referral_code' => "0",
            'pancard_no'  => '0000000000',
            'contact_no' => '0000000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add more seed data as needed...
    }
}
