// Track 3 (teacher) recording flows. See docs/video-scripts/track-3-teacher.md
// for the full voiceover scripts these captions are condensed from, and
// docs/video-scripts/flows.cjs for the schema this file follows.
// All URLs verified against `php artisan route:list` (2026-07-07) — static
// GET routes only, no dynamic {id} segments, all reachable by role:teacher.
module.exports = [

  {
    id: 'V3.1', track: '3', title: 'نظرة عامة: لوحتي وجدولي وتقويمي', role: 'teacher',
    steps: [
      { goto: '/dashboard', caption: 'لوحتك بتلخّص يومك: <b>حصصك، طلابك، والواجبات</b> المطلوب تصحيحها.', dwell: 4500 },
      { caption: 'خد لمحة سريعة عن يومك قبل ما تدخل أي شاشة تفصيلية.', dwell: 4000 },
      { goto: '/teacher/schedule', caption: '<b>جدولي</b>: جدولك الأسبوعي بكل حصصك ومادتها وفصلها، وتقدر تصدّره PDF.', dwell: 4500 },
      { goto: '/teacher/calendar', caption: '<b>تقويم المعلم</b> بيجمّع كل حاجة على مواعيد: دروس، اختبارات، واجبات، وفصول افتراضية.', dwell: 4500 },
    ],
  },

  {
    id: 'V3.2', track: '3', title: 'مواد المعلم ومركز إدارة المحتوى', role: 'teacher',
    steps: [
      { goto: '/teacher/subjects', caption: '<b>مواد المعلم</b>: كارت لكل مادة بتدرّسها فعليًا حسب حصصك في الجدول.', dwell: 4500 },
      { goto: '/teacher/materials', caption: 'اضغط على مادة يفتحلك <b>مركز إدارة المحتوى</b> — كل محتوى المادة من مكان واحد.', dwell: 4500 },
      { goto: '/teacher/materials/classes', caption: 'تصفية متسلسلة: <b>مادة ← صف ← فصل</b>، كل خطوة بتفتح خيارات اللي بعدها.', dwell: 4500 },
      { goto: '/teacher/materials/results', caption: 'اختَر نوع المحتوى (أسئلة، كتب، واجبات، اختبارات...) والنتائج <b>تتفلتر تلقائيًا</b>.', dwell: 4500 },
    ],
  },

  {
    id: 'V3.3', track: '3', title: 'الخطة الأسبوعية والتحضير', role: 'teacher',
    steps: [
      { goto: '/teacher/weekly-plans', caption: '<b>خططي الأسبوعية</b>: خططك مرتبة بالأسبوع مع حالتها — تحضير، مكتملة، أو مقفلة.', dwell: 4500 },
      { caption: 'انسخ خطة قديمة قريبة من اللي محتاجه بدل ما تكتبها من الأول.', dwell: 4000 },
      { goto: '/teacher/weekly-plans/create', caption: 'لكل حصة اكتب <b>الموضوع، الأهداف، والواجب</b> المرتبط بيها.', dwell: 4500 },
      { caption: 'احفظ الخطة وعلّم عليها <b>«تم التحضير»</b> لما تخلّص إعدادها.', dwell: 4000 },
      { caption: '⚠️ بعد ما الوكيل يصدّرها ويقفلها، <b>مش هتقدر تعدّل أو تحذف</b> — الفك من عنده هو بس.', dwell: 4500 },
    ],
  },

  {
    id: 'V3.4', track: '3', title: 'الواجبات: إنشاء ومتابعة وتصحيح', role: 'teacher',
    steps: [
      { goto: '/admin/assignments', caption: '<b>الواجبات</b>: كل واجباتك بحالتها — مفتوح، مغلق، أو محتاج تصحيح.', dwell: 4000 },
      { goto: '/admin/assignments/create', caption: 'اختَر المادة والفصل، اكتب العنوان والوصف وتاريخ <b>التسليم</b>.', dwell: 4500 },
      { caption: 'فعّل <b>«السماح بالتسليم المتأخر»</b> لو عايز تدّي الطالب فرصة إضافية.', dwell: 4000 },
      { caption: 'بعد التسليم تشوف قائمة بكل طالب: وقت تسليمه وحالته (في الميعاد / متأخر).', dwell: 4500 },
      { caption: 'حطّ <b>درجة وملاحظة</b> لكل تسليم — بتوصل للطالب وولي أمره فورًا بعد الحفظ.', dwell: 4500 },
    ],
  },

  {
    id: 'V3.5', track: '3', title: 'بنك الأسئلة (تصفّح المعلم)', role: 'teacher',
    steps: [
      { goto: '/admin/question-banks', caption: 'استعرض <b>بنك أسئلة مدرستك</b> وافتح معاينة أي سؤال قبل ما تسحبه في اختبار.', dwell: 4500 },
      { caption: 'صلاحيتك هنا <b>قراءة فقط</b>: تصفّح ومعاينة، بدون إنشاء أو اعتماد.', dwell: 4000 },
      { goto: '/admin/question-banks/library', caption: 'بنك عام (<b>بنك الأول</b>) لو مدرستك مفعّل ليها الوصول له.', dwell: 4500 },
      { caption: 'أنواع الأسئلة: اختياري من متعدد، صح وخطأ، إكمال، مقالي، وتوصيل.', dwell: 4500 },
      { caption: '⚠️ السؤال لازم يكون <b>«معتمَد»</b> قبل ما تقدر تسحبه في أي اختبار.', dwell: 4000 },
    ],
  },

  {
    id: 'V3.6', track: '3', title: 'الاختبارات: دورة الحياة الكاملة', role: 'teacher',
    steps: [
      { goto: '/teacher/exams', caption: '<b>اختباراتي</b>: دورة حياة الاختبار كاملة من الإنشاء للنتيجة.', dwell: 4000 },
      { goto: '/teacher/exams/create', caption: 'اختَر المادة والفصل، اكتب العنوان والمدة، وحدّد <b>وقت البداية والنهاية</b>.', dwell: 4500 },
      { caption: 'أضف سؤال يدوي أو الأسرع: اسحب أسئلة <b>معتمَدة</b> من بنك الأسئلة.', dwell: 4500 },
      { caption: '<b>النشر</b> يظهره في الجدول، و<b>التفعيل</b> هو اللي يفتح الدخول الفعلي للطلاب.', dwell: 4500 },
      { caption: 'بعد <b>الإنهاء</b> تشوف النتائج، وتقدر تعيد فتح محاولة طالب اتقفلت بغلط.', dwell: 4500 },
    ],
  },

  {
    id: 'V3.7', track: '3', title: 'رصد الدرجات وتقاريرها', role: 'teacher',
    steps: [
      { goto: '/teacher/grades', caption: '<b>إدخال الدرجات</b>: اختَر المادة والفصل واكتب درجة كل طالب.', dwell: 4000 },
      { caption: '⚠️ الدرجة تفضل <b>مسوّدة</b> — الطالب وولي أمره ما يشوفوهاش إلا بعد النشر.', dwell: 4500 },
      { goto: '/teacher/grades/class-report', caption: '<b>تقرير الفصل</b>: متوسط ودرجات كل الطلاب مجمّعة.', dwell: 4000 },
      { goto: '/teacher/grades/student-report', caption: '<b>تقرير الطالب</b>: أداؤه عبر كل المواد في مكان واحد.', dwell: 4000 },
      { goto: '/teacher/grades/subject-report', caption: '<b>تقرير المادة</b>: أداء كل فصولك في مادة معيّنة.', dwell: 4000 },
    ],
  },

  {
    id: 'V3.8', track: '3', title: 'الحضور والغياب للحصة', role: 'teacher',
    steps: [
      { goto: '/teacher/attendance', caption: '<b>تسجيل الحضور</b>: اختَر الحصة وعلّم كل طالب حاضر، غايب، أو متأخر.', dwell: 4500 },
      { caption: 'اضغط <b>«تحضير الكل»</b> لو الفصل كله حاضر ووفّر وقتك.', dwell: 4000 },
      { goto: '/teacher/attendance/daily-report', caption: '<b>التقرير اليومي</b>: غياب النهاردة على كل حصصك.', dwell: 4000 },
      { goto: '/teacher/attendance/student-report', caption: '<b>تقرير الطالب</b>: سجل غيابه عبر الفصل الدراسي.', dwell: 4000 },
      { goto: '/teacher/attendance/class-report', caption: '<b>تقرير الفصل</b>: غياب فصل كامل مجمّع.', dwell: 4000 },
    ],
  },

  {
    id: 'V3.9', track: '3', title: 'الفصول الافتراضية', role: 'teacher',
    steps: [
      { goto: '/manage/virtual-classes', caption: '<b>الفصول الافتراضية</b>: حصصك اللي بتتعقد أونلاين (زووم).', dwell: 4000 },
      { goto: '/manage/virtual-classes/create', caption: 'اربط الجلسة بالحصة والفصل وحدّد <b>التاريخ والوقت</b> — الرابط بيتولّد تلقائيًا.', dwell: 4500 },
      { caption: '⚠️ الطالب <b>ما يدخلش إلا بعد ما تضغط «بدء»</b> بنفسك من شاشتك.', dwell: 4500 },
      { caption: 'بعد ما تبدأ، تابع مين داخل من <b>داشبورد الجلسة</b>.', dwell: 4000 },
      { caption: 'بعد انتهاء الجلسة، سجل الحضور بيتحسب تلقائيًا وتقدر <b>تصدّره</b>.', dwell: 4000 },
    ],
  },

  {
    id: 'V3.10', track: '3', title: 'غرف النقاش', role: 'teacher',
    steps: [
      { goto: '/manage/discussion-rooms', caption: '<b>غرف النقاشات</b>: مكان تناقش فيه طلابك خارج وقت الحصة.', dwell: 4000 },
      { goto: '/manage/discussion-rooms/create', caption: 'اكتب عنوان الغرفة واربطها <b>بمادة أو فصل</b> معيّن.', dwell: 4000 },
      { caption: 'داخل الغرفة ترفع ملف أو تفتح <b>موضوع نقاش</b> جديد.', dwell: 4000 },
      { caption: 'تعليقات غير مناسبة؟ اقفل التعليقات على الموضوع أو <b>اخفِ تعليق واحد</b> بس.', dwell: 4500 },
      { caption: 'وتقدر <b>تغلق الغرفة كاملة</b> لو خلصتوا نقاشها.', dwell: 4000 },
    ],
  },

  {
    id: 'V3.11', track: '3', title: 'المواعيد والحجوزات الواردة', role: 'teacher',
    steps: [
      { goto: '/manage/appointment-schedules', caption: '<b>المواعيد</b>: هنا تفتح أوقاتك المتاحة عشان أولياء الأمور يحجزوا معاك.', dwell: 4500 },
      { goto: '/manage/appointment-schedules/create', caption: 'حدّد <b>الأيام والأوقات</b> المتاحة ومدة كل موعد، واحفظ.', dwell: 4500 },
      { goto: '/manage/appointments', caption: '<b>الحجوزات الواردة</b>: طلبات أولياء الأمور بالاسم والوقت والحالة.', dwell: 4500 },
      { caption: 'افتح الطلب واضغط <b>«تأكيد»</b> أو <b>«رفض»</b> — ولي الأمر ياخد إشعار فورًا.', dwell: 4500 },
    ],
  },

];
