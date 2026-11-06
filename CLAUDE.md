# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

AngelAdmin2 is a CakePHP 5.4 (PHP 8.4+) server-rendered app for sports-club administration: members, teams, sites (cities), meetings/attendance, billing with Swiss QR bills, shop orders. UI is BootstrapUI + Bootstrap + jQuery. `.github/copilot-instructions.md` holds the full agent rule set; the important parts are summarized below.

## Commands

```bash
composer install                 # then copy .env.example to .env (SECURITY_SALT is required); config/app.php reads all settings from env
bin/cake server -p 8765          # dev server
bin/cake migrations migrate      # apply DB migrations

composer test                    # full PHPUnit suite
./vendor/bin/phpunit tests/TestCase/Controller/MembersControllerTest.php   # one file
./vendor/bin/phpunit --filter testIndex                                    # by name
composer cs-check                # phpcs (CakePHP standard)
composer cs-fix                  # phpcbf
composer check                   # test + cs-check

bin/cake i18n extract --paths src,templates --output resources/locales/src --overwrite --extract-core yes   # refresh .pot
resources/build-locales.sh       # compile all .po -> .mo (needs gettext/msgfmt)
```

The root `_*.bat` files are Windows wrappers for the same commands. CI (`.github/workflows/phpunit.yml`) runs `composer test` on PHP 8.4.

## Tests

- No MySQL needed: the `test` datasource is SQLite at `tmp/tests.sqlite` (override with `DATABASE_TEST_URL`). `tests/bootstrap.php` aliases `default` → `test` and builds the schema by running `config/Migrations`; fixtures in `tests/Fixture` only insert rows. **A new table or column must have a migration, or tests won't see it.**
- Some baked tests are still marked incomplete/skipped (`I`/`S` in output); they don't fail the run.

## Architecture

**Request pipeline** (`src/Application.php`): ErrorHandler → `SecurityMiddleware` → Asset → Routing → BodyParser → `MaintenanceMiddleware` → Authentication → Authorization → CSRF. Routes are just `DashedRoute` fallbacks (`/controller-name/action/...`) plus the home page in `config/routes.php`.

**Two ways to be identified:**
1. Normal login: Session + Form authenticators on the `Users` table (finder `withRoleMembers`, so the identity carries its `Role`). Each `User` is linked to a `Member` (`member_id`).
2. Hash access: members without an account open links containing a 40-hex SHA-1 `Members.hash` (as the first passed param), which is then kept in a `member_hash` cookie. Handled in `AppController::initializeHashAuthentication()`; actions that allow it are whitelisted with `addUnauthenticatedActions` (e.g. `MembersController`: `myPage`, `register`, `edit`, `addPhoto`, ...). Use `canAccessMember()`, `memberUrl()` and `redirectMember()` so hash users keep working. Don't break this flow when touching member pages.

**`AppController` is the hub** (`src/Controller/AppController.php`). On every request it:
- Loads the `Configurations` table as a key/value array into `$this->config` (with defaults: `year`, `lng`, `firstDaySeason`, `clubName`, enabled languages `fr/de/en/es/it`, computed `dateSeasonStart`) and exposes it to views as `$config`.
- Sets `$curUser`, `$curMember`, `$curRole` (and view vars `isLogged`, `curRole`, `curUser`, `isMobile`).
- Picks the locale: member's `language` → session `lng` → `config['lng']`, falling back if the language is disabled.
- Provides shared query builders used by many controllers: `getBills()`, `getLateBillsByMember()`, `getMembers()`, `getTeamsActiv()`, `getMembersEvo()`, etc.

**Site / team / filter preferences:** `src/Controller/PrefResult.php` resolves the page context. The site (city) comes from the member's `pref_site_id` (chosen in the nav menu, `0` = all sites) via `getSiteId()`; team and member filter are stored in session via `getPrefSession()` (value `null`/`-999` = reuse the stored one). Controllers typically do `$pref = new PrefResult($this, $teamId, $memberFilter)`. The site menu (`navSites`) is only shown to roles with `MemberViewAll` or `BillViewAll`.

**Authorization:** ORM policy resolver, one policy per entity in `src/Policy`. Permissions are boolean flags on the `Role` entity (`Admin`, `MemberViewAll`, `MemberEditAll`, `MemberEditOwn`, `BillViewAll`, ...). Controllers usually authorize against an empty entity, e.g. `$this->Authorization->authorize($this->Members->newEmptyEntity(), 'viewall')` → `MemberPolicy::canViewAll()`. Templates check `$curRole->...` directly.

