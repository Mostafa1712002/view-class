<?php

namespace App\Modules\Evaluation\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Evaluation;
use App\Models\EvaluationEvidence;
use App\Modules\Evaluation\Actions\UploadEvidence;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Task 12 — attach / remove evidence (files or links) bound to a specific
 * item or indicator of an evaluation, from the execution screen.
 */
class EvaluationEvidenceController extends Controller
{
    public function __construct(private readonly UploadEvidence $evidence)
    {
    }

    public function store(Request $request, int $evaluation): RedirectResponse
    {
        $eval = $this->resolveMine($evaluation);
        if (!$eval instanceof Evaluation) {
            return $eval;
        }

        $data = $request->validate([
            'type'               => ['required', 'in:file,link'],
            'item_id'            => ['nullable', 'integer'],
            'indicator_id'       => ['nullable', 'integer'],
            'url'                => ['nullable', 'url', 'max:2048'],
            'description'        => ['nullable', 'string', 'max:1000'],
            'visible_to_subject' => ['nullable', 'boolean'],
            'file'               => ['nullable', 'file', 'max:51200'], // 50MB
        ]);

        try {
            $this->evidence->upload(
                $eval,
                [
                    'type'               => $data['type'],
                    'item_id'            => $data['item_id'] ?? null,
                    'indicator_id'       => $data['indicator_id'] ?? null,
                    'url'                => $data['url'] ?? null,
                    'description'        => $data['description'] ?? null,
                    'visible_to_subject' => $request->boolean('visible_to_subject'),
                ],
                $request->file('file'),
                (int) auth()->id()
            );
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors())->withInput();
        }

        return redirect()->route('admin.evaluations.execute.show', $eval->id)
            ->with('status', __('evaluation.evidence.flash.added'));
    }

    public function destroy(int $evaluation, int $evidence): RedirectResponse
    {
        $eval = $this->resolveMine($evaluation);
        if (!$eval instanceof Evaluation) {
            return $eval;
        }

        $row = EvaluationEvidence::query()
            ->where('evaluation_id', $eval->id)
            ->whereKey($evidence)
            ->first();

        if (!$row) {
            return back()->with('error', __('evaluation.evidence.errors.not_found'));
        }

        $canOverride = (bool) (auth()->user()?->isSuperAdmin());

        try {
            $this->evidence->delete($eval, $row, (int) auth()->id(), $canOverride);
        } catch (ValidationException $e) {
            return back()->withErrors($e->errors());
        }

        return redirect()->route('admin.evaluations.execute.show', $eval->id)
            ->with('status', __('evaluation.evidence.flash.removed'));
    }

    private function resolveMine(int $id): Evaluation|RedirectResponse
    {
        $eval = Evaluation::query()->whereKey($id)->first();
        if (!$eval || (int) $eval->evaluator_id !== (int) auth()->id()) {
            return redirect()->route('admin.my-evaluations.index')->with('error', __('evaluation.execute.errors.not_yours'));
        }

        return $eval;
    }
}
