<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * دليل الاستخدام (Docs) — in-app tutorial video center.
 * Static catalog from config/tutorials.php, filtered to the user's role.
 */
class DocsController extends Controller
{
    public function index(Request $request)
    {
        $role = optional($request->user()->roles->first())->slug ?? 'student';

        $tracks = collect(config('tutorials.tracks'))
            ->filter(fn ($track) => in_array($role, $track['roles'], true))
            ->values();

        $videoCount = $tracks->sum(fn ($t) => count($t['videos']));

        return view('docs.index', compact('tracks', 'videoCount', 'role'));
    }
}
