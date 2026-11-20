# Coach presence and payment module

Track the presence of coaches and help-coaches at meetings, for statistics, and pay them according to their attendance.

Builds on the coach rework (`docs/implementationCoachRework.md`): coaches of a team are the rows of `teams_coaches` (`type`: `1` = coach, `2` = help-coach).

## Principles

- **Module:** the feature lives in its own tables (`coach_*`), its own table/entity classes, controllers, cells, templates, CSS and JS. No existing table is altered (no new column, no new FK on an existing table). Existing code is only touched at the few hook points listed in [Integration points](#integration-points).
- **Sites are separate:** the cities are nearly two different clubs. Every module record belongs to one site (types, coach accounts/IBAN, presences through their meeting, payments). Nothing is shared or summed across sites, and the admin pages always work on one site at a time.

## Decisions

| # | Topic | Decision |
|---|-------|----------|
| 1 | Who declares | Anyone who can take the attendance of the meeting (`MeetingPolicy::canSetPresence`: coaches of the team, staff with `MemberViewAll`, admins) can set the presence type of **every** coach of that team. Help-coaches have the same rights as coaches |
| 2 | Coaches listed | Current `teams_coaches` of the meeting's team **plus** any coach already declared for that meeting (e.g. removed from the team since), so history stays visible. No substitute from another team |
| 3 | When | Presences can be declared for past **and future** meetings (planning in advance) |
| 4 | Due vs planned | A presence counts in the balance (and can be paid) only once its meeting date is reached (`meeting_date <= now`). Presences of future meetings are shown as "planned", never in the QR code nor in a payment |
| 5 | Presence types | Defined **per site**: label, amount (CHF), order, active flag. The types offered for a meeting are those of the site of the meeting's team |
| 6 | Not declared | No row = "not declared". It counts neither in statistics nor in the balance. An explicit "Absent" type (0.-) is a normal type |
| 7 | Amount of open presences | While unpaid, the amount is read from the type: changing a type's value changes the open balances |
| 8 | Amount of paid presences | Copied on the presence when it is paid (snapshot): paid amounts never change |
| 9 | Balance scope | Per coach **and per site**: each site pays its coaches from its own account (`sites.iban`, `account_designation`, address). A coach of teams in two sites has two separate balances, accounts and payment histories |
| 10 | Payment | "Mark as paid" creates one payment covering **all** due open presences of the coach for that site (decision 4), at the moment of the click. The QR code amount is that total. Balance goes back to 0.- |
| 11 | Lock | A paid presence is read-only for everyone (also on the attendance page). To correct a mistake, the admin deletes the payment: its presences become open again |
| 12 | Admin access | Settings and reporting pages: `Admin` only (`CoachPaymentPolicy::canAdmin`). No new role flag. Always one site: with "all sites" (`0`) selected in the menu, the pages use the first site (`getSiteId(true)`), the menu shows which one |
| 13 | IBAN | Stored **per coach and per site** (a coach in two sites may get paid on two accounts). Set by the admin (settings page) **or by the coach themselves** (My account, decision 15). Write-only for everyone: the form never shows it back, pages show `•••• 1234` (last 4 digits), a new IBAN replaces the old one. Only CH/LI IBANs (Swiss QR bill limitation), validated with `sprain/swiss-qr-bill`. The full IBAN is only in the generated QR code, on an admin-only page |
| 14 | Deleting a type | A type used by at least one presence can't be deleted, only deactivated (hidden from the attendance page, kept in reports) |
| 15 | Coach's own view | On "My account" (`Members/view` of their own member), a coach sees, per site, their due open balance, planned presences and payment history (read-only), and their masked IBAN with a form to set a new one. A warning "No IBAN set" is shown when they have none for that site. Only for themselves: not shown when someone else opens the member page |
| 16 | Statistics | Count per presence type, per team, for the current season (`dateSeasonStart` → today) |

## Database

Migration `CreateCoachPayments` (new tables only):

- `coach_presence_types`: `id`, `site_id` (FK sites, cascade), `label` (string 50), `amount` (decimal 8,2, default 0), `sort` (int), `active` (bool, default true), `created`, `modified`.
- `coach_accounts`: `id`, `member_id` (FK members, cascade), `site_id` (FK sites, cascade), `iban` (string 34), `created`, `modified`. Unique index (`member_id`, `site_id`).
- `coach_presences`: `id`, `meeting_id` (FK meetings, restrict), `member_id` (FK members, restrict), `coach_presence_type_id` (FK coach_presence_types, restrict), `coach_payment_id` (nullable FK coach_payments, set null), `amount` (decimal 8,2, nullable: filled only when paid), `created`, `modified`. Unique index (`meeting_id`, `member_id`).
- `coach_payments`: `id`, `member_id` (FK members, restrict), `site_id` (FK sites, restrict), `amount` (decimal 8,2), `paid_date` (date), `created`, `modified`.

The site of a coach presence is the site of its meeting's team; no `site_id` is duplicated on `coach_presences`. The type of a presence must belong to that same site. To keep it stable, a meeting with coach presences can't move to a team of another site, and a team whose meetings have coach presences can't change site (listener rules).

A coach presence is **open** when `coach_payment_id IS NULL`, **due** when its meeting date is reached (decision 4).

Deleting a meeting or a member deletes its open coach presences (done by the module listener: the foreign keys restrict, so a bulk or SQL delete can never cascade over paid presences). Deleting a meeting that has **paid** coach presences, a member with coach payments, or a site with coach presences or payments is refused, otherwise paid history is lost: done by the module listener (see [Integration points](#integration-points)), not in the existing tables.

Fixtures: `CoachPresenceTypesFixture`, `CoachAccountsFixture`, `CoachPresencesFixture`, `CoachPaymentsFixture`. Existing fixtures are unchanged.

## Model

Associations are declared only on the module tables (`belongsTo` existing tables). No `hasMany` is added to `MeetingsTable`, `MembersTable`, `SitesTable` or `TeamsTable`.

- `CoachPresenceTypesTable`: `belongsTo Sites`, `hasMany CoachPresences`. Finder `activeOfSite(int $siteId)`. Rule: can't delete when used.
- `CoachAccountsTable`: `belongsTo Members, Sites`. `ibanOf(int $memberId, int $siteId): ?string`, `setIban(...)` / `removeIban(...)`. Entity `CoachAccount`: `iban` in `$_hidden`, virtual `MaskedIban` (`•••• 1234`).
- `CoachPresencesTable`: `belongsTo Meetings, Members, CoachPresenceTypes, CoachPayments`.
  - `setType(Meeting $meeting, int $memberId, ?int $typeId)`: create/update/delete (`null` = not declared). Refuses when the row is paid, when the member is not a listed coach of the meeting (decision 2) or the type is not an active type of the meeting's site. The update / delete has the condition `coach_payment_id IS NULL` on the row, so a payment running at the same time wins.
  - `ofMeeting(Meeting $meeting)`: the coach rows for the attendance page (decision 2).
  - `balances(int $siteId, ?int $memberId = null)`: per coach `['due' => total, 'planned' => total, 'count' => [type_id => n]]` of the open presences.
- `CoachPaymentsTable`: `belongsTo Members, Sites`, `hasMany CoachPresences`.
  - `payOpen(int $memberId, int $siteId)`: in a transaction, copy the type amount into each due open presence, create the payment with the sum, link the presences. Refuses when the total is 0. Each presence is linked with an `UPDATE ... WHERE coach_payment_id IS NULL AND coach_presence_type_id = <read type>`: the row lock makes a concurrent payment or type change wait, and if one row is missed the whole payment is rolled back (never paid twice).
  - `beforeDelete`: unlink its presences and clear their `amount` (they become open again).

## Presence declaration (`meetings/view`)

A "Coaches" block just above the presence filters (`.mt-filters`), rendered by a `CoachPresencesCell`, shown only when the team has coaches or declared coach presences.

- One row per coach, same layout as the member rows (`mt-member`): name, coach/help-coach label, then one button per active presence type of the site (same look as the member state buttons), plus a way to clear (not declared).
- Paid rows show the selected type (not a button) with a "Paid" badge.
- Tapping the selected type again clears it (not declared).
- The block uses its own classes (`cp-*` in `webroot/css/coach-payments.css`), not `mt-member`, so `meetings.js` counters and filters ignore the coaches.
- Saving uses AJAX like the members, in its own `webroot/js/coach-presences.js`: controller action `CoachPresencesController::setType($meetingId)` (`set` is reserved by `Controller::set()`), POST `member_id`, `type_id` (empty = clear), authorised with `canSetPresence` on the meeting, JSON answer `{saved, error}` (422 with the message when refused).
- The "Mark the rest as present" bulk button does not touch coaches. Meeting counters (`present`, `absent`...) don't count coaches.
- Hash-access members never see this block (it needs a logged user).

## Coach's own balance and IBAN (My account)

`CoachBalanceCell` rendered on `Members/view`, only when the viewed member is the current user's member (`curUser->member_id`) and they are a coach (a `teams_coaches` row), have coach presences or a coach account. One card per site (sites of their coached teams, plus sites where they have presences or an account):

- Due open balance, planned presences, last payments (read-only).
- **IBAN:**
  - When set: `•••• 1234` (last 4 digits) and a `btn-edit` "Change IBAN". The full IBAN is never sent to the browser.
  - When not set: `alert alert-warning` "No IBAN set: you can't be paid until you set one." and a `btn-add` "Set IBAN".
  - The form (collapsed under the button) has one empty IBAN field, never pre-filled, and a `btn-save`. A new IBAN replaces the old one. Validation errors (not CH/LI, invalid checksum) shown as a flash message, back to My account.
  - The coach can't remove their IBAN (only replace it); the admin can remove it from the settings page.

Action `CoachAccountsController::setIban($siteId)` (POST, logged users only, no hash access): always saves for `curUser->member_id`, never for a member id from the request. Policy `CoachAccountPolicy::canSetOwnIban`: the user's member is a coach of a team of that site, or already has presences or an account in that site. Redirects to `Members/view` of their member.

## Administration (Admin only)

New controller `CoachPaymentsController`. Site taken from `PrefResult::getSiteId()` (decision 12).

### Settings (`coach-payments/settings`)

Two sections, for the selected site:

1. **Presence types**: table (label, amount, active, order) with add / edit / deactivate / delete (delete only if unused).
2. **Coaches**: every member having a `teams_coaches` row in a team of the site, with their teams, masked IBAN for this site and an edit button. The IBAN form has an empty field (never pre-filled) and a "Remove IBAN" action. Validation: CH/LI IBAN; also show a warning when the coach has no address/postcode/city (needed for the QR code).

### Reporting (`coach-payments/index`)

One long page for the selected site, one section (h4) per coach who has presences in this site (coaches without any presence are listed at the end, collapsed):

- Statistics (decision 16).
- Open presences: table (date, meeting, team, type, amount), due total and planned total.
- When the due total > 0: the Swiss QR code (creditor = coach name/address/IBAN of this site, debtor = site `account_designation`/address, amount = due total, CHF, reference type NON, additional info "Coach {season} - {coach name}") and a `btn-add` "Mark as paid" (`postLink` with confirm, shows the amount). The QR is generated in memory (data URI), not stored on disk.
- "Mark as paid" posts the amount shown: the payment is refused if the due balance changed since the page was opened (e.g. a coach declared a presence meanwhile).
- When the coach has no IBAN for this site or no address: no QR code, an `alert alert-warning` with a link to the settings.
- Payment history: date, amount, number of presences, delete (`icon-btn-delete`, confirm: "the presences become open again").

## Integration points

The only changes to existing code:

| File | Change |
|------|--------|
| `src/Application.php` | `bootstrap()`: `CoachPaymentListener::register()` (attaches once to the global event manager) |
| `templates/Meetings/view.php` | One line: `$this->cell('CoachPresences', [$meeting])` above `.mt-toolbar` |
| `templates/Members/view.php` | One line: `$this->cell('CoachBalance', [$member])` before the attendance |
| `templates/layout/default.php` | Admin menu link "Coach payments" (admins only) |
| `src/View/Helper/PdfHelper.php` | `CreateQR()` uses the shared `src/Service/QrBillBuilder.php` (extracted from it), so bills and coach payments build the QR code the same way |

`MeetingsController::delete`, `SitesController::delete` and `MemberManagementComponent::deleteMember` show the rule message of the listener when the delete is refused.

## Files of the module

| Layer | Files |
|-------|-------|
| Migration | `config/Migrations/20261118000000_CreateCoachPayments.php` |
| Model | `src/Model/Table/Coach{PresenceTypes,Accounts,Presences,Payments}Table.php`, entities `Coach{PresenceType,Account,Presence,Payment}` |
| Hooks | `src/Event/CoachPaymentListener.php` (`Model.beforeRules`: delete and site change guards; `Model.beforeDelete`: deletes the open presences of a meeting or member) |
| Service | `src/Service/QrBillBuilder.php` (QR bill, IBAN validation) |
| Controllers | `CoachPresencesController` (attendance page), `CoachAccountsController` (own IBAN), `CoachPaymentsController` (admin), `EntityFlashTrait` (flash with the first validation error) |
| Policies | `CoachPaymentPolicy` (admin), `CoachAccountPolicy` (own IBAN) |
| Views | cells `CoachPresencesCell`, `CoachBalanceCell` (`templates/cell/...`), `templates/CoachPayments/{index,settings,edit_type}.php`, `webroot/css/coach-payments.css`, `webroot/js/coach-presences.js` |
| Tests | `tests/TestSuite/CoachPaymentDataTrait.php`, fixtures `Coach*Fixture`, tests of the tables, service, listener, policies and controllers |

## Tests

- `CoachPresencesTable`: set/clear type, refused for a paid row, a non-coach member, an inactive type or a type of another site; future presence allowed but not due; open balance follows a type amount change; paid amount does not.
- `CoachPaymentsTable`: `payOpen` total only includes due presences and the given site, refused at 0, delete reopens presences, refused (rolled back) when a presence is paid or changed meanwhile.
- `CoachAccountsTable`: one IBAN per site, CH/LI validation, IBAN never serialised.
- `CoachAccountsController::setIban`: the coach sets/replaces their own IBAN for a site they coach in; refused for a site they don't coach in, for hash-access users and anonymous; a `member_id` in the POST data is ignored (can't change someone else's IBAN); the full IBAN never appears in the My account HTML; "No IBAN set" warning shown when missing.
- Listener: meeting delete refused with paid coach presences, allowed (open ones deleted) otherwise; member with payments and site with coach history not deleted; meeting / team site change refused with coach presences.
- `QrBillBuilder`: same output as before for bills (existing bill PDF tests still pass).
- Controllers: access (admin only for `CoachPayments`, `CoachPresences::set` follows `canSetPresence` incl. team scope), site isolation (data of another site never shown), IBAN never in the HTML except inside the QR image.
- `meetings/view` renders the coach block, paid rows disabled; `Members/view` shows the balance only to the member themselves.

## Translations

All new strings in `fr`, `de`, `es`, `it` (`default.po`, French with tutoiement), then `resources/build-locales.sh`.
