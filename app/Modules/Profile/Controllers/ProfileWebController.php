<?php

namespace App\Modules\Profile\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Profile\Actions\ChangePasswordAction;
use App\Modules\Profile\Actions\UpdateAvatarAction;
use App\Modules\Profile\Actions\UpdateProfileAction;
use App\Modules\Profile\DTOs\ChangePasswordDto;
use App\Modules\Profile\DTOs\UpdateProfileDto;
use App\Modules\Profile\Exceptions\InvalidCurrentPasswordException;
use App\Modules\Profile\Exceptions\SamePasswordException;
use App\Modules\Profile\Http\Requests\ChangePasswordRequest;
use App\Modules\Profile\Http\Requests\UpdateAvatarRequest;
use App\Modules\Profile\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProfileWebController extends Controller
{
    public function edit(Request $request): View
    {
        return view('profile.edit', ['user' => $request->user()]);
    }

    public function update(UpdateProfileRequest $request, UpdateProfileAction $action): RedirectResponse
    {
        $dto = new UpdateProfileDto(
            nameAr: $request->input('name_ar'),
            nameEn: $request->input('name_en'),
            phone: $request->input('phone'),
            email: $request->input('email'),
        );

        $action->execute($request->user(), $dto);

        return redirect()->route('profile.edit')->with('status', trans('profile.update_success'));
    }

    public function changePassword(ChangePasswordRequest $request, ChangePasswordAction $action): RedirectResponse
    {
        $dto = new ChangePasswordDto(
            currentPassword: $request->string('currentPassword')->toString(),
            newPassword: $request->string('newPassword')->toString(),
        );

        try {
            $action->execute($request->user(), $dto);
        } catch (InvalidCurrentPasswordException) {
            return back()->withErrors(['currentPassword' => trans('profile.password_mismatch')]);
        } catch (SamePasswordException) {
            return back()->withErrors(['newPassword' => trans('profile.password_same_as_current')]);
        }

        return back()->with('status', trans('profile.update_success'));
    }

    public function updateAvatar(UpdateAvatarRequest $request, UpdateAvatarAction $action): RedirectResponse
    {
        $action->execute($request->user(), $request->file('avatar'));

        return back()->with('status', trans('profile.update_success'));
    }
}