**Reusable logic:** big controller features are split into components in `src/Controller/Component` (`MemberData`, `MemberList`, `MemberManagement`, `MemberRegistration`, `MemberDocument`, `Dashboard`, `Image` (incl. HEIC), `Mail`, `Slack`, `Zip`, `Get`). View helpers: `MyHelper` (icons, info grids, presence/language widgets, team links), `PdfHelper` (TCPDF), `DashboardHelper`. Shared member template fragments live in `templates/element/Members`. Prefer adding to these over duplicating code.

**Schema:** migration-first. `config/Migrations/20250921070000_CreateInitialTables.php` is the baseline; add new migrations instead of editing applied ones. `config/schema/*.sql` are legacy upgrade scripts.

## Conventions

- All user-facing strings are English in source, wrapped in `__()` / `__x()` with `{0}` placeholders; translations in `resources/locales/<lang>/default.po` (fr, de, es, it). French translations use tutoiement.
- **Follow the style guide** at `/admin/typography` (`templates/Admin/typography.php`, admin only) for every UI change. Tokens and action buttons live in `webroot/css/theme.css` (loaded globally after `cake.css`); keep the page, `theme.css` and this section in sync when a style rule changes.
  - Colors: use the `--aa-*` CSS variables (brand `--aa-brand`, `--aa-success`, `--aa-danger`, `--aa-warning`, `--aa-info`, `--aa-nav`, neutrals), never a hardcoded hex in templates. Domain CSS files should derive their variables from these tokens.
  - Buttons: one class per type of action, always `btn` + action class (+ `btn-sm` in toolbars, filters and table rows; default size for form submits):
    - `btn-save`: submit / save / validate the main action (one per form). `Form->button()` must get `['class' => 'btn btn-save']`, without a class BootstrapUI renders a grey `btn-secondary`.
    - `btn-add`: create a record or positive state change (New, Add, Join, Mark delivered/paid, Received). "New ..." page links: `btn btn-add btn-sm float-end`.
    - `btn-edit`: open a record for modification.
    - `btn-delete`: delete / remove / negative state change, always a `Form->postLink()` with `confirm`.
    - `btn-nav`: navigation without data change (Back, Cancel, List, My page).
    - `btn-export`: PDF, download, print, CSV, email pack.
    - `btn-bulk`: operations on many records or hard to undo (Send all emails, Reset, Generate fees), with a confirm/validation step.
    - `btn-filter` (+ `pressed` on the current one): filters and toggles (teams, member status, periods).
    - `btn-touch` for the main action of member-facing mobile pages; wrap button groups in `.btn-bar`.
    - Never use the Milligram `.button`/`.button-outline` classes nor raw `btn-primary`/`btn-secondary`/`btn-success`/`btn-danger`/`btn-warning`/`btn-info`/`btn-outline-*` (the whole site has been migrated). JS that changes a button's state toggles a class (`pressed`, `btn-bulk`/`btn-delete`), never `style`.
    - Domain-specific button systems (e.g. `mt-btn` in `meetings.css`) are allowed, but their colors come from the `--aa-*` tokens.
  - Table row actions: gg-* icon links (`gg-pen`, `gg-add-r`, `gg-file-document`) in `<td class="actions"><div class="icon">`; other icons via `$this->my->icon()` / `$this->my->symbol()`.
  - Status badges: `badge bg-{success|danger|warning|info|dark}`, color computed in the entity (e.g. `Bill::$statusHtml`): success = done/paid, danger = late/problem, warning = draft/waiting, info = in progress, dark = canceled.
  - Tables: `table table-striped table-hover table-sm` inside `.table-responsive`. Messages: `$this->Flash->success|error|warning()` after actions, `alert alert-*` inside a page. Headings: h2/h3 page title, h4 sections.
- No inline styles (`style="..."` or style options in helpers): use classes in `webroot/css/cake.css` (or the domain CSS files next to it). Toggle classes from JS rather than setting styles; prefer vanilla JS over new jQuery code.
- All pages must be mobile friendly: test at phone width, no horizontal page scroll (wide tables go in `.table-responsive` or collapse into stacked cards), touch-sized buttons, Bootstrap responsive grid/utilities instead of fixed widths.
- In member forms/layouts, checkboxes and their labels must stay on the same line (fix with classes, not inline styles).
- Escape output with `h()`; use Cake helpers (Form/Html/Number) and keep Bootstrap-compatible markup and the layout patterns of `templates/layout/default.php`.
- For billing/member changes, check side effects in emails, PDFs, reminders and role checks.
- Line endings LF.
