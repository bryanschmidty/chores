# Chore App PRD (Draft v0.5)

## 1) Product Overview
Build a simple mobile-friendly chore app for families with multiple kids.

The app supports:
- recurring chores on flexible schedules (daily, weekly, every N days, specific weekdays),
- ad-hoc one-off chores that can be assigned to a specific kid or left open to claim,
- kid-created extra ad-hoc chores with kid-selected point values,
- point-based rewards,
- optional helpers per chore with automatic point splitting,
- Google authentication via Laravel Socialite.
- role-based permissions using Spatie `laravel-permission`.

## 2) Goals
- Make it easy for kids to see what chores are due right now from a phone.
- Let parents manage recurring and one-off chores quickly.
- Keep chore completion and points transparent and fair.
- Support common real-world recurrence patterns without complex setup.
- Require supervisor approval before points are awarded.

## 3) Non-Goals (for initial version)
- Payroll or real-money payouts.
- Advanced gamification (badges, levels, streak bonuses).
- Native mobile apps (web app only, responsive UI).
- Multi-family household support (single family/household first).
- Notifications (push/email/SMS).

## 4) Primary Users
- Parents/Guardians: create chores, assign chores, review completion, manage points.
- Kids: view due/open chores, claim chores (if allowed), complete chores, add helpers (subject to rules).
- Supervisors: approve completed chores (cannot approve their own completion).

## 5) Core User Stories
1. As a parent, I can create recurring chores with flexible schedules.
2. As a parent, I can create one-off chores and optionally set a deadline.
3. As a kid, I can see chores due now and chores I can claim.
4. As a kid, I can complete a chore and optionally add helper(s).
5. As a supervisor, I can approve chore completions and award points.
6. As a parent, I can review who did each chore and how points were split.

## 6) Functional Requirements

### 6.1 Authentication & Access
- Users authenticate with Google OAuth (Laravel Socialite).
- Users belong to one household.
- Each kid uses their own Google account.
- Role types (via Spatie `laravel-permission`):
  - `parent`: full chore management permissions.
  - `kid`: task view/claim/complete permissions.
  - `supervisor`: can approve completions and award points.
- Role combinations:
  - Parents have both `parent` and `supervisor`.
  - Some kids may have both `kid` and `supervisor`.
- Approval restriction:
  - Users with `supervisor` role cannot approve their own completed chores.
- Local development login override:
  - Add environment variable `LOCAL_LOGIN_USER_ID` for local/dev use only.
  - When set, app auto-authenticates as that user without Google OAuth flow.
  - App must keep frontend session/auth state consistent (cookies and any auth-related local storage values used by the app).

### 6.2 Chore Types
- **Recurring chore template**:
  - Name, description (optional), base points, recurrence rule, active/inactive status.
  - Optional default assignee (specific kid) or unassigned/open.
- **Ad-hoc chore**:
  - Name, description (optional), base points, optional assignee, optional deadline.
  - One-time completion only.
- **Kid-created extra ad-hoc chore**:
  - Any kid can create an ad-hoc chore to record extra work.
  - Kid sets chore name/description and point value.
  - Default assignee is the creator.
  - Creator can instead leave it open or assign it to any household user (including a parent).
  - Still requires supervisor approval before points are awarded.

### 6.3 Recurrence Rules (MVP)
- Daily.
- Weekly.
- Every N days (e.g., every 2 days, every 3 days).
- Specific weekday(s) (e.g., every Friday, every Saturday).
- System generates/marks chores as due based on recurrence definition.
- For "Every N days," next due date is calculated from the last completion date.
- Miss behavior by recurrence type:
  - Daily/weekly/weekday chores: missed occurrences are skipped (no backlog carryover).
  - Every N days chores: once due, they remain due each day until completed; on completion, the next due date resets to completion date + N days.

### 6.4 Assignment & Claiming
- Chores can be:
  - assigned to a specific kid, or
  - left open for any kid to claim.
- Claim action is available only for open chores.
- Only users with `supervisor` role can change assignee.
- Weekly claim mode (for recurring chores):
  - A kid can claim a recurring chore "for the week" (week ends Sunday).
  - Weekly claim sets that kid as assignee for all remaining occurrences in that week.
  - A kid can also set a weekly claim to another kid (for in-person planning scenarios).

### 6.5 Completion & Helpers
- A chore completion record includes:
  - primary doer,
  - zero or more helpers,
  - completion timestamp.
- Both kids and parents can add helpers.
- Points split evenly among all participants (primary + helpers).
  - Example: 9-point chore with 3 participants => 3 points each.
- Points are always integers.
- Integer split rules:
  - If points divide evenly, all participants get equal points.
  - If uneven, the original assignee receives the remainder.
  - If participant count exceeds total point value, only original assignee receives points and all others receive 0.
- Participant guardrail:
  - UI must warn before adding a participant that would make participant count exceed point value.
- Helpers cannot be edited after submission (new submission required if changes are needed).
- Points are awarded only after supervisor approval.
- At approval time, supervisor can adjust final awarded point value.

### 6.6 Deadlines & Due Status
- Ad-hoc chores may include optional deadline.
- Recurring chores have due instances derived from recurrence rules.
- Overdue ad-hoc chores remain completable.
- If ad-hoc chore is completed after its deadline, awarded points are reduced to 50% before participant split.
- Chore states include:
  - upcoming, due, overdue, pending_approval, approved.

