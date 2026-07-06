// Per-video recording flows. Captions are trusted (authored here), rendered as
// on-screen Arabic subtitles by record.js. `role` = which account to log in as
// (null = no login, e.g. the login screen itself). dwell in ms.
module.exports = [

  // ── Track 0: shared ─────────────────────────────────────────────
  {
    id: 'V0.1', track: '0', title: 'تسجيل الدخول', role: null,
    steps: [
      { goto: '/login', caption: 'أهلاً بك في <b>المنصة الذهبية</b> — تعلّم إزاي تدخل حسابك.', dwell: 4500 },
      { caption: 'اكتب <b>بريدك أو اسم المستخدم</b> في الخانة الأولى.', dwell: 4000 },
      { caption: 'واكتب <b>كلمة المرور</b> اللي استلمتها من مدرستك.', dwell: 4000 },
      { caption: 'فعّل <b>تذكرني</b> على جهازك الخاص فقط، ثم اضغط <b>تسجيل الدخول</b>.', dwell: 4500 },
      { caption: 'النظام هيوصّلك تلقائيًا للوحة التحكم الخاصة بدورك.', dwell: 4000 },
    ],
  },
  {
    id: 'V0.2', track: '0', title: 'التنقّل في الواجهة', role: 'school-admin',
    steps: [
      { goto: '/dashboard', caption: 'دي واجهة النظام بعد الدخول — تعال نتعرّف عليها.', dwell: 4500 },
      { caption: 'على <b>اليمين</b> القائمة الجانبية — منها توصل لكل شاشات النظام.', dwell: 4500 },
      { caption: 'في <b>الأعلى</b>: اختيار المدرسة والعام، البريد، اللغة، والبحث.', dwell: 4500 },
      { caption: 'أيقونة <b>الجرس</b> إشعاراتك، و<b>صورتك</b> تفتح بياناتك وزر الخروج.', dwell: 4500 },
      { scroll: 500, caption: 'لوحة التحكم بتلخّص لك مؤشرات مدرستك بسرعة.', dwell: 4000 },
    ],
  },
  {
    id: 'V0.3', track: '0', title: 'صندوق البريد الداخلي', role: 'school-admin',
    steps: [
      { goto: '/my/mailbox', caption: '<b>البريد الداخلي</b> — قناة التواصل الرسمية جوّه النظام.', dwell: 4500 },
      { caption: 'على اليمين <b>مجلداتك</b>: الوارد، المرسل، المسودات، الأرشيف…', dwell: 4500 },
      { caption: 'فوق تلاقي <b>بحث وتصفية</b> — وخيار «غير مقروءة فقط».', dwell: 4500 },
      { caption: 'لإرسال رسالة اضغط <b>«رسالة جديدة»</b>، اختر المستلم وارفق ملف.', dwell: 4500 },
    ],
  },
  {
    id: 'V0.4', track: '0', title: 'المواعيد وحجز موعد', role: 'school-admin',
    steps: [
      { goto: '/my/appointments', caption: 'شاشة <b>مواعيدي</b> — احجز وتابع مواعيدك مع المدرسة.', dwell: 4500 },
      { caption: 'صفِّ مواعيدك <b>بالتاريخ أو الحالة</b> واضغط «تطبيق».', dwell: 4500 },
      { caption: 'لحجز موعد اضغط <b>«حجز موعد جديد»</b> واختر الوقت المتاح.', dwell: 4500 },
      { caption: 'بعد الحجز يظهر موعدك بحالته: قيد الانتظار، مؤكَّد، أو ملغى.', dwell: 4000 },
    ],
  },

];
