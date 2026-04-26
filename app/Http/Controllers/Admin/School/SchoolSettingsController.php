<?php

namespace App\Http\Controllers\Admin\School;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;

class SchoolSettingsController extends Controller
{
    public function show(School $school)
    {
        return view('admin.schools.settings.show', [
            'school' => $school,
            'settings' => $school->settings ?? [],
        ]);
    }

    public function update(Request $request, School $school)
    {
        // Sprint 2 Phase 2 will fully validate each section. For now persist as-is so the
        // UI roundtrips while per-section rules are being authored.
        $payload = $request->except(['_token', '_method']);

        // Cast checkbox-style strings to booleans so Blade @checked works on roundtrip.
        array_walk_recursive($payload, function (&$v) {
            if ($v === 'on' || $v === '1' || $v === 'true') {
                $v = true;
            } elseif ($v === '0' || $v === 'false') {
                $v = false;
            }
        });

        $school->settings = array_replace_recursive($school->settings ?? [], $payload);
        $school->save();

        return redirect()->route('admin.schools.settings.show', $school)
            ->with('success', __('common.saved_successfully'));
    }
}
