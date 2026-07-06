// Track 2: school-admin. Captions are trusted (authored here), rendered as
// on-screen Arabic subtitles by record.js. role = 'school-admin' throughout.
// dwell in ms. All goto URLs verified against `php artisan route:list`.
module.exports = [

  {
    id: 'V2.1', track: '2', title: 'نظرة عامة + لوحة تحكم المدرسة', role: 'school-admin',
    steps: [
      { goto: '/dashboard', caption: 'أهلاً بك في مسار <b>مدير المدرسة</b> — محرّك التشغيل اليومي للمدرسة.', dwell: 4500 },
      { caption: 'يدير كل المستخدمين ويشرف على المعلم بالتقييم، والمعلم يوصّل المحتوى للطالب وولي الأمر.', dwell: 4500 },
      { caption: 'بطاقات <b>لوحة التحكم</b> بتوريك عدد الطلاب والمعلمين والحضور والتنبيهات بضغطة واحدة.', dwell: 4000 },
      { caption: '<b>بياناتك معزولة تمامًا</b> عن أي مدرسة تانية — مدير النظام هو من يضيف المدارس الجديدة.', dwell: 4500 },
    ],
  },
  {
    id: 'V2.2', track: '2', title: 'إدارة المستخدمين', role: 'school-admin',
    steps: [
      { goto: '/admin/users/teachers', caption: 'من <b>المستخدمين</b> تضيف معلم جديد: بياناته، تخصصه، ويتولّد له حساب دخول تلقائي.', dwell: 4500 },
      { goto: '/admin/users/students', caption: 'من تبويب <b>الطلاب</b> تضيفه وتربطه بصف وفصل وولي أمره الموجود أو الجديد.', dwell: 4500 },
      { goto: '/admin/users/parents', caption: 'تبويب <b>أولياء الأمور</b> يعرض ملفه، وتقدر تربط له ابن تاني من هنا.', dwell: 4000 },
      { goto: '/admin/users/admins', caption: 'من <b>الإدارة</b> تضيف حساب لأي مسمّى وظيفي (وكيل، مشرف) بصلاحياته الموروثة.', dwell: 4500 },
      { goto: '/admin/users/cards', caption: '<b>بطاقات المستخدمين</b> تولّد بطاقة دخول مطبوعة أو تجدّد كلمة مرور منسية.', dwell: 4000 },
    ],
  },
  {
    id: 'V2.3', track: '2', title: 'استيراد نظام نور + الاستيراد بالإكسل', role: 'school-admin',
    steps: [
      { goto: '/admin/noor', caption: 'استورد بيانات الطلاب من <b>نظام نور</b> بدل الإدخال اليدوي واحد واحد.', dwell: 4500 },
      { caption: 'حمّل القالب، املأه، وارفعه — النظام يعرض <b>معاينة</b> قبل التنفيذ الفعلي.', dwell: 4500 },
      { caption: 'راجع تقرير <b>الأخطاء</b> — الصفوف الناقصة أو المكررة مش هتتستورد.', dwell: 4000 },
      { goto: '/admin/users/students/import', caption: 'مفيش نور؟ كل تبويب مستخدمين عنده استيراد <b>إكسل</b> مستقل بنفس الخطوات.', dwell: 4500 },
    ],
  },
  {
    id: 'V2.4', track: '2', title: 'الصلاحيات والأدوار الوظيفية', role: 'school-admin',
    steps: [
      { goto: '/admin/users/job-titles', caption: 'كل مستخدم إداري له <b>مسمّى وظيفي</b> يحدد صلاحياته: وكيل، مشرف مقيم أو تخصص.', dwell: 4500 },
      { caption: 'أضف مسمّى جديد باسمه، ثم من زر <b>الصلاحيات</b> فعّل أو أوقف كل صلاحية بالتفصيل.', dwell: 4500 },
      { caption: 'حدد نطاق مشرف التخصص بصلاحيات محددة فقط — مش بمنحه كل الصلاحيات دفعة واحدة.', dwell: 4500 },
      { caption: 'كل حساب إداري تقدر تحدد له <b>المعلمين اللي تحت إشرافه</b> من شاشة "من يشرف عليهم".', dwell: 4000 },
    ],
  },
  {
    id: 'V2.5', track: '2', title: 'المواد الدراسية والمسارات', role: 'school-admin',
    steps: [
      { goto: '/admin/subjects', caption: '<b>المواد الدراسية</b> أساس كل حاجة بعد كده: الجدول، الاختبارات، والدرجات.', dwell: 4500 },
      { goto: '/admin/subjects/templates', caption: 'استورد مواد جاهزة من <b>قوالب المنصّة</b>، أو أنشئ مادة خاصة بمدرستك.', dwell: 4500 },
      { goto: '/admin/subjects/create', caption: 'حدد اسم المادة وصورتها والصف، وأضف <b>مجالات ووحدات ودروس</b> تحتها.', dwell: 4500 },
      { goto: '/admin/subjects/tracks', caption: '<b>المسارات</b> تُستخدم لتخصصات الثانوي (علمي/أدبي) — كل مسار له مجموعة مواد.', dwell: 4000 },
    ],
  },
  {
    id: 'V2.6', track: '2', title: 'الجدول المدرسي ونصاب المعلمين', role: 'school-admin',
    steps: [
      { goto: '/admin/lessons', caption: '<b>الحصص</b> تربط كل معلم بمادته وفصله وتوقيته طول الأسبوع.', dwell: 4500 },
      { goto: '/admin/lessons/advanced', caption: 'الجدول <b>المتقدّم</b> يعرض كل الفصول في مصفوفة واحدة، مع توليد تلقائي مبدئي.', dwell: 4500 },
      { goto: '/admin/school-schedule', caption: 'شاشة <b>الجدول المدرسي</b> تحت إعدادات النظام تجمّع كل الفصول والمعلمين.', dwell: 4000 },
      { goto: '/admin/users/teachers/workloads', caption: 'تقرير <b>النصاب</b> يوريك عدد حصص كل معلم أسبوعيًا — راقب من يقترب من 35 حصة.', dwell: 4500 },
      { goto: '/admin/lessons/conflicts', caption: 'شاشة <b>التعارضات</b> تكشف لو معلم اتحط في حصتين بنفس الوقت بالغلط.', dwell: 4000 },
    ],
  },
  {
    id: 'V2.7', track: '2', title: 'بنك الأسئلة', role: 'school-admin',
    steps: [
      { goto: '/admin/question-banks', caption: '<b>بنك الأسئلة</b> مخزون الأسئلة اللي المعلم يسحب منه لما يعمل اختبار.', dwell: 4500 },
      { goto: '/admin/question-banks/create', caption: 'حدد المادة والصف، واختر البنك <b>خاص</b> لمعلم أو <b>عام</b> لكل معلمي المادة.', dwell: 4500 },
      { goto: '/admin/question-banks/batch/create', caption: 'خمس أنواع أسئلة: اختياري، صح وخطأ، إكمال، توصيل، ومقالي — كل نوع له نموذج مختلف.', dwell: 4500 },
      { caption: 'السؤال يدخل "بانتظار الاعتماد" — لازم تعتمده قبل ما يظهر للمعلم في السحب.', dwell: 4500 },
      { goto: '/admin/question-banks/library', caption: 'مكتبة الأسئلة العامة تتيح <b>استنساخ</b> سؤال معتمَد من مدرسة تانية لبنكك.', dwell: 4000 },
    ],
  },
  {
    id: 'V2.8', track: '2', title: 'الاختبارات والواجبات', role: 'school-admin',
    steps: [
      { goto: '/admin/exams', caption: 'تابع كل <b>اختبارات</b> مدرستك حتى لو المعلم هو اللي أنشأها فعليًا.', dwell: 4500 },
      { goto: '/admin/exams/create', caption: 'حدد المادة والفصل والمدة، ثم أضف الأسئلة يدويًا أو بالسحب من <b>بنك الأسئلة</b>.', dwell: 4500 },
      { caption: 'الاختبار يمر بمراحل: مسودة، منشور، مفعّل، ومكتمل — وتقدر تعيد فتح محاولة طالب.', dwell: 4500 },
      { goto: '/admin/assignments', caption: 'نفس المنطق في <b>الواجبات</b>: موعد تسليم، السماح بالتأخير، ورصد الدرجة والملاحظة.', dwell: 4500 },
    ],
  },
  {
    id: 'V2.9', track: '2', title: 'إدارة الدرجات والتقارير', role: 'school-admin',
    steps: [
      { goto: '/admin/grade-reports', caption: '<b>تقارير الدرجات</b> الديناميكية تجمع أعمدة زي الاختبار والمشاركة بوزن لكل عمود.', dwell: 4500 },
      { goto: '/admin/grade-reports/create', caption: 'أنشئ تقرير لفصل ومادة، وحدد وزن كل عمود من <b>الدرجة الكلية</b>.', dwell: 4500 },
      { goto: '/admin/grade-reports/monitor', caption: 'شاشة <b>مراقبة الرصد</b> تكشف أي تقرير درجات لسه ناقص عند المعلمين.', dwell: 4500 },
      { goto: '/admin/grades/entry', caption: 'من <b>الإدخال الديناميكي</b> ترصد أو تعدّل درجة أي طالب مباشرة كإدارة.', dwell: 4000 },
      { goto: '/admin/grades', caption: '<b>الإدخال المبسّط</b> الأقدم فيه زر نشر/إلغاء نشر يتحكم في ظهور الدرجة للطالب.', dwell: 4500 },
    ],
  },
  {
    id: 'V2.10', track: '2', title: 'المكتبات الرقمية والكتب', role: 'school-admin',
    steps: [
      { goto: '/admin/libraries/public', caption: '<b>المكتبة العامة</b> ظاهرة لكل معلمي وطلاب مدرستك مع تقييم وتعليقات.', dwell: 4500 },
      { goto: '/admin/libraries/private', caption: '<b>المكتبة الخاصة</b> تُربط بفصل معين — بس طلابه يشوفوا محتواها.', dwell: 4000 },
      { goto: '/admin/libraries/labs', caption: '<b>المعامل الافتراضية</b> مصادر تفاعلية زي المحاكاة العلمية تربطها بمادة.', dwell: 4000 },
      { goto: '/manage/books', caption: 'شاشة <b>الكتب</b> منفصلة — ترفع كتاب المنهج كملف يقرأه الطالب أونلاين.', dwell: 4500 },
    ],
  },
  {
    id: 'V2.11', track: '2', title: 'الحضور والغياب', role: 'school-admin',
    steps: [
      { goto: '/admin/attendance', caption: '<b>غياب الطلاب</b> فيه لوحة تحكم وتقارير، وقسم منفصل لغياب المعلمين.', dwell: 4500 },
      { goto: '/admin/attendance/students/daily', caption: 'فيه حضور <b>يومي</b> عام للطالب، وحضور لكل حصة يسجّله المعلم.', dwell: 4000 },
      { goto: '/admin/attendance/follow-up', caption: 'شاشة <b>متابعة التأخير والغياب</b> تجمع كل الحالات اللي محتاجة قرار إداري.', dwell: 4500 },
      { goto: '/admin/attendance/reports/status', caption: 'عدة <b>تقارير غياب</b> مجمّعة: الحالة العام، التأخير، وغياب أيام الفصول.', dwell: 4500 },
      { goto: '/admin/teacher-attendance/daily', caption: 'غياب <b>المعلم</b> نفسه بيدخل ضمن عوامل تقييم أدائه الوظيفي.', dwell: 4000 },
    ],
  },
  {
    id: 'V2.12', track: '2', title: 'التقييم والزيارات الصفية والأداء الوظيفي', role: 'school-admin',
    steps: [
      { goto: '/admin/evaluations', caption: 'من <b>نماذج التقييم</b> تنشئ استمارة ببنود ومؤشرات وتحدد مين هيتقيّم بيها.', dwell: 4500 },
      { goto: '/admin/my-evaluations', caption: 'من <b>التقييمات</b> تنفّذ تقييم فعلي على معلم، تحفظ مسودة وترسلها للاعتماد.', dwell: 4500 },
      { goto: '/admin/class-visits', caption: '<b>الزيارات الصفية</b>: زيارة دعم بدون درجة، وزيارة تقييم رسمية تؤثر على نقاط المعلم.', dwell: 4500 },
      { goto: '/admin/evaluations/approvals', caption: 'شاشة <b>اعتماد التقييمات</b> تراجع كل بند وتعتمد أو ترفض قبل الاحتساب الرسمي.', dwell: 4500 },
      { goto: '/admin/job-performance', caption: '<b>الأداء الوظيفي</b> يجمع كل تقييمات المعلم في نتيجة إجمالية واحدة.', dwell: 4000 },
    ],
  },
  {
    id: 'V2.13', track: '2', title: 'التواصل: الإعلانات والرسائل وعلاقات العملاء', role: 'school-admin',
    steps: [
      { goto: '/admin/announcements', caption: 'أنشئ <b>إعلان</b> لكل المدرسة أو لفصل معين وحدد فترة ظهوره.', dwell: 4500 },
      { goto: '/admin/sms/send', caption: '<b>رسائل الجوال</b> ترسل فرديًا أو جماعيًا من ملف إكسل لأرقام كتير مرة واحدة.', dwell: 4500 },
      { goto: '/admin/whatsapp/send', caption: 'نفس الفكرة عبر <b>واتساب</b> — مع سجل رسائل وإعادة إرسال لأي رسالة فشلت.', dwell: 4000 },
      { goto: '/admin/parents-contact', caption: '<b>التواصل مع أولياء الأمور</b> يسجّل كل مكالمة وزيارة وشكوى في تاريخ واحد.', dwell: 4500 },
    ],
  },
  {
    id: 'V2.14', track: '2', title: 'الشهادات الإلكترونية', role: 'school-admin',
    steps: [
      { goto: '/admin/certificates', caption: 'كرّم طلابك ومعلميك بـ<b>شهادات إلكترونية</b> تشاركها بواتساب أو إيميل.', dwell: 4500 },
      { goto: '/admin/certificate-templates', caption: 'قبل الإصدار جهّز <b>قالب</b>: التصميم والألوان ومكان شعار المدرسة.', dwell: 4500 },
      { goto: '/admin/certificate-templates/create', caption: 'أنشئ قالب جديد وحدد شكله — شعار مدرستك هيظهر عليه تلقائيًا.', dwell: 4000 },
      { goto: '/admin/certificates/issue', caption: 'اختر نوع الشهادة (تفوّق/شكر) والمستفيد والقالب، واكتب نص <b>التقدير</b>.', dwell: 4500 },
    ],
  },
  {
    id: 'V2.15', track: '2', title: 'أدوات إضافية', role: 'school-admin',
    steps: [
      { goto: '/admin/surveys', caption: '<b>الاستبيانات</b> تُنشئ لأي فئة، تُنشر، وتشوف نتائجها بعد الإغلاق.', dwell: 4500 },
      { goto: '/admin/appointment-settings', caption: '<b>إعدادات المواعيد</b> تتحكم في فتح وإقفال خدمة الحجز وقواعدها العامة.', dwell: 4000 },
      { goto: '/admin/behavior/groups', caption: 'قسم <b>السلوك</b>: مجموعة عامة، سلوكيات محددة، وإجراءات رد الفعل المناسب.', dwell: 4500 },
      { goto: '/admin/educational-sites', caption: '<b>مواقع تعليمية</b> روابط خارجية مفيدة تضيفها وترتّب ظهورها للمعلمين والطلاب.', dwell: 4500 },
      { goto: '/admin/admissions', caption: '<b>القبول والتسجيل</b> يدير طلبات الالتحاق: مراجعة، مقابلة، وتحويل لحساب طالب.', dwell: 4000 },
    ],
  },

];
