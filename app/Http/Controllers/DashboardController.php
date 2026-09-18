<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $modules = Module::orderBy('day_number')->get();
        $progress = $user->progress()->pluck('completed', 'module_id');
        $subscriptionStatus = $user->subscription_status;

        $accessibleModules = $modules->filter(function (Module $module) use ($user) {
            return $module->isAccessibleBy($user);
        });

        $pricing = [
            'course_price_full' => Setting::coursePriceFull(),
            'course_price_daily' => Setting::coursePriceDaily(),
        ];

        $payload = [
            'user' => $user->only(['id', 'name', 'phone_number', 'email', 'subscription_status', 'role']),
            'subscription_status' => $subscriptionStatus,
            'pricing' => $pricing,
            'modules' => $modules->map(function (Module $module) use ($user, $progress) {
                return [
                    'id' => $module->id,
                    'day_number' => $module->day_number,
                    'title' => $module->title,
                    'is_free' => $module->is_free,
                    'accessible' => $module->isAccessibleBy($user),
                    'completed' => (bool) $progress->get($module->id, false),
                ];
            }),
        ];

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json($payload);
        }

        return view('dashboard', [
            'user' => $user,
            'modules' => $modules,
            'accessibleModules' => $accessibleModules,
            'progress' => $progress,
            'subscriptionStatus' => $subscriptionStatus,
            'pricing' => $pricing,
        ]);
    }
}
