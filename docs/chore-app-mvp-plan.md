# Chore App MVP Plan (Ordered Tasks)

This plan is a build sequence for the MVP defined in `docs/chore-app-prd.md`.

## Phase 0: Project Setup

### Task 1: Confirm stack decisions
- Lock MVP stack:
  - Blade templates for all UI
  - Bootstrap 5 via CDN
  - Vanilla JavaScript only
  - Blade layouts/partials/components to reduce duplication
  - MySQL 8 for deployment
  - Queue worker + scheduler/cron enabled
- Output: stack choices documented in `docs/` and reflected in project config.

### Task 2: Install auth and authorization dependencies
- Install/configure Laravel Socialite (Google provider).
- Install/configure Spatie `laravel-permission`.
- Output: dependencies installed, service provider/config published, env keys documented.

### Task 3: Environment and secrets configuration
- Add required Google OAuth env vars.
- Add app URL/session config for OAuth callbacks.
- Add local-dev-only env var `LOCAL_LOGIN_USER_ID` for auth override.
- Output: local login can complete round trip with test account.

### Task 4: Implement local login override middleware
- If `LOCAL_LOGIN_USER_ID` is set in local/dev, auto-authenticate that user.
- Skip override outside approved environments.
- Ensure auth state consistency for frontend session/cookies and any app local storage auth state.
- Output: developer can switch active user quickly without OAuth.

## Phase 1: Core Data Model and Access Control

### Task 5: Create database schema migrations
- Create tables for:
  - households
  - users (household relation + Google identity fields)
  - roles/permissions (Spatie tables)
  - chore_templates
  - chore_instances
  - weekly_claims
  - chore_completions
  - chore_completion_participants
  - points_ledger (append-only awarded points history)
- Add indexes for due dates, status, assignee, approvals.
- Output: migrations run cleanly on fresh DB.

### Task 6: Build Eloquent models and relationships
- Implement model relations for all entities.
- Add casts/enums for statuses and recurrence types.
- Output: relationship tests pass for core read/write paths.

### Task 7: Seed roles and baseline household/users
- Seed roles: `parent`, `kid`, `supervisor`.
- Ensure parent users have both `parent` + `supervisor`.
- Output: seeder creates a usable local test setup.

### Task 8: Implement authorization policies/gates
- Restrict assignee changes to `supervisor`.
- Restrict approval to `supervisor` and block self-approval.
- Restrict claim to open chores only.
- Output: policy tests cover allowed/denied scenarios.

## Phase 2: Authentication and Household Onboarding

### Task 9: Implement Google login flow
- Add OAuth routes/controllers for Google.
- Create/link users by Google account and household.
- Output: login/logout works for parent and kid test accounts.

### Task 10: Single-household onboarding behavior
- Enforce single-household scope for MVP.
- Define first-user bootstrap as parent/supervisor.
- Output: no multi-household paths exposed in UI.

### Task 11: Add `make:user` Artisan command
- Command signature accepts optional `name`, `email`, `password`.
- Prompt interactively for missing values.
- Default password to `password` when omitted.
- Assign created user to single household and support role selection.
- Output: local/dev can create parent/kid/supervisor users from CLI quickly.

## Phase 3: Chore Management Features

### Task 12: Recurring chore template CRUD
- Parent CRUD for template fields:
  - title/description/points
  - recurrence type/rule
  - default assignee/open
  - active/inactive
- Output: parents can create/edit/archive templates.

### Task 13: Ad-hoc chore CRUD
- Parent creates ad-hoc with optional deadline and assignee/open.
- Kid can create extra ad-hoc chores with custom points.
- Default kid-created assignment is self; kid may set open or assign anyone in household.
- Output: both parent and kid ad-hoc creation flows pass validation and policy checks.

### Task 14: Assignment and claim actions
- Implement claim action for open chores only.
- Implement supervisor-only direct reassignment action.
- Output: API/controller tests for claim and assign rules.

### Task 15: Weekly claim feature (current week only)
- Implement weekly claim through Sunday for recurring chores.
- Allow kids to set weekly claim for self or another kid.
- Allow only supervisors to override existing weekly claims.
- Output: weekly claim behavior verified with date-bound tests.

## Phase 4: Recurrence and Due Logic

