# Foxhole Productivity Platform

Foxhole is Neofox's ADHD-friendly operations cockpit for a full-stack marketing agency. It is written in vanilla PHP (7.4+) with MySQL storage and runs comfortably on shared Apache hosting.

## Feature highlights
- **Role-based launchpads** with tailored dashboards for admins, project managers, and employees.
- **Client and retainer intelligence** showing relationship health, spend, and monthly coverage.
- **Pipeline kanban** to track every engagement from pitch through retainer and flag risk-heavy work.
- **Resource capacity heatmap** with planned vs. actual utilisation, meeting load, and focus theme reminders.
- **Milestones, meeting notes, and idea bank** to keep creative delivery, rituals, and experimentation in one view.
- **Employee focus planner** with one-click timers, inline status changes, and daily wellbeing check-ins.
- **Energy analytics and reports** for weekly/monthly windows including utilisation, pipeline balance, and mood trends.
- **Revenue intelligence** with invoice tracking, expense logging, and live project margin calculations.
- **Freelancer hub & automation alerts** to staff contractors quickly and surface overdue deadlines, retainers, invoices, and wellbeing dips.
- **One-click CSV exports** to bring time, project, and financial summaries into executive packets or spreadsheets instantly.

## Getting started
1. Create a MySQL database (default `foxhole_productivity`).
2. Update credentials inside `productivity/includes/config.php` or set `FOXHOLE_DB_*` environment variables.
3. Run the schema to provision tables and seed demo data:
   ```sql
   SOURCE productivity/schema.sql;
   ```
4. Deploy the `productivity` directory to your web root and browse to `/productivity/index.php`.
5. Sign in with a demo account (`admin@neofox.io`, `pm@neofox.io`, `employee@neofox.io`, password `${FOXHOLE_DEMO_PASSWORD:-foxhole2024}`).

> ℹ️  Set the `FOXHOLE_DEMO_PASSWORD` environment variable before deployment to customise the shared demo password.
> Existing seeded accounts with the legacy hash are automatically upgraded the first time someone signs in with the updated password.

## Modules at a glance
- `dashboard.php`: Adaptive UI for each role with forms to launch projects, log milestones, document meetings, submit check-ins, and capture ideas.
- `reports.php`: Export-friendly analytics including retainer coverage, pipeline totals, project velocity, financial performance, and energy journal plus automation alerts.
- `api/time.php`: Lightweight JSON endpoint for the focus timer, supporting optional completion notes.
- `includes/functions.php`: Shared helper library for authentication, formatting, metrics queries, and persistence helpers.
- `assets/css/style.css`: Minimal, high-contrast styling optimised for executive clarity and ADHD-friendly scanning.
- `assets/js/timer.js`: Client script for the timer toast/prompt flow.

## Database overview
The schema adds supporting tables for clients, capacity planning, wellbeing pulses, meeting notes, idea bank, freelancer resourcing, invoicing, expenses, and richer project metadata. Seed data gives you:
- 3 demo users with titles, focus colours, and sample capacity plans.
- Live demo clients, projects, milestones, time entries, wellbeing check-ins, ideas, meeting notes, freelancer assignments, invoices, expenses, and automation alerts.

Re-running `schema.sql` will drop/overwrite existing data; take backups before applying to live environments.

## Operational tips
- Time entries capture quick wrap-up notes so reports can surface narrative context.
- Weekly capacity uses Monday as the anchor; adjust `week_start` when logging different weeks.
- The wellbeing feed is the source for blockers surfaced on admin and manager dashboards—encourage daily check-ins.
- Reports pull realtime data; export via your browser's "Print to PDF" for weekly executive packets.

Happy foxing! 🦊
