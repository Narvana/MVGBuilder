<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Models\AgentRegister;

class AgentRegisterSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
       $agent= DB::table('agent_registers')->insertGetId([
            'fullname' => 'MVG Super Agent',
            'email' => 'info@MVGBuilder.com', //info@MVGBuilder.com
            'password' => Hash::make('Info2mvg'), // Hash the password
            'referral_code' => "0",
            'pancard_no'  => '0000000000',
            'contact_no' => '0000000000',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $agentModel = AgentRegister::find($agent); // Assuming your model for the 'agent_registers' table is User
        $agentModel->assignRole('agent');
        // Add more seed data as needed...
    }
}
