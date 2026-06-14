<?php

namespace App\Modules\NoorImport\Controllers;

use App\Http\Controllers\Controller;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Method 2 & 3 — Noor API / Credential Settings (scaffolded, no live calls).
 *
 * These settings are stored encrypted in the `noor_connection_settings` table.
 * The "test connection" route returns a clear stub message indicating the
 * integration requires official Noor API credentials which are not yet active.
 *
 * Design is intentionally swappable: swap the stub in testConnection() for
 * a real HTTP call once official API access is granted by the Ministry.
 */
class NoorSettingsController extends Controller
{
    public function index(Request $request): View
    {
        $this->authorise($request);

        $row = DB::table('noor_connection_settings')
            ->orderByDesc('id')
            ->first();

        // Never expose decrypted credentials to the view — only meta fields.
        $settings = [
            'api_base_url'      => $row?->api_base_url ?? '',
            'has_api_token'     => ! empty($row?->api_token_encrypted),
            'has_admin_pass'    => ! empty($row?->admin_password_encrypted),
            'admin_username'    => $row?->admin_username ?? '',
            'method'            => $row?->active_method ?? 'excel',
            'last_sync_at'      => $row?->last_sync_at,
            'sync_status'       => $row?->sync_status ?? null,
        ];

        $history = DB::table('noor_sync_logs')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        return view('admin.noor.settings', compact('settings', 'history'));
    }

    public function save(Request $request): RedirectResponse
    {
        $this->authorise($request);

        $validated = $request->validate([
            'api_base_url'   => ['nullable', 'url', 'max:255'],
            'api_token'      => ['nullable', 'string', 'max:1024'],
            'admin_username' => ['nullable', 'string', 'max:255'],
            'admin_password' => ['nullable', 'string', 'max:1024'],
            'active_method'  => ['required', 'string', 'in:excel,api,credential'],
        ]);

        $update = [
            'api_base_url'  => $validated['api_base_url'] ?? null,
            'admin_username' => $validated['admin_username'] ?? null,
            'active_method' => $validated['active_method'],
            'updated_at'    => now(),
        ];

        // Only overwrite encrypted fields when a new value is provided.
        if (! empty($validated['api_token'])) {
            $update['api_token_encrypted'] = Crypt::encryptString($validated['api_token']);
        }
        if (! empty($validated['admin_password'])) {
            $update['admin_password_encrypted'] = Crypt::encryptString($validated['admin_password']);
        }

        $existing = DB::table('noor_connection_settings')->orderByDesc('id')->value('id');
        if ($existing) {
            DB::table('noor_connection_settings')->where('id', $existing)->update($update);
        } else {
            $update['created_at'] = now();
            DB::table('noor_connection_settings')->insert($update);
        }

        return back()->with('success', __('noor.settings.saved'));
    }

    /**
     * Stub: returns a clear, honest message that live API is not yet wired.
     * Swap the body of this method once official Noor API access is obtained.
     */
    public function testConnection(Request $request): JsonResponse
    {
        $this->authorise($request);

        return ApiResponse::ok(null, __('noor.settings.connection_stub'));
    }

    private function authorise(Request $request): void
    {
        // The noor_connection_settings row is global (system-wide), so only a
        // super-admin may read/write it — a school-admin writing it would alter
        // the integration for every school (privilege escalation).
        $user = $request->user();
        abort_unless($user && $user->isSuperAdmin(), 403);
    }
}