### Task 16: Recurrence engine/service
- Implement due generation/evaluation for:
  - daily
  - weekly
  - weekday rules
  - every N days
- Output: deterministic service class with unit tests per rule type.

### Task 17: Missed-chore behavior rules
- Daily/weekly/weekday: missed occurrences are skipped.
- Every N days: once due, stays due until completion, then resets to completion + N days.
- Output: edge-case tests for missed periods and reset behavior.

### Task 18: Future chores window and list ordering
- Ensure default kid list order:
  - my assigned
  - open
  - future recurring
- Limit future recurring section to next 7 days.
- Output: query/service tests verify ordering and segmentation.

## Phase 5: Completion, Approval, and Points

### Task 19: Completion submission workflow
- Create completion with primary doer + helpers.
- Lock helpers from editing after submission.
- Require new submission after rejection.
- Output: completion state transitions validated.

### Task 20: Points calculation engine
- Keep all points as integers.
- Apply ad-hoc overdue penalty (50% before split).
- Apply split rules:
  - even split when divisible
  - remainder to original assignee
  - if participants > points, only original assignee gets points
- Output: unit tests for all point scenarios.

### Task 21: Participant guardrail UX + validation
- Warn user before adding participants beyond point count.
- Enforce server-side validation to prevent invalid submissions.
- Output: UI warning present and backend protected.

### Task 22: Supervisor approval/rejection flow
- Supervisor can approve/reject except own submissions.
- Reject requires reason.
- Approve supports optional comment visible to kids.
- Approve allows supervisor-adjusted points.
- Output: approval queue and actions fully policy-tested.

### Task 23: Points ledger and leaderboard
- Write awarded points entries only on approval.
- Track per-user totals from ledger.
- Build household leaderboard and completion history view.
- Output: totals reconcile exactly with approvals.

## Phase 6: Mobile-First UI

### Task 24: Build shared Blade layout/components
- Build base Blade layout (header/nav/footer/flash messages).
- Add reusable partials/components for chore cards, status badges, action buttons, and forms.
- Integrate Bootstrap 5 via CDN in base layout.
- Output: common UI is centralized with minimal duplicated markup.

### Task 25: Build kid list screens
- Mobile-first screens for default list, all chores, and chore details.
- Include claim actions, completion submission, and helper selection.
- Output: primary kid flows usable on phone viewport.

### Task 26: Build parent management screens
- Template/ad-hoc management, assignee controls, history.
- Output: parent can manage chores without admin tooling.

### Task 27: Build supervisor queue screens
- Pending approvals list + approve/reject dialog.
- Include adjusted points + comment/reason fields.
- Output: supervisor can process approvals quickly on mobile.

## Phase 7: Quality, Hardening, and Launch

### Task 28: Automated test coverage
- Add feature tests for:
  - auth/roles
  - claim/assignment rules
  - recurrence/miss behavior
  - approval flow
  - points calculations
- Output: reliable test suite for MVP-critical logic.

### Task 29: Background jobs and schedule setup
- Add scheduled job(s) for recurrence prep/backfill as needed.
- Verify scheduler and queue worker configuration in local and deploy env.
- Output: due chores remain current without manual intervention.

### Task 30: Auditability and observability
- Ensure audit fields are always populated (created/approved/by whom).
- Add structured logs for approval and points award events.
- Output: supportable audit trail for family disputes.

### Task 31: MVP acceptance pass
- Run through all PRD success criteria end-to-end.
- Resolve blocking bugs only; defer non-blocking polish.
- Output: release-ready MVP checklist marked complete.

## Suggested Milestones
- Milestone A (Tasks 1-11): stack/config, login, roles, baseline data model, and developer productivity tooling.
- Milestone B (Tasks 12-18): chore creation, claiming, and recurrence behavior.
- Milestone C (Tasks 19-23): completion, approval, and points correctness.
- Milestone D (Tasks 24-31): Blade UI, tests, and release readiness.

## Definition of Done (MVP)
- Kids can sign in, see ordered chore lists, claim open chores, submit completions with helpers.
- Supervisors can approve/reject (not own), require reject reason, add comments, and adjust points.
- Points and leaderboard are correct for all edge rules in the PRD.
- Recurrence behavior matches skipped vs persistent rules and 7-day future view.
