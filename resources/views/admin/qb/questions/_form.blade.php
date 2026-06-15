@php
    $isEdit = isset($question) && $question->exists;
    $isRtl = app()->getLocale() === 'ar';
    $answer = $question->answer_data ?? [];
    $mcqOptions = $answer['options'] ?? ['', '', '', ''];
    $mcqCorrect = $answer['correct'] ?? null;
    $tfCorrect  = $answer['correct'] ?? null;
    $essayAnswer = $answer['model_answer'] ?? null;
    $shortAnswer = $answer['model_answer'] ?? null;
    $matchingPairs = $answer['pairs'] ?? [['left'=>'','right'=>''],['left'=>'','right'=>'']];
    $blanks = $answer['blanks'] ?? [''];
    $curType = old('type', $question->type ?? 'mcq');
@endphp

@push('styles')
<style>
    .qbf .section-title { font-size:15px; font-weight:700; color:#0f172a; padding:12px 0 8px; border-bottom:1px solid #e2e8f0; margin-bottom:14px; display:flex; align-items:center; gap:8px; }
    .qbf .form-label { font-weight:600; color:#0f172a; font-size:13px; }
    .qbf .helper { font-size:12px; color:#64748b; margin-top:3px; }
    .qbf .opt-row { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
    .qbf .opt-letter { width:32px;height:32px;border-radius:50%;background:#f1f5f9;display:flex;align-items:center;justify-content:center;font-weight:700;flex-shrink:0; }
    .qbf .opt-row .form-control { flex:1; }
    .qbf .pair-row { display:flex; align-items:center; gap:8px; margin-bottom:8px; }
    .qbf .pair-row .form-control { flex:1; }
    .qbf .pair-row .arrow { color:#94a3b8; }
    .qbf .scope-school { border:1px solid #e2e8f0; border-radius:10px; padding:10px 14px; margin-bottom:8px; }
    .qbf .scope-school.selected { border-color:#d4af37; background:#fffbeb; }
    .qbf .compound-title { font-weight:700; color:#7a5d12; margin:10px 0 6px; }
    .answer-block { display:none; }
    .answer-block.active { display:block; }
    .qbf .section-title span { color:#b8860b; font-weight:800; }
    .qb-stepnav { position:sticky; top:0; z-index:5; }
    .qb-steps { list-style:none; display:flex; flex-wrap:wrap; gap:6px; margin:0; padding:0; }
    .qb-steps li { flex:1 1 auto; }
    .qb-steps a { display:flex; align-items:center; justify-content:center; gap:8px; padding:8px 10px; border-radius:10px; background:#f8fafc; border:1px solid #e2e8f0; color:#475569; font-size:13px; font-weight:600; text-decoration:none; transition:all .15s; }
    .qb-steps a:hover { background:#fffbeb; border-color:#e3c97a; color:#7a5d12; }
    .qb-steps .n { width:24px; height:24px; border-radius:50%; background:#fff; border:1px solid #e2e8f0; display:flex; align-items:center; justify-content:center; font-weight:800; color:#b8860b; flex-shrink:0; }
    html { scroll-behavior:smooth; }
    [id^="qbSec"] { scroll-margin-top:72px; }
</style>
@endpush

<input type="hidden" name="type" id="qType" value="{{ $curType }}">

<div class="qbf">
    {{-- Section navigator (#257 §3 — clear stepped sections without a fragile JS wizard) --}}
    <div class="card mb-2 qb-stepnav">
        <div class="card-body py-2">
            <ol class="qb-steps">
                <li><a href="#qbSec1"><span class="n">1</span> النطاق والمدارس</a></li>
                <li><a href="#qbSec2"><span class="n">2</span> التصنيفات</a></li>
                <li><a href="#qbSec3"><span class="n">3</span> محتوى السؤال</a></li>
                <li><a href="#qbSec4"><span class="n">4</span> الإجابات</a></li>
            </ol>
        </div>
    </div>

    {{-- ── Section 1: scope (#249 reusable component) ── --}}
    <div class="card mb-2" id="qbSec1">
        <div class="card-body">
            <div class="section-title"><x-svg-icon name="diagram-3-fill" :size="16" /> <span>1.</span> تحديد نطاق السؤال</div>
            @include('admin.qb.partials.scope-selector', [
                'tree' => $tree,
                'scope' => $scopeService,
                'selected' => [
                    'school_id'   => old('scope_school_id', $isEdit ? optional($question->bank)->school_id : null),
                    'grade_id'    => old('grade_id', $question->grade_id ?? null),
                    'class_id'    => old('class_id', $question->class_id ?? null),
                    'semester_id' => old('semester_id', $question->semester_id ?? null),
                    'week_id'     => old('week_id', $question->week_id ?? null),
                ],
            ])
        </div>
    </div>

    {{-- ── Section 2: classification ── --}}
    <div class="card mb-2" id="qbSec2">
        <div class="card-body">
            <div class="section-title"><x-svg-icon name="tags-fill" :size="16" /> <span>2.</span> بيانات التصنيف</div>
            <div class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">بنك الأسئلة <span class="text-danger">*</span></label>
                    <select name="question_bank_id" class="form-select" required @disabled($isEdit)>
                        <option value="">— اختر البنك —</option>
                        @foreach($banks as $b)
                            <option value="{{ $b->id }}" @selected(old('question_bank_id', $question->question_bank_id ?? null) == $b->id)>{{ $b->name_ar }}</option>
                        @endforeach
                    </select>
                    @if($isEdit)<input type="hidden" name="question_bank_id" value="{{ $question->question_bank_id }}">@endif
                </div>
                <div class="col-md-4">
                    <label class="form-label">المادة</label>
                    <select name="subject_id" id="qSubject" class="form-select">
                        <option value="">— لا شيء —</option>
                        @foreach($subjects as $s)
                            <option value="{{ $s->id }}" @selected(old('subject_id', $question->subject_id ?? null) == $s->id)>{{ $s->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">المهارة</label>
                    <select name="skill_id" id="qSkill" class="form-select">
                        <option value="">— لا شيء —</option>
                        @foreach($skills as $sk)
                            <option value="{{ $sk->id }}" @selected(old('skill_id', $question->skill_id ?? null) == $sk->id)>{{ $sk->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">نوع السؤال <span class="text-danger">*</span></label>
                    <select id="qTypeSelect" class="form-select">
                        @foreach($types as $k => $label)
                            <option value="{{ $k }}" @selected($curType === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">مستوى الصعوبة</label>
                    <select name="difficulty" class="form-select">
                        @foreach($difficulties as $k => $label)
                            <option value="{{ $k }}" @selected(old('difficulty', $question->difficulty ?? 1) == $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">درجة السؤال</label>
                    <input type="number" step="0.5" min="0" name="points" class="form-control" value="{{ old('points', $question->points ?? 1) }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label">كود السؤال</label>
                    <input type="text" name="question_code" id="qCode" class="form-control" value="{{ old('question_code', $question->question_code ?? '') }}">
                    <div class="helper" id="qCodeHelp"></div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">حالة السؤال</label>
                    <select name="status" id="qbStatusField" class="form-select">
                        @foreach($statuses as $k => $label)
                            <option value="{{ $k }}" @selected(old('status', $question->status ?? 'approved') === $k)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">نوع التصنيف</label>
                    @php $curCategory = old('question_category', $question->question_category ?? 'normal'); @endphp
                    <select name="question_category" id="qCategory" class="form-select" @disabled($curCategory === 'passage')>
                        <option value="normal" @selected($curCategory === 'normal')>عادي</option>
                        <option value="tahsili" @selected($curCategory === 'tahsili')>تحصيلي</option>
                        @if($curCategory === 'passage')
                            <option value="passage" selected>قطعة</option>
                        @endif
                    </select>
                    @if($curCategory === 'passage')
                        <input type="hidden" name="question_category" value="passage">
                        <div class="helper">سؤال تابع لقطعة قرائية.</div>
                    @else
                        <div class="helper">سؤال التحصيلي يُستخدم في اختبارات التحصيلي وله تصنيفه المستقل.</div>
                    @endif
                </div>

                {{-- #251 tahsili-only classification (المجال/المعيار). Shown when التصنيف=تحصيلي. --}}
                <div class="col-md-3 qb-tahsili-field" style="{{ $curCategory === 'tahsili' ? '' : 'display:none;' }}">
                    <label class="form-label">المعيار (التحصيلي)</label>
                    <select name="standard_id" id="qStandard" class="form-select">
                        <option value="">— لا شيء —</option>
                        @foreach(($standards ?? collect()) as $st)
                            <option value="{{ $st->id }}" @selected(old('standard_id', $question->standard_id ?? null) == $st->id)>{{ $st->name }}</option>
                        @endforeach
                    </select>
                    <div class="helper">المجال والمعيار مدعومان للتحصيلي (المهارة غير مشترطة).</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Section 3: content ── --}}
    <div class="card mb-2" id="qbSec3">
        <div class="card-body">
            <div class="section-title"><x-svg-icon name="card-text" :size="16" /> <span>3.</span> محتوى السؤال</div>
            <div class="row g-3">
                <div class="col-md-12">
                    <label class="form-label">نص السؤال</label>
                    <textarea name="body_ar" id="qBody" class="form-control" rows="3">{{ old('body_ar', $question->body_ar ?? '') }}</textarea>
                    <div class="helper">مطلوب إلا إذا كان السؤال صورة كاملة.</div>
                </div>
                <div class="col-md-6">
                    <label class="form-label">رفع صورة (اختياري)</label>
                    <input type="file" name="attachment" accept="image/*" class="form-control">
                    @if($isEdit && $question->attachment_path)
                        <div class="mt-2 d-flex align-items-center gap-2">
                            <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($question->attachment_path) }}" style="max-width:120px;border-radius:8px;border:1px solid #e2e8f0;">
                            <label class="d-flex align-items-center gap-1 mb-0" style="font-size:13px;">
                                <input type="checkbox" name="remove_attachment" value="1"> حذف الصورة
                            </label>
                        </div>
                    @endif
                </div>
                <div class="col-md-6 d-flex align-items-center">
                    <label class="d-flex align-items-center gap-2 mb-0">
                        <input type="checkbox" name="is_full_image_question" id="qFullImage" value="1" @checked(old('is_full_image_question', $question->is_full_image_question ?? false))>
                        هل السؤال صورة كاملة؟
                    </label>
                </div>
                <div class="col-md-12">
                    <label class="form-label">شرح السؤال / ملاحظة داخلية (اختياري)</label>
                    <textarea name="explanation" class="form-control" rows="2">{{ old('explanation', $question->explanation ?? '') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    {{-- ── Section 4: answers (per type) ── --}}
    <div class="card mb-2" id="qbSec4">
        <div class="card-body">
            <div class="section-title"><x-svg-icon name="check2-circle" :size="16" /> <span>4.</span> الإجابات</div>

            {{-- MCQ --}}
            <div class="answer-block {{ $curType === 'mcq' ? 'active' : '' }}" data-type="mcq">
                <div id="mcqOptions">
                    @foreach($mcqOptions as $i => $opt)
                        <div class="opt-row">
                            <span class="opt-letter">{{ chr(65 + $i) }}</span>
                            <input type="text" name="options_ar[]" class="form-control" value="{{ old('options_ar.'.$i, $opt) }}" placeholder="نص الخيار">
                            <label class="d-flex align-items-center gap-1 mb-0" style="font-size:12px;white-space:nowrap;">
                                <input type="radio" name="correct_index" value="{{ $i }}" @checked((string)old('correct_index', $mcqCorrect) === (string)$i)> صحيحة
                            </label>
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.opt-row').remove(); qbReletter();">&times;</button>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="qbAddOption()">+ إضافة خيار</button>
                <div class="helper">خياران على الأقل، مع تحديد الإجابة الصحيحة.</div>
            </div>

            {{-- True/False --}}
            <div class="answer-block {{ $curType === 'true_false' ? 'active' : '' }}" data-type="true_false">
                <div class="d-flex gap-3">
                    <label class="d-flex align-items-center gap-1"><input type="radio" name="correct" value="true" @checked(in_array($tfCorrect, [true,'true','1',1], true))> صح</label>
                    <label class="d-flex align-items-center gap-1"><input type="radio" name="correct" value="false" @checked(in_array($tfCorrect, [false,'false','0',0], true))> خطأ</label>
                </div>
            </div>

            {{-- Essay --}}
            <div class="answer-block {{ $curType === 'essay' ? 'active' : '' }}" data-type="essay">
                <label class="form-label">الإجابة النموذجية</label>
                <textarea name="essay_answer" class="form-control" rows="3">{{ old('essay_answer', $essayAnswer) }}</textarea>
                <div class="helper">يحتاج تصحيحًا يدويًا.</div>
            </div>

            {{-- Short --}}
            <div class="answer-block {{ $curType === 'short' ? 'active' : '' }}" data-type="short">
                <label class="form-label">الإجابة القصيرة</label>
                <input type="text" name="short_answer" class="form-control" value="{{ old('short_answer', $shortAnswer) }}">
            </div>

            {{-- Fill blank --}}
            <div class="answer-block {{ $curType === 'fill_blank' ? 'active' : '' }}" data-type="fill_blank">
                <div id="blankList">
                    @foreach($blanks as $i => $bl)
                        <div class="opt-row">
                            <span class="opt-letter">{{ $i + 1 }}</span>
                            <input type="text" name="blanks[]" class="form-control" value="{{ old('blanks.'.$i, $bl) }}" placeholder="إجابة الفراغ">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.opt-row').remove();">&times;</button>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="qbAddBlank()">+ إضافة فراغ</button>
            </div>

            {{-- Matching --}}
            <div class="answer-block {{ $curType === 'matching' ? 'active' : '' }}" data-type="matching">
                <div id="pairList">
                    @foreach($matchingPairs as $i => $pair)
                        <div class="pair-row">
                            <input type="text" name="matching_left[]" class="form-control" value="{{ old('matching_left.'.$i, $pair['left'] ?? '') }}" placeholder="عمود أ">
                            <span class="arrow">⟷</span>
                            <input type="text" name="matching_right[]" class="form-control" value="{{ old('matching_right.'.$i, $pair['right'] ?? '') }}" placeholder="عمود ب">
                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.pair-row').remove();">&times;</button>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="qbAddPair()">+ إضافة زوج</button>
                <div class="helper">زوجان صحيحان على الأقل.</div>
            </div>
        </div>
    </div>

    <div class="qb-form-footer d-flex flex-wrap gap-2 mb-4 align-items-center">
        <button type="submit" class="btn btn-warning">
            <x-svg-icon name="check2-circle" :size="15" /> {{ $isEdit ? 'حفظ التعديلات' : 'إضافة السؤال' }}
        </button>
        {{-- حفظ كمسودة (#257 §3) — reuses the existing status=draft path, no backend change --}}
        <button type="submit" class="btn btn-outline-secondary" onclick="document.getElementById('qbStatusField') && (document.getElementById('qbStatusField').value='draft');">
            <x-svg-icon name="save" :size="15" /> حفظ كمسودة
        </button>
        <a href="{{ $backUrl ?? route('admin.qb.questions.index') }}" class="btn btn-outline-light text-dark border">رجوع</a>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const typeSelect = document.getElementById('qTypeSelect');
    const typeHidden = document.getElementById('qType');
    const blocks = document.querySelectorAll('.answer-block');

    function applyType(t) {
        typeHidden.value = t;
        blocks.forEach(b => b.classList.toggle('active', b.dataset.type === t));
    }
    typeSelect.addEventListener('change', e => applyType(e.target.value));

    // full-image → code required hint
    const fullImg = document.getElementById('qFullImage');
    const codeHelp = document.getElementById('qCodeHelp');
    function syncFullImage() {
        if (fullImg.checked) {
            codeHelp.textContent = 'مطلوب عند استخدام سؤال صورة كاملة لتسهيل البحث عنه لاحقًا.';
            codeHelp.style.color = '#b91c1c';
        } else {
            codeHelp.textContent = '';
        }
    }
    fullImg.addEventListener('change', syncFullImage);
    syncFullImage();

    // #251 — reveal tahsili-only fields (المعيار) when category = tahsili
    const category = document.getElementById('qCategory');
    if (category) {
        category.addEventListener('change', function () {
            const show = this.value === 'tahsili';
            document.querySelectorAll('.qb-tahsili-field').forEach(el => el.style.display = show ? '' : 'none');
        });
    }

    // skills filtered by subject
    const subject = document.getElementById('qSubject');
    const skill = document.getElementById('qSkill');
    if (subject) {
        subject.addEventListener('change', function () {
            const url = "{{ route('admin.qb.scope.skills') }}" + (this.value ? ('?subject_id=' + this.value) : '');
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } })
                .then(r => r.json())
                .then(d => {
                    skill.innerHTML = '<option value="">— لا شيء —</option>';
                    d.skills.forEach(s => {
                        const o = document.createElement('option');
                        o.value = s.id; o.textContent = s.name; skill.appendChild(o);
                    });
                }).catch(() => {});
        });
    }
})();

function qbReletter() {
    document.querySelectorAll('#mcqOptions .opt-row').forEach((row, i) => {
        row.querySelector('.opt-letter').textContent = String.fromCharCode(65 + i);
        row.querySelector('input[type=radio]').value = i;
    });
}
function qbAddOption() {
    const list = document.getElementById('mcqOptions');
    const i = list.children.length;
    const row = document.createElement('div');
    row.className = 'opt-row';
    row.innerHTML = `<span class="opt-letter">${String.fromCharCode(65 + i)}</span>
        <input type="text" name="options_ar[]" class="form-control" placeholder="نص الخيار">
        <label class="d-flex align-items-center gap-1 mb-0" style="font-size:12px;white-space:nowrap;"><input type="radio" name="correct_index" value="${i}"> صحيحة</label>
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.opt-row').remove(); qbReletter();">&times;</button>`;
    list.appendChild(row);
}
function qbAddBlank() {
    const list = document.getElementById('blankList');
    const i = list.children.length;
    const row = document.createElement('div');
    row.className = 'opt-row';
    row.innerHTML = `<span class="opt-letter">${i + 1}</span>
        <input type="text" name="blanks[]" class="form-control" placeholder="إجابة الفراغ">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.opt-row').remove();">&times;</button>`;
    list.appendChild(row);
}
function qbAddPair() {
    const list = document.getElementById('pairList');
    const row = document.createElement('div');
    row.className = 'pair-row';
    row.innerHTML = `<input type="text" name="matching_left[]" class="form-control" placeholder="عمود أ">
        <span class="arrow">⟷</span>
        <input type="text" name="matching_right[]" class="form-control" placeholder="عمود ب">
        <button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('.pair-row').remove();">&times;</button>`;
    list.appendChild(row);
}
</script>
@endpush
