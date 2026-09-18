<?php

namespace App\Http\Controllers;

use App\Models\Module;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ModuleController extends Controller
{
    /**
     * Display a single module lesson if the user has access.
     */
    public function show($id)
    {
        $module = Module::findOrFail($id);
        $user = Auth::user();

        if (! $module->isAccessibleBy($user)) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'This module is locked. Upgrade to the full course or daily plan to continue learning.');
        }

        $userProgress = UserProgress::firstOrCreate(
            ['user_id' => $user->id, 'module_id' => $module->id],
            ['completed' => false]
        );

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'module' => [
                    'id' => $module->id,
                    'day_number' => $module->day_number,
                    'title' => $module->title,
                    'content' => $module->content,
                    'is_free' => $module->is_free,
                ],
                'completed' => $userProgress->completed,
            ]);
        }

        return view('modules.show', [
            'module' => $module,
            'userProgress' => $userProgress,
        ]);
    }

    /**
     * Mark the module as completed for the authenticated user.
     */
    public function complete(Request $request, $id)
    {
        $module = Module::findOrFail($id);
        $user = Auth::user();

        if (! $module->isAccessibleBy($user)) {
            return redirect()
                ->route('dashboard')
                ->with('error', 'You do not have access to this module.');
        }

        UserProgress::updateOrCreate(
            ['user_id' => $user->id, 'module_id' => $module->id],
            ['completed' => true, 'completed_at' => now()]
        );

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Module marked as completed.']);
        }

        return redirect()
            ->route('modules.show', $module->id)
            ->with('success', 'Great work! Module marked as completed.');
    }
}
