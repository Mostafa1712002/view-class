<?php

namespace App\Modules\VirtualClasses\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use App\Modules\VirtualClasses\Repositories\Contracts\VirtualClassRepositoryInterface;
use Illuminate\View\View;

/**
 * Student view of upcoming/live virtual classroom sessions.
 */
class StudentVirtualClassController extends Controller
{
    use HasSchoolScope;

    public function __construct(private VirtualClassRepositoryInterface $repo) {}

    public function index(): View
    {
        $user     = auth()->user();
        $schoolId = $this->activeSchoolId();
        $classes  = $this->repo->forStudent($user->id, $schoolId);

        return view('virtual-classes.student.index', compact('classes'));
    }
}
