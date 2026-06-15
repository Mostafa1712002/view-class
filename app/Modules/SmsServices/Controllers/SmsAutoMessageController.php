<?php

namespace App\Modules\SmsServices\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SmsServices\Models\SmsAutoMessageSetting;
use App\Modules\SmsServices\Support\SmsTemplateRenderer;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Trello #241 — bulk student auto-message settings + per-event templates.
 *
 * Each event type has an enable toggle + an editable template body. The
 * automatic dispatch triggers (fingerprint device events) are not all wired in
 * the platform yet; this card delivers the settings + templates + persistence.
 */
class SmsAutoMessageController extends Controller
{
    use HasSchoolScope;

    private function gate(): void
    {
        // reuse the templates permission (managing message models)
        abort_unless(auth()->user()?->canDo('messages.templates'), 403);
    }

    public function index(): View
    {
        $this->gate();
        $schoolId = $this->scopedSchoolId();

        $existing = SmsAutoMessageSetting::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->get()->keyBy('event_type');

        $events = [];
        foreach (SmsAutoMessageSetting::EVENT_TYPES as $type => $meta) {
            $row = $existing->get($type);
            $events[$type] = [
                'meta'      => $meta,
                'enabled'   => $row?->is_enabled ?? false,
                'body'      => $row?->template_body ?? $meta['default'],
                'threshold' => $row?->threshold,
            ];
        }

        return view('admin.sms-services.auto-messages.index', compact('events'));
    }

    public function edit(string $type): View
    {
        $this->gate();
        abort_unless(array_key_exists($type, SmsAutoMessageSetting::EVENT_TYPES), 404);
        $schoolId = $this->scopedSchoolId();
        $meta = SmsAutoMessageSetting::EVENT_TYPES[$type];

        $row = SmsAutoMessageSetting::query()
            ->when($schoolId, fn ($q) => $q->where('school_id', $schoolId))
            ->where('event_type', $type)->first();

        return view('admin.sms-services.auto-messages.edit', [
            'type'      => $type,
            'meta'      => $meta,
            'enabled'   => $row?->is_enabled ?? false,
            'body'      => $row?->template_body ?? $meta['default'],
            'threshold' => $row?->threshold,
            'variables' => SmsTemplateRenderer::VARIABLES,
        ]);
    }

    public function update(Request $request, string $type): RedirectResponse
    {
        $this->gate();
        abort_unless(array_key_exists($type, SmsAutoMessageSetting::EVENT_TYPES), 404);
        $schoolId = $this->scopedSchoolId();

        $data = $request->validate([
            'is_enabled'    => ['sometimes', 'boolean'],
            'template_body' => ['required', 'string', 'max:1600'],
            'threshold'     => ['nullable', 'integer', 'min:1'],
        ]);

        SmsAutoMessageSetting::updateOrCreate(
            ['school_id' => $schoolId, 'event_type' => $type],
            [
                'is_enabled'    => $request->boolean('is_enabled'),
                'template_body' => $data['template_body'],
                'threshold'     => $data['threshold'] ?? null,
            ]
        );

        return redirect()->route('admin.sms.auto-messages.index')
            ->with('success', __('common.updated_successfully'));
    }

    public function toggle(string $type): RedirectResponse
    {
        $this->gate();
        abort_unless(array_key_exists($type, SmsAutoMessageSetting::EVENT_TYPES), 404);
        $schoolId = $this->scopedSchoolId();
        $meta = SmsAutoMessageSetting::EVENT_TYPES[$type];

        $row = SmsAutoMessageSetting::firstOrCreate(
            ['school_id' => $schoolId, 'event_type' => $type],
            ['is_enabled' => false, 'template_body' => $meta['default']]
        );
        $row->update(['is_enabled' => ! $row->is_enabled]);

        return back()->with('success', __('common.updated_successfully'));
    }
}
