<?php

namespace App\Modules\RawaniHub\Controllers\My;

use App\Http\Controllers\Controller;
use App\Modules\RawaniHub\Actions\IssueSsoLink;
use Illuminate\Http\Request;

class AccountOpenController extends Controller
{
    public function __invoke(Request $request, int $account, IssueSsoLink $issue)
    {
        $out = $issue->execute($request->user(), $account);

        if (isset($out['url'])) {
            return redirect()->away($out['url'])->withHeaders(['Cache-Control' => 'no-store, private']);
        }

        return response()->view('hub.my.open_credentials', $out)->withHeaders(['Cache-Control' => 'no-store, private']);
    }
}
