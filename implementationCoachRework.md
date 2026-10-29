# Coach access rework

We have members, that can only view and edit their informations.
And coaches that should be able to manage their teams.

For now to identify the coach there is:
- The field `members.coach` (only used for display: "Coach" prefix, dashboard chips, add/edit/register checkbox)
- A role "coach" exists (database only, not referenced in code)
- A group "coach" exists (a team named "Coach", database only)

## Purpose

Allows to assign for each team, multiple coach and help-coach.
Narrows access to only their teams and don't show teams they are not coach or help-coach.

## Decisions

| # | Topic | Decision |
|---|-------|----------|
| 1 | Storage | New table `teams_coaches`, separate from `teams_members`: a coach may or may not be a member of the team |
| 2 | Coach vs help-coach | Same access. The type is only a label for outside the website |
| 3 | Data migration | Drop `members.coach`. `teams_coaches` starts empty and is filled by hand. The "Coach" team is not touched |
| 4 | Identification | New role flag `TeamScoped`. Access is computed from `teams_coaches`. A coach with no team assigned sees no team (and no member) |
| 5 | Scope | Members pages, meetings, presences, mailings, bills, teams: only for their teams. No dashboard |
| 6 | Other teams | A coach sees all teams of a visible member and can edit them (add/remove the member to/from teams) |
| 7 | Site menu | Unchanged, always present. Lists are filtered by coached teams, so other sites show nothing |
| 8 | Accounts | Coaches need a `User`. The assignment page only proposes members having a user account |
| 9 | "Coach" prefix | Dropped everywhere (header, home, agreement, membership PDF) |
| 10 | Assignment page | Allowed to `MemberEditAll` |
| 11 | Registration | Validation stays admin only |
| 12 | Mailings | Opened to coaches, for their teams only |
| 13 | Adding members | A coach cannot see members outside their teams, but from their team page can add any **active** member to that team |
| 14 | Member's teams | A coach edits a visible member as today: they can add/remove the member to/from **any** team |
| 15 | Mailings visibility | A coach sees every mailing targeting at least one of their teams, including mailings created by admins or sent to several teams |
| 16 | Bills | Coaches only **view** bills of their teams. Creating/editing bills stays reserved to `BillEditAll` (the coach role does not get it) |
| 17 | New member | Coaches can create a new member (`Members/add`) |
| 18 | Assignment page | `TeamScoped` users can't assign coaches, even with `MemberEditAll` |
| 19 | Mailings actions | A coach creates mailings for their teams only, because the send page shows the recipients' email addresses. They can send (`send`/`sendNext`) and `delete` only mailings whose teams are all coached; other listed mailings (admin or multi-team) are read-only in the list |

## Access model

### Database

Migration `AddTeamsCoaches`:
- Create `teams_coaches`: `id`, `team_id` (FK teams, cascade), `member_id` (FK members, cascade), `type` (int: `1` = coach, `2` = help-coach), `created`, `modified`. Unique index on (`team_id`, `member_id`).
- Add `roles.TeamScoped` boolean, default `false`.
- Remove `members.coach`.

Update `MembersFixture`, `Member` entity, `MembersTable` (validation + selected fields), add `TeamsCoachesTable` + `TeamsCoach` entity, associations `Teams hasMany TeamsCoaches` / `Members hasMany TeamsCoaches`.

### `TeamScoped` semantics

`TeamScoped` is a **modifier** on the existing flags: the coach role keeps the usual flags (`MemberViewAll`, `MemberEditAll`, `BillViewAll`, ...) and `TeamScoped` restricts them to the members/teams/meetings/bills of the teams found in `teams_coaches` for the current member.

- `Admin` always ignores `TeamScoped` (full access).
- A user without `TeamScoped` keeps today's behaviour.
- "Members of my teams" = members linked through `teams_members` to a team in `teams_coaches` for `curUser->member_id`.

### Shared helper (no duplication)

One place computes the scope, reused by controllers, policies and templates:
- `AppController::getCoachedTeamIds(): ?array` → `null` = no restriction, `[]` = nothing, `[ids]` = restricted (cached per request).
- `getTeamsActiv()`, `getMembers()`, `getBills()`, `getLateBillsByMember()`, `getMembersEvo()` apply the restriction, so `PrefResult` and every page using them get it for free.
- Policies: a small trait/helper (e.g. `TeamScopeTrait`) with `isScoped($user)` and `canAccessTeam($user, $teamId)` / `canAccessMember($user, $memberId)`, used by `MemberPolicy`, `TeamPolicy`, `MeetingPolicy`, `PresencePolicy`, `BillPolicy`, `MailingPolicy`.
- `AppController::canAccessMember()` also checks the scope.

## Pages recap by access

Legend: **Scoped** = available to coaches, restricted to their teams. **No coach** = not available to coaches (even if the coach role has the underlying flag). **Unchanged** = same as today.

### Available to coaches (scoped to their teams)

