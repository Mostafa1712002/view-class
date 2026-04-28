# ViewClass — Project Architecture Rules

These rules apply to **all new code** in this repository. Existing Sprint 1–10 code predates them and is considered legacy; refactor opportunistically when touching it.

## Module Pattern

All new feature code lives under `app/Modules/<ModuleName>/` organised by feature, not by file type.

```
app/Modules/Auth/
├── Actions/           # single-purpose business logic
├── Controllers/       # thin HTTP layer, delegate to Actions
├── DTOs/              # input/output data carriers (readonly classes)
├── Http/
│   ├── Requests/      # form request validation
│   └── Resources/     # API response transformers
├── Repositories/      # data access (interface + implementation)
├── Routes/            # web.php / api.php
├── Services/          # orchestration when a single Action isn't enough
└── routes.php         # optional: feature-scoped route registration
```

A module owns its migrations, models, routes, and views. Cross-module calls go through the target module's public Actions/Repositories, never its internals.

## Repository Pattern

All database access for new modules goes through a repository.

- Every repository has an **interface** in `Repositories/Contracts/` and a concrete implementation alongside it.
- Bind the interface → implementation in `App\Providers\RepositoryServiceProvider`.
- Controllers and Actions type-hint the interface.
- Eloquent lives **inside** the repository — never in controllers or actions.

```php
// app/Modules/Auth/Repositories/Contracts/UserRepository.php
interface UserRepository {
    public function findByUsernameOrEmail(string $identifier): ?User;
    public function recordLogin(User $user): void;
}
```

## Action Pattern

Business logic is encoded as single-purpose action classes invoked from controllers.

- One action, one public `execute(...)` method.
- Dependencies injected via constructor (repositories, services, mailers).
- Actions return DTOs or models, never `Response`.
- Controllers coordinate the HTTP concern; actions coordinate the domain concern.

```php
// app/Modules/Auth/Actions/LoginAction.php
final class LoginAction {
    public function __construct(private UserRepository $users) {}
    public function execute(LoginDto $dto): TokenPair { ... }
}
```

## API Response Envelope

Every `/api/*` endpoint (including module routes) returns the unified envelope:

```json
// success
{ "success": true,  "data": {...}, "message": "..." }

// failure
{ "success": false, "error": { "code": "ERROR_CODE", "message": "..." } }
```

Use `App\Support\ApiResponse::ok($data)` / `::fail($code, $message, $status)`.

## Other rules that are load-bearing

- **Multi-tenant scope**: every query that touches school-owned data filters by the authenticated user's `school_id` — enforced in repositories, not scattered in controllers.
- **Soft deletes** on `users` and `schools`; new tenant-owned entities should default to soft delete.
- **Rate limits**: auth endpoints throttled (`5/min` on login). Configure in route definitions.
- **Password hashing**: bcrypt, Laravel default rounds (10+) is fine. Never commit plain passwords.
- **Commits**: conventional subject line; explain the "why" in the body if non-obvious. Never mention AI.
- **Deploy flow**: commit → push → SSH → `git pull` → `migrate --force` → `view:cache`. No scp.

## Project board (Trello)

Feature cards, bugs, and sprint work for ViewClass live on this single Trello board. Read tasks from here, never invent work that isn't on the board.

- **Board name**: `فيوكلاس`
- **Board ID**: `69ccec0d70b4de73208c0716`
- **Short link**: `WBHlx52A`
- **URL**: https://trello.com/b/WBHlx52A/%D9%81%D9%8A%D9%88%D9%83%D9%84%D8%A7%D8%B3
- **Lists** (left → right):
  - `sprint prompt` — `69ccec3d278382c2dfec0481` (new sprint cards waiting to be picked up)
  - `coding prompt` — `69ccec5e717a8c75e4132bae` (in active development)
  - `prompt done` — `69ccec6450ee70145325d982` (coding finished, awaiting deploy)
  - `testing prompt` — `69f0774def6235774c7409c5` (deployed, awaiting QA verification)
  - `testing done` — `69ccec6e16e7c02381c8ddb0` (QA-verified, closed)

Workflow: pick from `sprint prompt` → move to `coding prompt` while building → ship to live → move to `testing prompt` and add an Arabic QA comment → reassign to the card creator. Developer never advances to `testing done` — only QA does that.

## Legacy notes

- Primary keys are `int`, not UUID. Full UUID migration was considered out of scope for the currently deployed DB.
- Session-based Blade auth (`/login`) and JWT API auth (`/api/auth/login`) run in parallel. Do not collapse them.
- Existing `App\Models\*` sit at the project root (not inside modules). Leave in place; treat as shared domain models.
