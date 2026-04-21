<?php

namespace App\Modules\Localization\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Localization\Actions\SwitchLocaleAction;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __construct(private SwitchLocaleAction $switchLocale) {}

    public function switch(Request $request, string $locale): RedirectResponse
    {
        $this->switchLocale->execute($request->user(), $locale, $request->session());

        return back();
    }
}
