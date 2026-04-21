<?php

namespace App\Modules\Profile\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Auth\Http\Resources\UserResource;
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
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProfileApiController extends Controller
{
    public function update(UpdateProfileRequest $request, UpdateProfileAction $action): JsonResponse
    {
        $dto = new UpdateProfileDto(
            nameAr: $request->input('name_ar'),
            nameEn: $request->input('name_en'),
            phone: $request->input('phone'),
            email: $request->input('email'),
        );

        $user = $action->execute($request->user(), $dto);

        return ApiResponse::ok(['user' => UserResource::toArray($user)], trans('profile.update_success'));
    }

    public function changePassword(ChangePasswordRequest $request, ChangePasswordAction $action): JsonResponse
    {
        $dto = new ChangePasswordDto(
            currentPassword: $request->string('currentPassword')->toString(),
            newPassword: $request->string('newPassword')->toString(),
        );

        try {
            $action->execute($request->user(), $dto);
        } catch (InvalidCurrentPasswordException) {
            return ApiResponse::fail('INVALID_CURRENT_PASSWORD', trans('profile.password_mismatch'), 422);
        } catch (SamePasswordException) {
            return ApiResponse::fail('SAME_PASSWORD', trans('profile.password_same_as_current'), 422);
        }

        return ApiResponse::ok(null, trans('profile.update_success'));
    }

    public function updateAvatar(UpdateAvatarRequest $request, UpdateAvatarAction $action): JsonResponse
    {
        $user = $action->execute($request->user(), $request->file('avatar'));

        return ApiResponse::ok([
            'url' => $user->profile_picture,
            'user' => UserResource::toArray($user),
        ], trans('profile.update_success'));
    }
}
