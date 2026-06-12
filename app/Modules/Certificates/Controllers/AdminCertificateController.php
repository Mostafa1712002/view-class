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

        $data['school_id'] = $schoolId;
        $data['issued_by'] = $data['issued_by'] ?? auth()->id();

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

        $user = auth()->user();
        if (! $user->isSuperAdmin()) {
            abort_unless($cert->school_id === $this->activeSchoolId(), 404);
        }

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

        $user = auth()->user();
        if (! $user->isSuperAdmin()) {
            abort_unless($cert->school_id === $this->activeSchoolId(), 404);
        }

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

        $user = auth()->user();
        if (! $user->isSuperAdmin()) {
            abort_unless($cert->school_id === $this->activeSchoolId(), 404);
        }

        $this->certificates->publish($cert);

        return redirect()
            ->route('admin.certificates.index')
            ->with('success', __('certificates.flash.published'));
    }

    public function destroy(int $certificate): RedirectResponse
    {
        $cert = $this->certificates->find($certificate);
        abort_if(! $cert, 404);

        $user = auth()->user();
        if (! $user->isSuperAdmin()) {
            abort_unless($cert->school_id === $this->activeSchoolId(), 404);
        }

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
