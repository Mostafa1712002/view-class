<?php

namespace App\Modules\Profile\Actions;

use App\Models\User;
use App\Modules\Profile\Repositories\Contracts\ProfileRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

final class UpdateAvatarAction
{
    public function __construct(private ProfileRepository $profiles) {}

    public function execute(User $user, UploadedFile $file): User
    {
        $path = $file->store('avatars', 'public');
        return $this->profiles->setProfilePicture($user, Storage::url($path));
    }
}
