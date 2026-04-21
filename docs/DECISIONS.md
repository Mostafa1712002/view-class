# ViewClass - Project Decisions Log

## Session: November 30, 2025

### Decision 1: Development Strategy
**Choice:** Start with Sprint 1 (Foundation)
**Rationale:** Begin with authentication, roles, and basic admin dashboard - recommended approach for new projects
**Impact:** Sequential development starting from core infrastructure

### Decision 2: Dashboard UI Template
**Choice:** Use existing HTML template
**Source:** `/home/mostafa/www/ecommerce-dashboard/html/rtl/vertical-menu-template/`
**Features Available:**
- RTL support (Arabic ready)
- LTR support (English)
- Charts (C3 charts)
- Forms
- Tables
- Cards
- Calendars
- Drag & Drop
- Image Cropper
- Multiple layout options

### Decision 3: Notification Channels
**Choice:** Email Only (for initial release)
**Rationale:** Lower cost, sufficient for MVP
**Future:** Can add SMS/WhatsApp in Phase 2

---

## Technical Stack Confirmed

| Component | Technology |
|-----------|------------|
| Framework | Laravel 12.40.2 |
| PHP | 8.3.28 |
| Database | MySQL (viewclass) |
| Frontend | Blade + Existing Template |
| CSS | Template CSS (RTL/LTR) |
| Charts | C3.js (from template) |
| Notifications | Laravel Mail |

---

## Next Steps (Sprint 1)

1. Copy dashboard template assets to Laravel public folder
2. Convert main layout to Blade
3. Create authentication system with multi-role support
4. Build role & permission system
5. Create school/section/class management
6. Implement Arabic/English language switching

---

## Template Integration Plan

```
/home/mostafa/www/ecommerce-dashboard/html/rtl/vertical-menu-template/
                    │
                    ▼
/home/mostafa/www/viewclass/
├── public/
│   ├── css/          ← Template CSS
│   ├── js/           ← Template JS
│   ├── fonts/        ← Template fonts
│   └── images/       ← Template images
├── resources/views/
│   ├── layouts/
│   │   ├── app.blade.php      ← Main template converted
│   │   └── auth.blade.php     ← Auth pages layout
│   └── components/            ← Reusable components
```
