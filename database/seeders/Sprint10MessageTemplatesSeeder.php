<?php

namespace Database\Seeders;

use App\Models\School;
use App\Modules\SmsServices\Models\SmsTemplate;
use Illuminate\Database\Seeder;

/**
 * Trello #271 — seed the message templates the card enumerates (student +
 * teacher) as real SmsTemplate rows so Sprint-10 screens can pick them.
 *
 * Idempotent: keyed on (school_id, title) so re-running won't duplicate.
 * Templates are reusable SmsTemplate records — NOT a new messaging system.
 */
class Sprint10MessageTemplatesSeeder extends Seeder
{
    /** @var array<int, array{title:string, body:string}> */
    private array $templates = [
        // ── نماذج رسائل الطلاب ─────────────────────────────────────────
        ['title' => 'غياب حصة', 'body'  => 'عزيزي ولي الأمر، نُعلمكم بغياب الطالب/ة {student_name} عن حصة {subject} ({period}) بتاريخ {date}. {school_name}'],
        ['title' => 'غياب يوم', 'body'  => 'عزيزي ولي الأمر، نُعلمكم بغياب الطالب/ة {student_name} عن الدوام بتاريخ {date}. {school_name}'],
        ['title' => 'تأخير',    'body'  => 'عزيزي ولي الأمر، تأخر الطالب/ة {student_name} عن الحضور بتاريخ {date}. {school_name}'],
        ['title' => 'استئذان',  'body'  => 'عزيزي ولي الأمر، استأذن الطالب/ة {student_name} من المدرسة بتاريخ {date}. {school_name}'],
        ['title' => 'شهادة',    'body'  => 'عزيزي ولي الأمر، تم إصدار شهادة الطالب/ة {student_name}. يمكنكم الاطلاع عليها عبر الرابط: {report_link}. {school_name}'],
        ['title' => 'قبول مبدئي', 'body' => 'عزيزي ولي الأمر، تم قبول طلب التحاق الطالب/ة {student_name} مبدئياً. {school_name}'],
        ['title' => 'رفض',      'body'  => 'عزيزي ولي الأمر، نأسف لإبلاغكم بعدم قبول طلب التحاق الطالب/ة {student_name}. {school_name}'],
        ['title' => 'موعد مقابلة', 'body' => 'عزيزي ولي الأمر، موعد مقابلة الطالب/ة {student_name} بتاريخ {date}. {school_name}'],

        // ── نماذج رسائل المعلمين ────────────────────────────────────────
        ['title' => 'تقرير يومي (معلم)',   'body' => 'الأستاذ/ة {teacher_name}، هذا تقريركم اليومي بتاريخ {date}. {school_name}'],
        ['title' => 'تقرير أسبوعي (معلم)', 'body' => 'الأستاذ/ة {teacher_name}، هذا تقريركم الأسبوعي. {school_name}'],
        ['title' => 'تقرير شهري (معلم)',   'body' => 'الأستاذ/ة {teacher_name}، هذا تقريركم الشهري. {school_name}'],
    ];

    public function run(): void
    {
        // Seed for every existing school so the picker has rows under each tenant.
        $schoolIds = School::query()->pluck('id');

        foreach ($schoolIds as $schoolId) {
            foreach ($this->templates as $t) {
                SmsTemplate::updateOrCreate(
                    ['school_id' => $schoolId, 'title' => $t['title']],
                    ['body' => $t['body'], 'lang' => 'ar', 'is_active' => true]
                );
            }
        }
    }
}
