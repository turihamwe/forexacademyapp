<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    public function index()
    {
        return response()->json([
            'course_price_full' => Setting::coursePriceFull(),
            'course_price_daily' => Setting::coursePriceDaily(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'course_price_full' => ['sometimes', 'numeric', 'min:0'],
            'course_price_daily' => ['sometimes', 'numeric', 'min:0'],
        ]);

        if (array_key_exists('course_price_full', $validated)) {
            Setting::setValue(
                Setting::KEY_COURSE_PRICE_FULL,
                (string) $validated['course_price_full']
            );
        }

        if (array_key_exists('course_price_daily', $validated)) {
            Setting::setValue(
                Setting::KEY_COURSE_PRICE_DAILY,
                (string) $validated['course_price_daily']
            );
        }

        return response()->json([
            'message' => 'Settings updated successfully.',
            'course_price_full' => Setting::coursePriceFull(),
            'course_price_daily' => Setting::coursePriceDaily(),
        ]);
    }
}
