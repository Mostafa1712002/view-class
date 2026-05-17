# Tasks: Trello Card #51 — المدارس

## Phase 1: schools/create — missing selects

### Task 1.1: Add config + migration + model
- [ ] config/saudi_cities.php (~100 cities, name_ar + name_en)
- [ ] migration adds: student_gender (enum boys/girls/mixed), timezone (string default Asia/Riyadh), branch_id (FK to school_branches)
- [ ] School model fillable updated

### Task 1.2: Patch SchoolController validation
- [ ] stage rule → in:primary,intermediate,secondary
- [ ] student_gender rule → in:boys,girls,mixed
- [ ] timezone rule → in:<timezone whitelist>
- [ ] branch_id rule → nullable|exists:school_branches,id
- [ ] city rule → in:<config city codes>

### Task 1.3: Replace inputs with selects in _form.blade.php
- [ ] stage → select (3 options)
- [ ] city → searchable select2 (Saudi cities)
- [ ] student_gender → new select
- [ ] timezone → new select
- [ ] branch (text) → branch_id select

## Phase 2: Branches module

### Task 2.1: Module scaffold
- [ ] app/Modules/SchoolBranches/{Actions,Controllers,Repositories/Contracts}
- [ ] migration create_school_branches_table
- [ ] SchoolBranch model

### Task 2.2: Repository + Actions
- [ ] SchoolBranchRepository contract + Eloquent impl
- [ ] CreateBranchAction, UpdateBranchAction, DeleteBranchAction
- [ ] Bind in RepositoryServiceProvider

### Task 2.3: Controller + routes + views
- [ ] SchoolBranchController index/create/store/edit/update/destroy
- [ ] routes/web.php under admin prefix
- [ ] index.blade.php (table)
- [ ] create.blade.php, edit.blade.php, _form.blade.php

### Task 2.4: Wire schools index button
- [ ] href="#" → route('admin.school-branches.index')

## Phase 3: SMS Services module

### Task 3.1: Module scaffold + migrations
- [ ] app/Modules/SmsServices/{Actions,Controllers,Repositories/Contracts}
- [ ] migration school_sms_settings (school_id, api_key, api_secret, default_sender_id, is_active, sms_used, sms_total)
- [ ] migration sms_senders (school_id, name_ar, name_en, status pending/approved/rejected)
- [ ] migration sms_sender_attachments (sender_id, provider, file_path)

### Task 3.2: Models + repository
- [ ] SchoolSmsSetting, SmsSender, SmsSenderAttachment models
- [ ] SmsSettingsRepository contract + Eloquent impl

### Task 3.3: Controllers + routes + views
- [ ] SmsServicesController index (table of all schools with sms balance/status)
- [ ] SmsConnectionController editConnection/updateConnection (api key/secret + activate)
- [ ] SmsDefaultSenderController editDefault/updateDefault
- [ ] SmsSenderRequestController create/store (sender name request)
- [ ] views in resources/views/admin/sms-services/
- [ ] Each blade sets @section('body_class', 'theme-light')

### Task 3.4: Wire schools index button
- [ ] "الخدمات الإضافية" href → route('admin.sms-services.index')

## Phase 4: Languages + deployment + verification

### Task 4.1: Lang keys
- [ ] ar + en additions for branches CRUD, sms services screens

### Task 4.2: Commit + push + deploy
- [ ] git add + commit + push
- [ ] One SSH deploy block

### Task 4.3: Playwright live verification
- [ ] login as developer@midade.com
- [ ] /admin/schools/create — all selects render
- [ ] /admin/schools — Branches button works, Additional Services button works
- [ ] /admin/school-branches — index renders, create works
- [ ] /admin/sms-services — index renders
- [ ] Connection settings modal/page
- [ ] Default sender modal/page
- [ ] Sender name request page

### Task 4.4: Trello close
- [ ] Arabic comment
- [ ] Move to testing prompt + reassign creator

## Out of scope (flag in report)
- Real SMS gateway integration (no API creds)
- Downloadable PDF templates for STC/Mobily/Zain
- Sender approval workflow stages (single status enum only)
- Test-connection button
- Message log / sending history
- Educational tracks CRUD (track enum stays as system-managed values)
