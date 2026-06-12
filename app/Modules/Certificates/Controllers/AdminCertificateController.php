<?php

namespace App\Modules\Certificates\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\User;
use App\Modules\Certificates\Http\Requests\StoreCertificateRequest;
use App\Modules\Certificates\Http\Requests\UpdateCertificateRequest;
use App\Modules\Certificates\Repositories\Contracts\CertificateRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminCertificateController extends Controller
{
    use HasSchoolScope;

    public function __construct(private CertificateRepository $certificates) {}

    public function index(Request $request): View
    {
        $filters = [
            'type' => $request->get('type'),
            'q'    => $request->get('q'),
        ];

        $certificates = $this->certificates->listForSchool($this->activeSchoolId(), $filters);

        return view('certificates.admin.index', compact('certificates', 'filters'));
    }

    public function create(): View
    {
        $schoolId = $this->activeSchoolId();
        $recipients = $this->getSchoolUsers($schoolId);

        return view('certificates.admin.form', [
            'certificate' => null,
            'recipients'  => $recipients,
        ]);
    }

    public function store(StoreCertificateRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $schoolId = $this->activeSchoolId();

        // A non super-admin must have a concrete school scope (no null-scope tenant bypass).
        abort_if(! auth()->user()->isSuperAdmin() && $schoolId === null, 403);

        $data['school_id'] = $schoolId;
        // The issuer is always the acting admin — never client-supplied.
        $data['issued_by'] = auth()->id();

        if ($request->hasFile('file')) {
            $data['file_path'] = $request->file('file')->store('certificates', 'public');
        }
        unset($data['file']);

        $this->certificates->create($data);

        return redirect()
            ->route('admin.certificates.index')
            ->with('success', __('certificates.flash.created'));
    }

    public function edit(int $certificate): View
    {
        $cert = $this->certificates->find($certificate);
        abort_if(! $cert, 404);
        $this->authorizeCert($cert);

        $schoolId = $this->activeSchoolId();
        $recipients = $this->getSchoolUsers($schoolId);

        return view('certificates.admin.form', [
            'certificate' => $cert,
            'recipients'  => $recipients,
        ]);
    }

    public function update(UpdateCertificateRequest $request, int $certificate): RedirectResponse
    {
        $cert = $this->certificates->find($certificate);
        abort_if(! $cert, 404);
        $this->authorizeCert($cert);

        $data = $request->validated();

        if ($request->hasFile('file')) {
            // Delete old file if it exists
            if ($cert->file_path) {
                Storage::disk('public')->delete($cert->file_path);
            }
            $data['file_path'] = $request->file('file')->store('certificates', 'public');
        }
        unset($data['file']);

        $this->certificates->update($cert, $data);

        return redirect()
            ->route('admin.certificates.index')
            ->with('success', __('certificates.flash.updated'));
    }

    public function publish(Request $request, int $certificate): RedirectResponse
    {
        $cert = $this->certificates->find($certificate);
        abort_if(! $cert, 404);
        $this->authorizeCert($cert);

        $this->certificates->publish($cert);

        return redirect()
            ->route('admin.certificates.index')
            ->with('success', __('certificates.flash.published'));
    }

    public function destroy(int $certificate): RedirectResponse
    {
        $cert = $this->certificates->find($certificate);
        abort_if(! $cert, 404);
        $this->authorizeCert($cert);

        // Delete the file from disk if it exists
        if ($cert->file_path) {
            Storage::disk('public')->delete($cert->file_path);
        }

        $this->certificates->delete($cert);

        return redirect()
            ->route('admin.certificates.index')
            ->with('success', __('certificates.flash.deleted'));
    }

    /**
     * Guard a single certificate against cross-tenant access. Super-admins are
     * global; everyone else must have a concrete active school that matches the
     * certificate's school_id. A null active school for a non super-admin is a
     * hard 403 (no null-scope bypass), never a silent match.
     */
    private function authorizeCert(Certificate $cert): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            return;
        }

        $schoolId = $this->activeSchoolId();
        abort_if($schoolId === null, 403);
        abort_unless($cert->school_id === $schoolId, 404);
    }

    /**
     * Get active users for the school to populate the recipients dropdown.
     */
    private function getSchoolUsers(?int $schoolId): \Illuminate\Database\Eloquent\Collection
    {
        return User::query()
            ->when($schoolId, fn ($w) => $w->where('school_id', $schoolId))
            ->whereNull('deleted_at')
            ->orderBy('name')
            ->limit(2000)
            ->get(['id', 'name']);
    }
}
