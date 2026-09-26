# OJT Sync

OJT Sync is an on-the-job training (OJT) management system for colleges and their partner companies. It connects three kinds of users:

- **Students** — browse internship postings, apply, track their daily time record (DTR), and submit weekly e-journal entries.
- **Coordinators** — monitor student OJT progress, review time records and journals, and assign companies to students.
- **Company HR** — manage job postings, review and update applications, and generate supervisor evaluations for hired students.

## Tech stack

- [Laravel 13](https://laravel.com) (PHP 8.3+) with SQLite by default
- Blade + Tailwind CSS + Alpine.js (Vite for asset bundling)
- Laravel Breeze for authentication

## Setup

```bash
# Install dependencies and scaffold .env
composer install
cp .env.example .env
php artisan key:generate

# Create the database and run migrations
touch database/database.sqlite
php artisan migrate

# Seed demo data (users, companies, jobs, a sample application)
php artisan db:seed

# Install and build frontend assets
npm install
npm run build
```

Then start the dev servers:

```bash
composer run dev
```

This runs `php artisan serve`, the queue worker, `pail` log tailing, and Vite together. Visit <http://localhost:8000>.

## Demo accounts

After `php artisan db:seed`, all accounts use the password `password`:

| Role        | Email                          |
|-------------|--------------------------------|
| Coordinator | coordinator@cvsu.edu.ph        |
| HR          | hr@technologia.test            |
| Student     | peter.parker@cvsu.edu.ph       |

## Roles & features

### Student
- Browse and search internship postings; apply with a resume and cover letter; withdraw pending applications.
- Check in / check out for daily time records; hours are totaled against the required OJT hours (default 300).
- Submit and delete weekly e-journal entries.

### Coordinator
- Dashboard with OJT progress across all students (hours completed vs. required).
- Review student time records and journals.
- Assign companies to students and update their status.

### Company HR
- Post and manage internship openings for the company.
- Review applications and move them through pending → interview → hired / rejected.
- Generate token-based supervisor evaluations for hired students; share the link with the supervisor to fill in anonymously.

## Evaluations

Evaluations are filled in through a public, token-protected link (no login required). Each token is single-use: the link shows a "thank you" screen once submitted, and a second submission attempt is rejected. The routes are rate-limited to discourage abuse.

## Deployment

Free production deploy (Render web service + Neon Postgres, $0/mo): see [DEPLOYMENT.md](DEPLOYMENT.md). A `render.yaml` Blueprint is included.

## Testing

```bash
composer test
```

Runs the full feature test suite (42 tests covering the student, coordinator, and HR flows plus evaluation token handling).
