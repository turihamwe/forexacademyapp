<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SuperadminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        User::updateOrCreate(
            ['phone_number' => '256700000001'],
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@forexacademy.local',
                'password' => Hash::make('changeme123'),
                'role' => User::ROLE_SUPERADMIN,
                'subscription_status' => User::SUBSCRIPTION_PAID_FULL,
            ]
        );
    }
}