| Controller | Actions | Current check | Change |
|------------|---------|---------------|--------|
| Members | `index`, `list`, `listEmail`, `birthdays`, `pictures`, `familyReduction`, `invite`, `batchRegister` | `MemberViewAll` | Team dropdown and members limited to coached teams |
| Members | `view` | `MemberViewAll` or own | Only members of coached teams (+ own) |
| Members | `edit`, `addPhoto`, `addPhotoId` | `MemberEditAll` or own | Only members of coached teams (+ own). Team selector shows all active teams, the coach can add/remove any of them |
| Members | `add` | `MemberEditAll` | Allowed. A new member is only visible to the coach once in one of their teams |
| Teams | `index`, `view` | `MemberViewAll` | Only coached teams. `view` shows coaches/help-coaches |
| Teams | `addMember`, `removeMember` | `MemberEditAll` | Only on coached teams. `addMember` can search **all active members** (only exception to visibility) |
| Meetings | `index`, `view`, `add`, `addMultiple`, `edit`, `delete` | `MemberViewAll` / `MemberEditAll` | Only meetings of coached teams |
| Presences | `setPresence` | `MemberViewAll` | Only meetings of coached teams |
| Mailings | `index`, `add`, `send`, `sendNext`, `delete` | `Admin` | Opened to coaches. `index` lists mailings targeting at least one coached team (whoever created them). `add` proposes only coached teams (and saving refuses other teams). "New" from an existing mailing keeps only the coached teams. `send`/`sendNext`/`delete` only when every team of the mailing is coached (the send page lists recipient emails); the action links are hidden otherwise |
| Bills | `index`, `simple`, `overview`, `view` | `BillViewAll` / own | Read only, bills of members of coached teams |

### Not available to coaches

| Controller | Actions | Current check |
|------------|---------|---------------|
| Admin | `dashboard`, `newYear`, `resetMembership`, `resetRegistration`, `generateMembership`, `viewLog`, `subventions*` | `Admin` |
| Members | `newMembers` (registration validation, admin only), `signatures` | `MemberViewAll` / `MemberEditAll` |
| Teams | `add`, `edit`, **new** `coaches` | `Admin` / `MemberEditAll` |
| Bills | `add`, `edit`, `pdf`, `pdfs`, `batchAdd`, `generateReminder`, `mailSend`, `mailPrint`, `testQR` | `BillEditAll` (unchanged, coach role does not have it) |
| Bills | `batchValidation`, `sendReminder` | `BillValidate` (unchanged, coach role does not have it) |
| Bills | `clearDraft` | `Admin` |
| MemberOrders, ShopItems | all | `MemberEditAll` → blocked when `TeamScoped` |
| Users, Roles, Sites, Configurations, BillTemplates, FieldTypes | all | `Admin` |
| Contents | `index`, `view`, `add`, `edit`, `delete` | `Editor` (unchanged) |

### Unchanged (everybody / hash access)

| Controller | Actions |
|------------|---------|
| Members | `myPage`, `register`, `checked`, `active`, `agreement`, `agreementPdf`, `cancelRegistration`, `setSite` |
| Meetings | `join` |
| Users | `login`, `logout`, `register`, `forgotPassword`, `reset`, `setLanguage`, `emailLogin`, `view`/`edit` own |
| Pages | `display` (home) |
| Contents | `info` |

## Interface

- Drop the "Coach" prefix in `element/Members/header.php`, `Pages/home.php`, `Members/agreement.php`, `Admin/generate_membership.php`.
- Remove the `coach` checkbox in `Members/add.php`, `Members/edit.php`, `Members/register.php`.
- `Team::_getCoachs()` reads `teams_coaches` (used by the admin dashboard), with coach / help-coach label.
- Nav menu (`layout/default.php`): show Members / Meetings / Teams / Bills / "Emails to teams" entries to coaches according to their flags; hide admin-only entries.
- Teams view: list coaches and help-coaches.
- Members index (`/members`, `templates/Members/index.php`): a single line above the list, e.g. `Coach: Anna Muller, Marc Rossi · Help coach: Luc Favre` (full names), for the selected team. Hidden when "all teams" is selected. Rendered by a reusable element/helper method (shared with the Teams view) and styled with a CSS class. A label is omitted when its list is empty.
- **New page `Teams/coaches`** (`MemberEditAll`, not `TeamScoped`): list of all active teams (filtered by selected site), for each team two multi-selects "Coaches" and "Help-coaches" proposing only members with a user account. Linked from the Teams index and the nav menu.
- Translations: new strings in fr/de/es/it `.po`.

## Tests

- Migration + fixtures (`TeamsCoachesFixture`, `RolesFixture` with a `TeamScoped` role, a coach user).
- Policies: scoped vs not scoped, coach with no team.
- Controllers: coach sees only own teams/members/meetings/bills, gets refused on another team's member, can add an active member from outside to a coached team, cannot reach dashboard, assignment page saves coaches.

## Open points

None.
