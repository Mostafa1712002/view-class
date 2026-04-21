# Trello Sprint 1 — فيوكلاس board execution

Source: https://trello.com/b/WBHlx52A/فيوكلاس
Strategy: Option C — add missing pieces on top of Laravel until each card's observable behaviors match, then comment + move to `testing done`.

## Fixed calls
- Stack: Laravel stays (10 sprints already committed)
- IDs: int (UUID migration too risky; noted in card 2 comment)
- Auth: dual — session for Blade, JWT added for `/api/auth/login` API
- Username: new column alongside email; seed `admin` / `Admin@12345`

## Slice 1 — Multi-tenant foundation (cards 1 + 2)
- [x] 1.1 Migration: `educational_companies` table
- [x] 1.2 Migration: add multi-tenant + spec columns to `schools` (name_ar, name_en, branch, sort_order, educational_track, stage, city, default_language, fax, socials, cover_image, soft deletes, educational_company_id FK)
- [x] 1.3 Migration: add spec columns to `users` (name_ar, name_en, username unique, language_preference, status enum, soft deletes)
- [x] 1.4 Models: EducationalCompany + update User/School relationships
- [x] 1.5 Seeder: DemoCompanySeeder + update AdminUserSeeder (username=admin, password=Admin@12345)
- [x] 1.6 Commit, push, deploy (pull on server, migrate, seed)
- [x] 1.7 Live verification with Playwright — can log in with username `admin` / `Admin@12345`
- [x] 1.8 Arabic comment on card 1 + move to testing done
- [x] 1.9 Arabic comment on card 2 (note int IDs instead of UUID) + move to testing done
