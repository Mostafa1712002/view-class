# Design: QB Excel import — passages

- Reuses QuestionBankCore `passages` + `passage_questions` (sort_order) + `bank_questions.passage_id`. No migration.
- Template: passage row (question_type = قطعة, text in question_text, image in question_image) followed by its sub-questions (question_category = passage). Chosen over a «رقم القطعة» column: no ID to invent and repeat by hand, and the sheet reads like the paper.
- ParseQuestionsExcel: passage rows carry `is_passage`; children carry `passage_row` + `passage_sort`; `checkPassages()` runs the structural checks and the all-or-nothing rule.
- ImportFromPreview: Passage created with the first imported child (status rule = PassageController::store); children attached in sheet order.
- Rendering: legacy exams via `ExamQuestion::sourcePassage()` (source bank question, withTrashed); QB exams via `question_snapshot.passage_*`; shared partial `partials/qb-passage`.
- Behaviour change: a question_category=passage row with no passage above it used to import as a standalone question; it is now an error.
