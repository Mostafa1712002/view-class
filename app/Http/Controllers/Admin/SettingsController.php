<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $schoolId = $user->school_id;

        $settings = [
            'general' => Setting::getByGroup('general', $schoolId),
            'academic' => Setting::getByGroup('academic', $schoolId),
            'notifications' => Setting::getByGroup('notifications', $schoolId),
        ];

        $defaults = Setting::getDefaults();

        return view('admin.settings.index', compact('settings', 'defaults'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $schoolId = $user->school_id;

        $defaults = Setting::getDefaults();

        foreach ($defaults as $group => $groupSettings) {
            foreach ($groupSettings as $setting) {
                $key = $setting['key'];
                $type = $setting['type'];
                $value = $request->input($key);

                if ($type === 'boolean') {
                    $value = $request->has($key) ? '1' : '0';
                }

                if ($value !== null || $type === 'boolean') {
                    Setting::set($key, $value, $type, $schoolId, $group);
                }
            }
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'تم حفظ الإعدادات بنجاح');
    }

    public function profile()
    {
        $user = Auth::user();
        return view('admin.settings.profile', compact('user'));
    }

    public function updateProfile(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'birth_date' => ['nullable', 'date'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('avatar')) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }
            $validated['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update($validated);

        return redirect()->route('admin.settings.profile')
            ->with('success', 'تم تحديث الملف الشخصي بنجاح');
    }

    public function password()
    {
        return view('admin.settings.password');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = Auth::user();

        if (!Hash::check($validated['current_password'], $user->password)) {
            return back()->withErrors(['current_password' => 'كلمة المرور الحالية غير صحيحة']);
        }

        $user->update([
            'password' => Hash::make($validated['password']),
        ]);

        return redirect()->route('admin.settings.password')
            ->with('success', 'تم تغيير كلمة المرور بنجاح');
    }

    public function notifications()
    {
        $user = Auth::user();
        $preferences = $user->notification_preferences ?? [
            'email_grades' => true,
            'email_attendance' => true,
            'email_announcements' => true,
            'email_messages' => true,
            'browser_notifications' => false,
        ];

        return view('admin.settings.notifications', compact('user', 'preferences'));
    }

    public function updateNotifications(Request $request)
    {
        $user = Auth::user();

        $preferences = [
            'email_grades' => $request->has('email_grades'),
            'email_attendance' => $request->has('email_attendance'),
            'email_announcements' => $request->has('email_announcements'),
            'email_messages' => $request->has('email_messages'),
            'browser_notifications' => $request->has('browser_notifications'),
        ];

        $user->update([
            'notification_preferences' => $preferences,
            'language' => $request->input('language', 'ar'),
            'timezone' => $request->input('timezone', 'Asia/Riyadh'),
        ]);

        return redirect()->route('admin.settings.notifications')
            ->with('success', 'تم حفظ تفضيلات الإشعارات بنجاح');
    }

    public function uploadLogo(Request $request)
    {
        $request->validate([
            'logo' => ['required', 'image', 'max:2048'],
        ]);

        $user = Auth::user();
        $schoolId = $user->school_id;

        $currentLogo = Setting::get('school_logo', null, $schoolId);
        if ($currentLogo && Storage::disk('public')->exists($currentLogo)) {
            Storage::disk('public')->delete($currentLogo);
        }

        $path = $request->file('logo')->store('logos', 'public');
        Setting::set('school_logo', $path, 'string', $schoolId, 'general');

        return response()->json([
            'success' => true,
            'path' => Storage::url($path),
        ]);
    }
}
