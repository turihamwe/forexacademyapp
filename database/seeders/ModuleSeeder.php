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
        $curriculum = require __DIR__ . '/data/curriculum.php';

        foreach ($curriculum as $dayNumber => $lesson) {
            Module::updateOrCreate(
                ['day_number' => $dayNumber],
                [
                    'title' => $lesson['title'],
                    'content' => $lesson['content'],
                    'is_free' => $lesson['is_free'],
                ]
            );
        }
    }
}
