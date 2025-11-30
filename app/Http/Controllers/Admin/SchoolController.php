<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\School;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    public function index()
    {
        $schools = School::withCount(['users', 'sections'])->paginate(15);
        return view('admin.schools.index', compact('schools'));
    }

    public function create()
    {
        return view('admin.schools.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:schools',
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|max:2048',
        ], [
            'name.required' => 'اسم المدرسة مطلوب',
            'code.required' => 'رمز المدرسة مطلوب',
            'code.unique' => 'رمز المدرسة مستخدم مسبقاً',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'website.url' => 'رابط الموقع غير صحيح',
            'logo.image' => 'يجب أن يكون الشعار صورة',
            'logo.max' => 'حجم الشعار يجب أن لا يتجاوز 2 ميجابايت',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('schools', 'public');
        }

        School::create($validated);

        return redirect()->route('admin.schools.index')
            ->with('success', 'تم إضافة المدرسة بنجاح');
    }

    public function show(School $school)
    {
        $school->load(['sections', 'academicYears']);
        return view('admin.schools.show', compact('school'));
    }

    public function edit(School $school)
    {
        return view('admin.schools.edit', compact('school'));
    }

    public function update(Request $request, School $school)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:schools,code,' . $school->id,
            'address' => 'nullable|string|max:500',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'logo' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
        ], [
            'name.required' => 'اسم المدرسة مطلوب',
            'code.required' => 'رمز المدرسة مطلوب',
            'code.unique' => 'رمز المدرسة مستخدم مسبقاً',
            'email.email' => 'البريد الإلكتروني غير صحيح',
            'website.url' => 'رابط الموقع غير صحيح',
        ]);

        if ($request->hasFile('logo')) {
            $validated['logo'] = $request->file('logo')->store('schools', 'public');
        }

        $school->update($validated);

        return redirect()->route('admin.schools.index')
            ->with('success', 'تم تحديث المدرسة بنجاح');
    }

    public function destroy(School $school)
    {
        if ($school->users()->count() > 0) {
            return back()->with('error', 'لا يمكن حذف المدرسة لوجود مستخدمين مرتبطين بها');
        }

        $school->delete();

        return redirect()->route('admin.schools.index')
            ->with('success', 'تم حذف المدرسة بنجاح');
    }
}
