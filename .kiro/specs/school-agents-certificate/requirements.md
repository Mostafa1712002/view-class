# Requirements: several agents per school + agent choice on the certificate

Trello PU6Djwic (reopened 11-24) + 4VSFrDk0. Tester: Mohamed Farouk.

Farouk: «لا بد من ربط المدرسة بالوكيل والمدير لأن تلك البيانات تطبع على شهادة الطالب … مع ضرورة ارفاق صورة لختم المدرسة … وايضا امكانية اختيار الوكيل. ممكن كلهم او ممكن واحد علي حسب الرغبة».
Meeting 4 (04:35–05:37): «ممكن أكثر من وكيل … يقول لي عايز اسمه يطبع على الشهادة ولا لا».

- WHEN an admin adds/edits a school THE SYSTEM SHALL allow linking one manager and any number of agents (job title «وكيل»), and a stamp image (jpg/png/webp, ≤2 MB).
- WHEN agents are linked THE SYSTEM SHALL let the admin mark which of them print on certificates by default.
- WHEN issuing certificates THE SYSTEM SHALL list the school's linked agents as checkboxes, pre-ticked by the default, and print exactly the ticked ones (all, some, one, or none).
- WHEN a super-admin issues with «all schools» THE SYSTEM SHALL print the manager, default agents and stamp of the student's school.
- Existing single agent (schools.agent_user_id) SHALL be carried into the new link table.

Design: table `school_agents` (school_id, user_id, print_on_certificate; PK pair; no FK like the other user links). `certificates.agent_user_ids` JSON = agents chosen at issue (null = school default; [] + print_agent=0 = none). `schools.agent_user_id` keeps mirroring the first agent.
Not built: several managers (meeting: «لا بس المدير»); an automatic certificate on finishing a «الأول بلس» program (no such certificate exists yet — asked on the card).
