# Copilot Instructions

## Project Overview
- This repository is AngelAdmin2, a CakePHP 5.2 application for sports-club administration.
- Main functional domains:
	- Member lifecycle (profiles, registration, signatures, team assignment).
	- Meetings and attendance tracking.
	- Billing and reminders, with Swiss QR bill support.
	- Shop items and member orders.
	- Multi-site, role-based administration.
- PHP baseline: 8.1+.
- Line endings: LF.

## Tech Stack and Dependencies
- Framework: CakePHP 5.2.x.
- AuthN/AuthZ: cakephp/authentication and cakephp/authorization.
- UI: friendsofcake/bootstrap-ui + Bootstrap + jQuery.
- Data and infra:
	- Migrations via cakephp/migrations.
	- Localization with gettext PO/POT files.
- Notable integrations:
	- PDF: tecnickcom/tcpdf.
	- Image processing/HEIC helpers: intervention/image and custom components/commands.
	- Swiss QR billing: sprain/swiss-qr-bill.

## High-Level Architecture
- Request lifecycle starts in webroot/index.php and Application middleware stack in src/Application.php.
- Global controller behavior is centralized in src/Controller/AppController.php.
	- Shared components loaded there (Authentication, Authorization, Mail, Get).
	- Locale is selected from user/member/config and set globally.
	- Hash-based member access is supported in addition to standard login.
- Views use BootstrapUI (src/View/AppView.php, templates/layout/default.php).
- Domain logic follows Cake conventions in src/Model/Table, src/Model/Entity, and controller actions.

## Important Repository Structure
- src/Controller: application controllers (Members, Bills, Meetings, Teams, Admin, etc.).
- src/Model/Table and src/Model/Entity: ORM schema and business rules.
- src/Controller/Component: reusable controller-level logic.
- src/Middleware: custom middleware (security and maintenance).
- src/View/Helper: custom helpers (MyHelper, PdfHelper, etc.).
- templates: server-rendered Cake templates by domain.
- templates/element/Members: reusable member-focused fragments.
- templates/layout/default.php: shared shell/navigation.
- webroot/css and webroot/js: frontend assets.
- config/Migrations and config/schema: schema management.
- resources/locales: translation catalogs per language.
- tests/TestCase and tests/Fixture: PHPUnit tests.

## Routing, Security, and Middleware Notes
- Routes are declared in config/routes.php.
- Middleware sequence (see src/Application.php) includes:
	- Error handler.
	- Custom SecurityMiddleware.
	- Asset and routing middleware.
	- Body parser.
	- MaintenanceMiddleware.
	- Authentication and Authorization middleware.
- App also has header/caching policies in webroot/.htaccess.

## Data Model and Business Areas
- Core entities include members, teams, sites, users, roles, bills, bill templates, meetings, presences, contents, configurations, shop items, and member orders.
- Database setup is migration-first (config/Migrations/20250921070000_CreateInitialTables.php is a key baseline migration).
- Preserve existing naming and relationships when adding features.

## Localization and Text Rules
- User-facing text must be in English in source code and wrapped with translation helpers.
- Use __() for standard translatable strings.
- Use contextual helpers like __x() where wording is ambiguous.
- Keep placeholders style consistent (for example {0}, {1}).
- Translation files live in resources/locales/<lang>/default.po.
- Extraction workflow exists (_extractLng.bat runs Cake i18n extract).
- If French translation is requested, use tutoiement.

## UI and Template Conventions
- Use Cake helpers (Form, Html, Number, etc.) instead of raw HTML when practical.
- Keep layout/navigation patterns aligned with templates/layout/default.php.
- Reuse shared elements under templates/element before creating duplicate markup.
- Preserve Bootstrap-compatible structure and classes.
- Keep responsive behavior intact on mobile and desktop.

## CSS and Frontend Good Practices (Important)
- Do not hardcode visual styling in HTML attributes.
	- Avoid style="..." on elements.
	- Avoid passing inline style strings in helper options unless absolutely unavoidable.
- Prefer semantic CSS classes and place styling in stylesheet files (mainly webroot/css/cake.css, or domain-specific CSS when added).
- Prefer class-based state toggling from JS rather than direct style mutations.
- Keep JavaScript unobtrusive and scoped to the page or component.
- Do not introduce new jQuery-heavy patterns when simple vanilla JS is clearer.

## Checkbox and Form Alignment Rule
- In members-related layouts and forms, checkbox inputs and their labels must remain on the same line.
- Prefer consistent Bootstrap/Cake form-check patterns and existing CSS conventions.
- When fixing existing forms, solve alignment with classes/CSS, not with inline styles.

## Coding Style and Safety Rules
- Follow existing CakePHP conventions and local code style of touched files.
- Keep changes minimal and focused; avoid unrelated refactors.
- Escape output by default (h()) unless HTML output is explicitly intended and safe.
- Preserve role/permission checks in controllers/templates.
- Do not bypass Authentication/Authorization flows.
- Keep compatibility with existing hash-based member access behavior.

## Database and Migration Rules
- Prefer new migrations for schema changes; do not edit old applied migrations except when explicitly asked.
- Keep foreign keys/indexes consistent with current patterns.
- Ensure nullable/default values are explicit in migrations.

## Testing and Validation
- Before finishing significant backend changes, run relevant checks:
	- composer test
	- composer cs-check
- For formatting fixes when requested:
	- composer cs-fix
- Add or update tests when behavior changes in controllers, tables, or critical workflows.

## Agent Workflow Recommendations
- First read related controller, template, model table/entity, and helper before coding.
- Trace existing patterns in neighboring features and follow them.
- For UI changes:
	- Update template markup.
	- Add/adjust CSS classes in stylesheet files.
	- Keep translations complete for any new text.
- For billing/member workflows, check for side effects in emails, PDFs, reminders, and role checks.

## Quick Domain Map for Agents
- Authentication/identity: src/Controller/AppController.php, src/Application.php.
- Member features: src/Controller/MembersController.php and templates/Members.
- Billing features: src/Controller/BillsController.php and templates/Bills.
- Meetings/attendance: src/Controller/MeetingsController.php, src/Controller/PresencesController.php.
- Admin/settings: src/Controller/AdminController.php, ConfigurationsController, SitesController.
- Shared UI: templates/layout/default.php, templates/element, webroot/css/cake.css.
- Translations: resources/locales.
