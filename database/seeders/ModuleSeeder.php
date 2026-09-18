<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

class ModuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        for ($day = 1; $day <= 30; $day++) {
            Module::updateOrCreate(
                ['day_number' => $day],
                [
                    'title' => "Day {$day}: Forex Foundations",
                    'content' => "Welcome to Day {$day} of the Forex Academy. Learn essential trading concepts step by step.",
                    'is_free' => $day <= 3,
                ]
            );
        }
    }
}
