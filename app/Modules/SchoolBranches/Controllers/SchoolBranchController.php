<?php

namespace App\Modules\SchoolBranches\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\SchoolBranches\Actions\CreateBranchAction;
use App\Modules\SchoolBranches\Actions\DeleteBranchAction;
use App\Modules\SchoolBranches\Actions\UpdateBranchAction;
use App\Modules\SchoolBranches\Http\Requests\StoreBranchRequest;
use App\Modules\SchoolBranches\Models\SchoolBranch;
use App\Modules\SchoolBranches\Repositories\Contracts\SchoolBranchRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use RuntimeException;

class SchoolBranchController extends Controller
{
    public function __construct(
        private SchoolBranchRepository $branches,
        private CreateBranchAction $createAction,
        private UpdateBranchAction $updateAction,
        private DeleteBranchAction $deleteAction,
    ) {}

    public function index(): View
    {
        $branches = $this->branches->paginate(15);
        return view('admin.school-branches.index', compact('branches'));
    }

    public function create(): View
    {
        return view('admin.school-branches.create');
    }

    public function store(StoreBranchRequest $request): RedirectResponse
    {
        $this->createAction->execute($request->validated());
        return redirect()
            ->route('admin.school-branches.index')
            ->with('success', __('common.created_successfully'));
    }

    public function edit(int $id): View
    {
        $branch = $this->branches->find($id);
        abort_if(!$branch, 404);
        return view('admin.school-branches.edit', compact('branch'));
    }

    public function update(StoreBranchRequest $request, int $id): RedirectResponse
    {
        $branch = $this->branches->find($id);
        abort_if(!$branch, 404);
        $this->updateAction->execute($branch, $request->validated());
        return redirect()
            ->route('admin.school-branches.index')
            ->with('success', __('common.updated_successfully'));
    }

    public function destroy(int $id): RedirectResponse
    {
        $branch = $this->branches->find($id);
        abort_if(!$branch, 404);
        try {
            $this->deleteAction->execute($branch);
        } catch (RuntimeException $e) {
            if ($e->getMessage() === 'branch_has_schools') {
                return back()->with('error', __('school_branches.cannot_delete_has_schools'));
            }
            throw $e;
        }
        return redirect()
            ->route('admin.school-branches.index')
            ->with('success', __('common.deleted_successfully'));
    }
}
