# TechScope — Professional Blog Platform

A complete, production-ready blog built with **PHP 8+ · MySQL · Tailwind CSS v4 · vanilla HTML/JS** — no npm, no frameworks, no build step on the server.

## Features

**Frontend**
- Home (hero + featured post, category strip, latest grid, newsletter, tags)
- Blog listing, Categories index, Category detail, Tag archive, Author profile
- Single post (reading progress bar, comments, related posts, share/copy link)
- Search (paginated), About, Contact (saved to DB), Privacy / Terms / Disclaimer
- HTML sitemap + XML sitemap (`/sitemap.xml`), SEO meta/OG tags, custom 404
- Mobile-first, fully responsive, editorial typography (Fraunces + Manrope)

**Admin panel** (`/admin`)
- Dashboard with stats, moderation queue, most-read, system info
- Posts: add/edit with rich-text editor, tags, cover image, SEO preview, bulk actions
- Categories, Tags, Authors (roles: admin / editor / author)
- Comments moderation (approve / spam / trash), Contact inbox
- Media library (upload, copy URL, delete), SEO settings, Website settings, Profile

**Security**
- PDO prepared statements everywhere · `password_hash()` + rehash
- CSRF tokens on all forms · XSS escaping on output · HTML allowlist sanitiser on save
- Rate-limited login (5 tries / 15 min) · session regeneration · honeypot spam traps
- `.htaccess` blocks config/tools/sql files + security headers

## Local development (Laragon / XAMPP)

```powershell
# 1. Import the database
C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe -u root < database.sql

# 2. (optional) add demo content
php seed.php

# 3. Start the dev server (clean URLs via router.php)
php -S localhost:8000 router.php
```

- Site: http://localhost:8000
- Admin: http://localhost:8000/admin — `admin@example.com` / `Admin@123`
  ⚠️ Change this password immediately (Admin → My Profile).

## Compile Tailwind (no npm)

A single standalone binary is committed in `tools/` (107 MB, optional — prebuilt CSS is already in the repo):

```powershell
.\tools\tailwindcss.exe -i assets\css\source.css -o assets\css\app.css --minify
```

## Deploy to cPanel

1. Upload **all files** except `tools/` and `router.php` to `public_html/`.
2. Import `database.sql` via phpMyAdmin.
3. Edit `config/config.php`:
   - `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`
   - `SITE_URL` → `https://yourdomain.com` (no trailing slash)
   - Set `DEBUG` to `false`
4. Ensure `assets/uploads/` is writable (`chmod 755`).
5. Done — `.htaccess` handles clean URLs (mod_rewrite must be enabled).

## Project structure

```
├── index.php / blog.php / post.php / category(s).php / tag.php / author.php
├── search.php / about.php / contact.php / privacy.php / terms.php
├── disclaimer.php / sitemap.php / sitemap-xml.php / 404.php
├── router.php              # dev server only (Apache uses .htaccess)
├── .htaccess               # clean URLs + security + caching
├── database.sql            # schema + seed (9 tables)
├── seed.php                # demo posts/tags/comments (idempotent)
├── config/                 # config.php, database.php (PDO singleton)
├── includes/               # functions.php, header, footer, legal template
├── admin/                  # panel: auth, layout, 13 pages
├── assets/                 # css (source → app), js, uploads
└── tools/                  # Tailwind standalone binary (dev only)
```

## Database tables

`users` · `categories` · `tags` · `post_tags` · `posts` · `comments` · `media` · `messages` · `settings`
