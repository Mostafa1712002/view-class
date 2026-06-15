<?php

namespace App\Modules\Certificates\Controllers;

use App\Http\Controllers\Controller;
use App\Models\CertificateTemplate;
use App\Modules\Certificates\Http\Requests\StoreCertificateTemplateRequest;
use App\Modules\Certificates\Repositories\Contracts\CertificateTemplateRepository;
use App\Modules\Users\Controllers\Concerns\HasSchoolScope;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class CertificateTemplateController extends Controller
{
    use HasSchoolScope;

    /** Preferred background dimensions per the card. */
    private const PREF_WIDTH = 3508;
    private const PREF_HEIGHT = 2479;

    public function __construct(private CertificateTemplateRepository $templates) {}

    public function index(Request $request): View
    {
        $filters = [
            'type' => $request->get('type'),
            'q'    => $request->get('q'),
        ];

        $templates = $this->templates->listForSchool($this->activeSchoolId(), $filters);

        return view('certificates.templates.index', compact('templates', 'filters'));
    }

    public function create(): View
    {
        return view('certificates.templates.form', ['template' => null]);
    }

    public function store(StoreCertificateTemplateRequest $request): RedirectResponse
    {
        // Fail-closed scope: no null-scope tenant bypass for non super-admins.
        $schoolId = $this->scopedSchoolId();

        $data = $this->payload($request, $schoolId);
        $warning = null;

        if ($request->hasFile('background')) {
            [$data['background_path'], $warning] = $this->storeBackground($request->file('background'));
        }

        $this->templates->create($data);

        return redirect()
            ->route('admin.certificate-templates.index')
            ->with('success', __('certificates.tpl.flash.created'))
            ->with($warning ? ['warning' => $warning] : []);
    }

    public function edit(int $template): View
    {
        $tpl = $this->templates->find($template);
        abort_if(! $tpl, 404);
        $this->authorizeTemplate($tpl);

        return view('certificates.templates.form', ['template' => $tpl]);
    }

    public function update(StoreCertificateTemplateRequest $request, int $template): RedirectResponse
    {
        $tpl = $this->templates->find($template);
        abort_if(! $tpl, 404);
        $this->authorizeTemplate($tpl);

        $data = $this->payload($request, $tpl->school_id);
        $warning = null;

        if ($request->hasFile('background')) {
            if ($tpl->background_path) {
                Storage::disk('public')->delete($tpl->background_path);
            }
            [$data['background_path'], $warning] = $this->storeBackground($request->file('background'));
        }

        $this->templates->update($tpl, $data);

        return redirect()
            ->route('admin.certificate-templates.index')
            ->with('success', __('certificates.tpl.flash.updated'))
            ->with($warning ? ['warning' => $warning] : []);
    }

    public function destroy(int $template): RedirectResponse
    {
        $tpl = $this->templates->find($template);
        abort_if(! $tpl, 404);
        $this->authorizeTemplate($tpl);

        if ($tpl->background_path) {
            Storage::disk('public')->delete($tpl->background_path);
        }

        $this->templates->delete($tpl);

        return redirect()
            ->route('admin.certificate-templates.index')
            ->with('success', __('certificates.tpl.flash.deleted'));
    }

    /** Shared, sanitised attribute payload for create/update. */
    private function payload(StoreCertificateTemplateRequest $request, ?int $schoolId): array
    {
        $lines = array_values(array_filter(
            (array) $request->input('lines', []),
            fn ($l) => $l !== null && trim((string) $l) !== ''
        ));

        return [
            'school_id'   => $schoolId,
            'name'        => $request->input('name'),
            'type'        => $request->input('type'),
            'orientation' => $request->input('orientation'),
            'text_color'  => $request->input('text_color') ?: '#222222',
            'name_color'  => $request->input('name_color') ?: '#1a3c6e',
            'body'        => ['lines' => $lines],
            'created_by'  => auth()->id(),
        ];
    }

    /**
     * Store the background and return [path, warning|null]. A wrong-dimension
     * image is accepted (does NOT break the page) with a soft warning.
     *
     * @return array{0:string,1:?string}
     */
    private function storeBackground(\Illuminate\Http\UploadedFile $file): array
    {
        $path = $file->store('certificates/templates', 'public');
        $warning = null;

        $size = @getimagesize($file->getRealPath());
        if ($size && ($size[0] !== self::PREF_WIDTH || $size[1] !== self::PREF_HEIGHT)) {
            $warning = __('certificates.tpl.dimension_warning', [
                'w' => self::PREF_WIDTH,
                'h' => self::PREF_HEIGHT,
                'aw' => $size[0],
                'ah' => $size[1],
            ]);
        }

        return [$path, $warning];
    }

    /**
     * Tenant guard: super-admins are global; everyone else needs a concrete
     * active school matching the template's school_id.
     */
    private function authorizeTemplate(CertificateTemplate $tpl): void
    {
        $user = auth()->user();
        if ($user->isSuperAdmin()) {
            return;
        }

        $schoolId = $this->activeSchoolId();
        abort_if($schoolId === null, 403);
        abort_unless($tpl->school_id === $schoolId, 404);
    }
}
