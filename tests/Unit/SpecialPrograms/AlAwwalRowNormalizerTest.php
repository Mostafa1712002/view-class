<?php

namespace Tests\Unit\SpecialPrograms;

use App\Modules\SpecialPrograms\Actions\Import\AlAwwalRowNormalizer;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class AlAwwalRowNormalizerTest extends TestCase
{
    private function opts(array $texts, int $correct): array
    {
        return array_map(fn ($t, $i) => ['text' => $t, 'is_correct' => $i === $correct], $texts, array_keys($texts));
    }

    private function independent(array $over = []): array
    {
        return $over + [
            'id' => 30929, 'is_independent' => 'independent', 'skill_id' => 6216, 'difficulty' => 'easy',
            'term' => 'first', 'week' => 1, 'question_image' => null, 'question_order' => null,
            'question_text' => json_encode(['كتاب : ورقة'], JSON_UNESCAPED_UNICODE),
            'options' => json_encode($this->opts(['فصل : مدرسة', 'رأس : عين', 'باب : خشب', 'نهر : شاطئ'], 1), JSON_UNESCAPED_UNICODE),
        ];
    }

    public function test_independent_row(): void
    {
        $r = (new AlAwwalRowNormalizer)->normalize($this->independent());

        $this->assertSame(30929, $r['source_id']);
        $this->assertFalse($r['is_passage']);
        $this->assertSame(1, $r['difficulty']);
        $this->assertSame('first', $r['term']);
        $this->assertCount(1, $r['items']);
        $this->assertSame('كتاب : ورقة', $r['items'][0]['stem']);
        $this->assertSame(1, $r['items'][0]['correct_index']);
        $this->assertSame(6216, $r['items'][0]['skill_source_id']);
        $this->assertFalse($r['items'][0]['generic_stem']);
    }

    public function test_br_tags_become_line_breaks(): void
    {
        $r = (new AlAwwalRowNormalizer)->normalize($this->independent([
            'question_text' => json_encode(['حصل على 30<br /> في الاختبار<BR>الخامس'], JSON_UNESCAPED_UNICODE),
            'options' => json_encode($this->opts(['أ<br/>ب', 'ج', 'د', 'هـ'], 0), JSON_UNESCAPED_UNICODE),
        ]));
        $this->assertSame("حصل على 30\nفي الاختبار\nالخامس", $r['items'][0]['stem']);
        $this->assertSame("أ\nب", $r['items'][0]['options'][0]);
    }

    public function test_image_row_generic_stems_and_third_term(): void
    {
        $n = new AlAwwalRowNormalizer;
        $r = $n->normalize($this->independent([
            'id' => 30953, 'term' => 'third', 'difficulty' => 'hard', 'question_image' => 'uploads/questions/question_1_a.png',
            'question_text' => '["اختر"]',
            'options' => json_encode($this->opts(['أ', 'ب', 'ج', 'د'], 0), JSON_UNESCAPED_UNICODE),
        ]));
        $this->assertSame('both', $r['term']);
        $this->assertSame(3, $r['difficulty']);
        $this->assertTrue($r['items'][0]['generic_stem']);
        $this->assertSame('uploads/questions/question_1_a.png', $r['image']);

        foreach (['اختر', 'اختر الإجابة الصحيحة', 'اختر الاجابة الصحيحة'] as $s) {
            $this->assertTrue($n->isGenericStem($s), $s);
        }
        $this->assertFalse($n->isGenericStem('اختر الإجابة الخاطئة'));
    }

    public function test_tahsili_row_with_null_skill(): void
    {
        $r = (new AlAwwalRowNormalizer)->normalize($this->independent([
            'id' => 21988, 'skill_id' => null, 'week' => null, 'question_order' => 387, 'difficulty' => 'medium', 'term' => 'both',
            'question_text' => '["اختر الاجابة الصحيحة"]',
            'options' => json_encode($this->opts(['أ', 'ب', 'ج', 'د'], 2), JSON_UNESCAPED_UNICODE),
        ]));
        $this->assertSame(387, $r['question_order']);
        $this->assertNull($r['items'][0]['skill_source_id']);
        $this->assertSame(2, $r['items'][0]['correct_index']);
        $this->assertTrue($r['items'][0]['generic_stem']);
    }

    public function test_stray_wrapper_and_literal_newline(): void
    {
        $r = (new AlAwwalRowNormalizer)->normalize($this->independent([
            'id' => 18469,
            'question_text' => json_encode(['["السطر الأول\nالثاني"]'], JSON_UNESCAPED_UNICODE),
        ]));
        $this->assertSame("السطر الأول\nالثاني", $r['items'][0]['stem']);
    }

    public function test_qiyas_passage_uses_row_skill(): void
    {
        $r = (new AlAwwalRowNormalizer)->normalize([
            'id' => 33155, 'is_independent' => 'passage', 'skill_id' => 6220, 'skills' => null,
            'passage_text' => ' عني أديسون ', 'difficulty' => 'medium', 'term' => 'second', 'week' => 3,
            'question_text' => json_encode(['عدد مكونات جهاز التليغراف:', 'علاقة جملة بما قبلها:'], JSON_UNESCAPED_UNICODE),
            'options' => json_encode([$this->opts(['2', '3', '4', '6'], 1), $this->opts(['تعليل', 'تمثيل', 'شرط', 'توضيح'], 0)], JSON_UNESCAPED_UNICODE),
        ]);
        $this->assertTrue($r['is_passage']);
        $this->assertSame('عني أديسون', $r['passage_text']);
        $this->assertCount(2, $r['items']);
        $this->assertSame([0, 1], array_column($r['items'], 'sub_index'));
        $this->assertSame([1, 0], array_column($r['items'], 'correct_index'));
        $this->assertSame([6220, 6220], array_column($r['items'], 'skill_source_id'));
    }

    public function test_double_encoded_nafis_passage_with_per_index_skills(): void
    {
        $texts = ['قد يصدر الخطأ من:', 'وقوع الخطأ قد يؤدي إلى:', 'معنى كلمة الثرى'];
        $opts = array_map(fn ($c) => $this->opts(['أ', 'ب', 'ج', 'د'], $c), [0, 3, 2]);
        $double = fn ($v) => json_encode(json_encode($v, JSON_UNESCAPED_UNICODE));

        $r = (new AlAwwalRowNormalizer)->normalize([
            'id' => 17959, 'is_independent' => 'passage', 'skill_id' => null, 'passage_text' => "خلق\nالاعتذار",
            'difficulty' => 'medium', 'term' => 'both',
            'question_text' => $double($texts), 'options' => $double($opts), 'skills' => $double(['4489', '4489', '4491']),
        ]);
        $this->assertCount(3, $r['items']);
        $this->assertSame([4489, 4489, 4491], array_column($r['items'], 'skill_source_id'));
        $this->assertSame([0, 3, 2], array_column($r['items'], 'correct_index'));
        $this->assertSame($texts[2], $r['items'][2]['stem']);
    }

    public function test_zero_or_two_correct_options_throw(): void
    {
        $n = new AlAwwalRowNormalizer;
        foreach ([[], [0, 1]] as $correct) {
            $opts = array_map(fn ($i) => ['text' => "o{$i}", 'is_correct' => in_array($i, $correct, true)], [0, 1, 2, 3]);
            try {
                $n->normalize($this->independent(['options' => json_encode($opts)]));
                $this->fail('expected exception');
            } catch (InvalidArgumentException $e) {
                $this->assertStringContainsString('exactly 1 correct', $e->getMessage());
            }
        }
    }

    public function test_undecodable_json_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AlAwwalRowNormalizer)->normalize($this->independent(['question_text' => '{not json']));
    }

    public function test_passage_length_mismatch_throws(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new AlAwwalRowNormalizer)->normalize([
            'id' => 1, 'is_independent' => 'passage', 'skill_id' => 6220, 'difficulty' => 'easy',
            'question_text' => '["a","b"]', 'options' => json_encode([$this->opts(['x', 'y'], 0)]),
        ]);
    }
}
