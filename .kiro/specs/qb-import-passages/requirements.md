# Requirements: QB Excel import — passages («قطعة»)

- WHEN a Questions-sheet row has question_type «قطعة»/passage THE SYSTEM SHALL treat it as a passage and the following question_category=passage rows as its sub-questions, in sheet order.
- WHEN a passage has no sub-questions THE SYSTEM SHALL reject it with an Arabic error naming its row.
- WHEN a question_category=passage row has no passage above it THE SYSTEM SHALL reject it with an Arabic error.
- WHEN any row of a passage is invalid THE SYSTEM SHALL reject the whole passage, naming the bad rows.
- WHEN a file has no passage rows THE SYSTEM SHALL import it exactly as before.
- WHEN exam/preview pages show passage sub-questions THE SYSTEM SHALL show the passage once above each run of them.