### 6.7 Views (Mobile-First Web)
- Kid view:
  - Default list order: my assigned chores, then open chores, then future recurring chores.
  - Ability to view all chores.
  - Completed chores and points earned.
- Parent view:
  - Chore template management.
  - Ad-hoc chore management.
  - Household completion feed/history.
  - Points leaderboard/report.
- Supervisor queue:
  - Pending approvals list.
  - Approve/reject completion actions (except own submissions).
  - Optional approval comment that is visible to kids.
- Responsive design optimized for phone usage.

### 6.8 Audit & History
- Track who created/edited chores and who completed chores.
- Keep completion history for point transparency.
- Track who approved each completion and when.

### 6.9 Admin/Developer Console Command
- Add `make:user` Artisan command.
- Command supports parameters: `name`, `email`, `password`.
- If parameters are not provided, command prompts interactively.
- If password is omitted (argument or prompt), default password is `password`.
- Command assigns user to the single household and supports selecting role(s).

## 7) Data Model (Draft)
- Household
- User
  - Google identity fields
- Role / Permission (Spatie)
  - role names include `parent`, `kid`, `supervisor`
- ChoreTemplate (for recurring chores)
  - recurrence_type, recurrence_interval, recurrence_weekdays, points, default_assignee_id
  - last_completed_at (for N-day recurrence calculations)
- ChoreInstance (due/generated or ad-hoc record)
  - source_type: recurring | ad_hoc
  - due_at, deadline_at, assigned_to_user_id, status
  - base_points, adjusted_points
- WeeklyClaim
  - chore_template_id, week_start_at, week_end_at, assigned_to_user_id, claimed_by_user_id
- ChoreCompletion
  - chore_instance_id, completed_by_user_id, completed_at
  - approval_status, approved_by_user_id, approved_at
  - supervisor_adjusted_points, approval_comment, rejection_reason
- ChoreCompletionParticipant
  - completion_id, user_id, points_awarded

## 8) MVP Success Criteria
- Parents can create recurring and ad-hoc chores in under 30 seconds each.
- Kids can open phone browser and identify due chores within 10 seconds.
- Point totals reflect approval workflow, deadline penalties, and helper splits accurately.
- Google login works for all family users.
- Kids can complete weekly claiming in a few taps and see assignee changes immediately.

## 9) Constraints / Technical Notes
- Backend: Laravel.
- Auth: Laravel Socialite with Google provider.
- Authorization: Spatie `laravel-permission`.
- Frontend: Blade templates only for MVP.
- UI framework: Bootstrap 5 via CDN for MVP.
- JavaScript: standard vanilla JS only for MVP (no frontend framework).
- UI architecture: use Blade layouts/partials/components to minimize duplicated markup.
- Deployment database: MySQL 8.
- Queue driver: `database`.
- Background processing: basic queue worker and scheduler/cron setup.
- Time zone handling must be explicit per household (TBD exact behavior).

## 10) Decisions Captured in v0.5
1. "Every N days" recurrence is based on last completion date.
2. Overdue ad-hoc chores remain completable at half points.
3. Points are always integers.
4. Both kids and parents can add helpers.
5. Supervisor approval is required before awarding points.
6. Roles use Spatie `laravel-permission`: `parent`, `kid`, `supervisor`.
7. Any supervisor can approve except the user who completed that chore.
8. Kids can view all chores, but default list is my chores, then open chores, then future recurring chores.
9. Each kid logs in with their own Google account.
10. Notifications are out of MVP scope.
11. Uneven point split remainder goes to original assignee.
12. If participants exceed point value, only original assignee gets points.
13. Rejected completions require a new submission (no in-place edit/resubmit).
14. Daily/weekly/weekday missed recurrences are skipped; N-day recurrences persist until completed.
15. Helpers cannot be edited after submission.
16. Only open chores are claimable; only supervisors can directly change assignee.
17. Single household scope for MVP.
18. Kids can create extra ad-hoc chores and set point values (no min/max limit).
19. Weekly recurring-claim assignment runs through Sunday.
20. Weekly claim conflicts can be overridden by supervisors only.
21. Weekly claims are for current week only.
22. Ad-hoc chores assigned to someone else must be completed by that assignee as primary doer.
23. Reject action requires a reason.
24. Future recurring chores display window is next 7 days.
25. Supervisor can adjust awarded points when approving.
26. Supervisor can include an approval comment visible to kids.
27. Frontend stack for MVP is Blade + vanilla JS + Bootstrap 5 via CDN.
28. Use layouts/partials/components to reduce duplicate UI code.
29. Deployment uses MySQL 8.
30. Queue driver is `database` for MVP.
31. Basic queue worker and scheduler/cron capability are required in deployment.
32. Local development supports `LOCAL_LOGIN_USER_ID` auto-login override.
33. Add `make:user` command with args + interactive prompts and default password `password`.

## 11) Proposed MVP Scope Cut (Recommendation)
For fastest first build, include:
- Google login,
- Spatie roles (`parent`, `kid`, `supervisor`),
- recurring + ad-hoc chores,
- kid-created extra ad-hoc chores,
- open claim + assigned chores,
- completion with helpers,
- supervisor approval workflow,
- supervisor-adjusted points and approval comments,
- ad-hoc overdue half-point rule,
- even points split with integer outputs,
- weekly recurring claim assignment through Sunday,
- mobile-friendly list views,
- basic household points leaderboard.

Exclude initially:
- notifications,
- advanced recurrence exceptions,
- rewards redemption system.

## 12) Remaining Open Questions
No open questions currently for MVP behavior.
