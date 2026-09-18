<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Setting::setValue(Setting::KEY_COURSE_PRICE_FULL, '100');
        Setting::setValue(Setting::KEY_COURSE_PRICE_DAILY, '4');
    }
}
