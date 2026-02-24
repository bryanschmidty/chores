# Chore App MVP Stack Choices

This document captures the agreed technical stack and architecture constraints for MVP implementation.

## Frontend Stack
- Rendering: Blade templates only.
- Styling: Bootstrap 5 via CDN.
- JavaScript: vanilla JavaScript only.
- Reuse strategy: use Blade layouts/partials/components to minimize duplicate markup.

## Backend Stack
- Framework: Laravel 12.
- Authentication: Google OAuth via Laravel Socialite.
- Authorization: Spatie `laravel-permission` with roles `parent`, `kid`, and `supervisor`.

## Data and Infrastructure
- Primary database (deployment): MySQL 8.
- Queue: `database` driver with basic queue worker enabled.
- Scheduling/Cron: Laravel scheduler enabled for recurring chore processing.

## Local Development Overrides
- Support environment variable `LOCAL_LOGIN_USER_ID`.
- When set in local/dev, app auto-authenticates as that user to speed development/testing.
- Override should not run in production.
- Override should preserve normal frontend auth/session behavior (session/cookies and any auth-related local storage values used by the app).

## Out of Scope for MVP
- Frontend frameworks (React/Vue/Inertia/Livewire/Alpine for MVP by default).
- Tailwind CSS adoption for MVP.
- Multi-household support.
- Notifications (push/email/SMS).
