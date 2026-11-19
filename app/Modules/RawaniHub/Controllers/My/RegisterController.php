<?php

namespace App\Modules\RawaniHub\Controllers\My;

use App\Http\Controllers\Controller;
use App\Modules\RawaniHub\Actions\NationalIdCollision;
use App\Modules\RawaniHub\Actions\RegisterBuyer;
use App\Modules\RawaniHub\Http\Requests\RegisterBuyerRequest;
use Illuminate\Support\Facades\Auth;

class RegisterController extends Controller
{
    public function show()
    {
        return view('hub.my.register');
    }

    public function store(RegisterBuyerRequest $request, RegisterBuyer $register)
    {
        try {
            $user = $register->execute(['phone' => $request->normalizedPhone()] + $request->validated());
        } catch (NationalIdCollision) {
            // Same answer for an email clash and a national-ID clash: never say which one exists.
            return back()->withInput($request->except('password', 'password_confirmation'))->withErrors(['collision' => __('hub_my.have_account')]);
        }

        Auth::login($user);
        $request->session()->regenerate();
        $user->forceFill(['last_login_at' => now()])->save();

        return redirect()->route('my.packages.index');
    }
}
