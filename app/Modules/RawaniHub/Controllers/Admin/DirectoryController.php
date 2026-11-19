<?php

namespace App\Modules\RawaniHub\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Modules\RawaniHub\Actions\CreatePersonFromAccount;
use App\Modules\RawaniHub\Http\Requests\CreatePersonRequest;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformAccountRepository;
use App\Modules\RawaniHub\Repositories\Contracts\PlatformRepository;
use App\Modules\RawaniHub\Services\HubPeople;
use App\Modules\RawaniHub\Services\LinkCandidates;
use Illuminate\Http\Request;

/** «دليل المستخدمين» (design §3.5, tasks 4.2–4.3). Linking is always an admin action; nothing here links on its own. */
class DirectoryController extends Controller
{
    public function __construct(private PlatformAccountRepository $accounts, private PlatformRepository $platforms, private HubPeople $people) {}

    public function index(Request $request)
    {
        $filters = $request->validate([
            'tab' => ['nullable', 'in:accounts,people'],
            'platform_id' => ['nullable', 'integer'],
            'role' => ['nullable', 'in:student,parent,teacher'],
            'status' => ['nullable', 'in:pending,needs_info,active,conflict,failed,disabled,missing,unlinked'],
            'linked' => ['nullable', 'in:0,1'],
            'q' => ['nullable', 'string', 'max:100'],
        ]);
        $tab = $filters['tab'] ?? 'accounts';
        $people = $tab === 'people' ? $this->people->directory($filters)->withQueryString() : null;

        return view('hub.admin.directory.index', [
            'tab' => $tab,
            'platforms' => $this->platforms->all(),
            'accounts' => $tab === 'accounts' ? $this->accounts->paginate($filters)->withQueryString() : null,
            'people' => $people,
            'peopleAccounts' => $people ? $this->people->accountsByUser($people->pluck('id')->all()) : collect(),
        ]);
    }

    public function linkForm(Request $request, int $account, LinkCandidates $candidates)
    {
        $row = $this->accounts->find($account) ?? abort(404);
        $lookup = $request->validate(['lookup' => ['nullable', 'string', 'max:100']])['lookup'] ?? null;

        return view('hub.admin.directory.link', [
            'account' => $row,
            'relatives' => $this->accounts->relatives($row),
            'people' => $row->user_id === null ? $candidates->peopleFor($row, $lookup) : collect(),
            'mirrorRows' => $row->status === 'conflict' ? $candidates->accountsFor($row) : collect(),
            'lookup' => $lookup,
        ]);
    }

    public function createPerson(CreatePersonRequest $request, int $account, CreatePersonFromAccount $create)
    {
        $person = $create->execute($request->user(), $account, $request->validated());

        return redirect()->route('admin.hub.people.show', $person->id)->with('success', __('hub_directory.person_created'));
    }
}
