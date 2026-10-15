# TechScope — Production Deployment & Search Console Guide

## 1. Pre-upload configuration

Edit `config/config.php` on the server (never commit live credentials):

```php
define('SITE_URL', 'https://yourdomain.com');   // exact public URL, no trailing slash
define('DEBUG',    false);                       // hides PHP errors from visitors
```

Also review (admin panel → Settings):
- `site_name`, `site_description` — used in metadata sitewide
- `contact_email` — real inbox before launch
- `ga_measurement_id` — **leave empty until you have a real GA4 ID** (no ID is ever fabricated)
- Admin password (default `Admin@123` must be changed) and admin e-mail

## 2. Upload (cPanel)

1. Upload all project files to `public_html/` (or a subfolder).
2. Create the MySQL database + user in cPanel → MySQL Databases; grant **ALL PRIVILEGES**.
3. Import `database.sql` via phpMyAdmin → then run `migrations/2026-10-12-seo-overhaul.sql`
   *(skip the migration if your `database.sql` already contains `cover_alt` — it is included in the base schema)*.
4. Set DB credentials in `config/config.php` to the cPanel database/user.
5. Ensure `.htaccess` is present (Apache routes pretty URLs; `router.php` is never used on cPanel).
6. File permissions: `assets/uploads/` writable (755 or 775), everything else 644.

## 3. Post-upload verification checklist

| Check | How | Expected |
|---|---|---|
| Homepage | visit `/` | 200, correct title/description |
| Article | open any `/post/…` | 200, breadcrumb, JSON-LD validates |
| Missing post | visit a fake slug | **404** status (not 200) |
| Pagination | `/blog?page=2` | 200 + canonical → itself; `/blog?page=99` → **404** |
| Search | `/search?q=php` | `noindex` robots meta |
| Sitemap | `/sitemap.xml` | well-formed XML, HTTPS URLs |
| Robots | `/robots.txt` | `Sitemap:` line with your HTTPS domain |
| Admin | `/admin` | login works; `<meta name="robots" content="noindex">` present |
| HTTPS | whole site | http → https redirect (host-level or add to `.htaccess`) |
| Debug | trigger a PHP error deliberately? | **No** error details shown with `DEBUG=false` |

Tools: **Rich Results Test** + **Schema Validator** on 3 article URLs (expect valid Article,
BreadcrumbList, WebSite, Organization), **PageSpeed Insights** on `/`, one article, one category.

## 4. Google Search Console — one-time setup

1. Add property (recommended: **Domain** verification — covers http/https + www/apex).
2. Verify via DNS record (or HTML file/meta tag if domain verification isn't possible).
3. Left menu → **Sitemaps** → enter `sitemap.xml` → Submit. Expect "Success" within minutes;
   full processing can take days.
4. **URL Inspection** → paste a key article → *Test live URL* → confirm:
   - discovered canonical == your canonical
   - robots meta = `index`
   - page renders (JS/CSS not blocked — they are served normally)
5. Request indexing for the homepage + 2–3 best articles (a few per day is plenty).

## 5. Monitoring (weekly, first 3 months)

In Search Console → **Performance** (compare periods: last 28 days vs previous):

| Metric | What to watch |
|---|---|
| **Impressions** | Are new query terms appearing? Growing = topical authority building |
| **Clicks / CTR** | Low CTR on high-impression queries → improve title/meta *honestly* |
| **Average position** | Trend per query group, not a single rank promise |
| **Coverage (Pages)** | "Valid" grows; "Excluded: noindex" should list only search/empty archives; **zero** "Error" rows |
| **Sitemap** | Discovered URLs ≈ posts + categories + tags + authors + static pages |

Also: Core Web Vitals (PageSpeed Insights / CrUX report) — track LCP/INP/CLS per template.
Fix regressions first on the article template (highest traffic value).

**Expectations:** no ranking timeline can be guaranteed. Judge progress by impressions,
indexed coverage and click trends over 4–8 weeks — not by daily rank checks.

## 6. Privacy & compliance notes

- Newsletter/contact forms store data in your database only — no third-party transmission.
  If you connect an email service later, add consent wording + a privacy policy update.
- Analytics: only add scripts when a real measurement ID exists; never send personal data.
- Comments store IP addresses (anti-spam) — disclosed in the privacy policy if you enable comments publicly.

## 7. Rollback

Schema changes are additive only (`cover_alt VARCHAR(200) NULL`) — no destructive migration.
To roll back code: re-upload the previous file versions; the DB remains compatible.
