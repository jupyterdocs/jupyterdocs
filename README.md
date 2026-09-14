# JupyterDocs

A Scribd-style academic resource marketplace for university students — upload past papers, lecture notes, and study guides, and download whatever you need in return.

## How it works

- **Upload freely.** Anyone, including guests without an account, can upload a document (PDF, Word, PowerPoint, Excel, or text).
- **Earn downloads by contributing.** Once a user has 3 approved uploads, they unlock the ability to download any resource on the platform. Own uploads are always downloadable.
- **Moderation queue.** Every upload is reviewed by an admin before it appears publicly. Admins can preview any document (including pending ones) without needing to download it.
- **Free discovery.** Browsing and searching the catalog is open to everyone — no rigid university/faculty/programme hierarchy to click through, just full-text search and tags.
- **Document reader.** Approved PDFs open in an in-browser, page-by-page viewer (via pdf.js) with related-document suggestions alongside.

## Stack

- Laravel 11 + Blade/Alpine (Breeze scaffolding)
- PostgreSQL
- Tailwind CSS + Vite

## Local setup

```bash
composer install
npm install && npm run build
cp .env.example .env
php artisan key:generate
php artisan storage:link
php artisan migrate --seed
php artisan serve
```

Update the `DB_*` values in `.env` to point at your own PostgreSQL instance before migrating.

A seeded admin account is created on `migrate --seed` — check `database/seeders/DatabaseSeeder.php` for the credentials.
