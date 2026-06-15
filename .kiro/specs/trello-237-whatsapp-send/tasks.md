# Tasks: WhatsApp Send / Compose (Trello #237)

## Phase 1: Data layer
- [x] Migration: `whatsapp_broadcasts` table (parent record per send)
- [x] Migration: extend `whatsapp_logs` (nullable broadcast_id, recipient_user_id, message_type, media_path) + extend status enum additively
- [x] Model: `WhatsappBroadcast`
- [x] Update `WhatsappLog` model (fillable + relations + status labels)

## Phase 2: Recipients + sending
- [x] DTO: `ComposeMessageDto`
- [x] Repository: `RecipientRepository` (contract + Eloquent) — null-safe school scope, class via section.school_id
- [x] Action: `ResolveRecipientsAction` (group → user list, dedupe, number status)
- [x] Action: `SendBroadcastAction` (persist broadcast + per-recipient logs, drive existing WhatsappService/driver, ActivityLog)
- [x] Add media-aware send to driver interface + both drivers (text real via log, media stored+logged)

## Phase 3: HTTP + UI
- [x] FormRequest: `ComposeMessageRequest` (validation, file rules, sanitize)
- [x] Controller: `WhatsappSendController` (create, resolveRecipients ajax, store) — gate canDo('whatsapp.send')
- [x] Routes: `app/Modules/Whatsapp/Routes/web.php`
- [x] View: compose page (RTL, radio msg-type, dynamic image/pdf upload, recipient dropdown, selected-users panel)
- [x] Lang entries

## Phase 4: Wiring + verify
- [x] Bind RecipientRepository in RepositoryServiceProvider
- [x] Temporarily require routes file → verify → revert (report require line)
- [x] Playwright: super-admin null-school 200, compose text + recipients + submit persisted+logged, image/pdf options, 403 for permission-less user

## Progress
| Phase | Status |
|-------|--------|
| 1 Data | Done |
| 2 Logic | Done |
| 3 UI | Done |
| 4 Verify | Done |
