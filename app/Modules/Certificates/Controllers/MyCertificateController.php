<?php

namespace App\Modules\Certificates\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Certificates\Repositories\Contracts\CertificateRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\View\View;

class MyCertificateController extends Controller
{
    use HasSchoolScope;

    public function __construct(private CertificateRepository $certificates) {}

    public function index(): View
    {
        $user = auth()->user();
        $schoolId = $this->activeSchoolId();
        $certificates = collect();

        if ($user->isTeacher()) {
            // Teacher sees their own certificates AND certificates they issued or participated in
            $certificates = $this->certificates->forTeacher($schoolId, (int) $user->id);
        } elseif ($user->isStudent()) {
            // Student sees only their own published certificates
            $certificates = $this->certificates->publishedForRecipient($schoolId, (int) $user->id);
        } elseif ($user->isParent()) {
            // Parent sees published certificates for all their children
            $childIds = $user->children()->pluck('users.id')->map(fn ($id) => (int) $id)->all();
            $certificates = $this->certificates->publishedForRecipients($schoolId, $childIds);
        }

        return view('certificates.my.index', compact('certificates'));
    }
}
