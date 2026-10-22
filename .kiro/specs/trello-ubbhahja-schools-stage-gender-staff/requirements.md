# Requirements: Schools — stage/gender columns, manager/agent link, stamp (Trello UbbHAHja)

## Round 1 (2026-10-22, commits c100d1f, 2672ecd)
- WHEN the admin opens /admin/schools THE SYSTEM SHALL show «المرحلة التعليمية» and «الجنس» columns after المجمعة التعليمية, with filters.

## Round 2 (2026-10-22, Mohamed Farouk)
- WHEN the admin creates/edits a school THE SYSTEM SHALL let him pick the school manager (المدير) and vice principal (الوكيل) from existing staff.
- WHEN the admin uploads a stamp image (ختم المدرسة) THE SYSTEM SHALL store it and show it on the school page.
- WHEN a certificate is rendered for a student of that school THE SYSTEM SHALL print the manager and agent names and the school stamp (unless the certificate has its own signer/stamp).
- THE SYSTEM SHALL NOT offer «مختلط» in «نوع الطلاب» on the school form (legacy mixed schools keep the value and stay editable).
- THE SYSTEM SHALL place the select arrow on the left of every select2 dropdown in Arabic RTL.
