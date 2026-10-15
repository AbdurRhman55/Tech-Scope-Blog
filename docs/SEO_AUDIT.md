# TechScope — Technical SEO Audit

**Date:** 2026-10-12 · **Auditor:** automated code + database inspection
**Stack:** PHP 8.3 · MySQL 8.4 · Tailwind CSS v4 (static build) · no npm
**Scope:** crawlability, canonicals, metadata, structured data, content quality, performance, accessibility, security.

Status legend: ✅ Implemented & tested · 🟡 Implemented, needs production verification · ⛔ Recommended, not implemented · 🔶 Requires human review

---

## Critical

| # | Issue | Evidence | Fix | Status |
|---|-------|----------|-----|--------|
| C1 | **Search results were indexable.** `seo_head()` compared `REQUEST_URI === '/search'`, but real search URLs are `/search?q=…`, so `index, follow` was emitted. | `includes/functions.php` | Path-based detection + `seo_noindex()` flag. | ✅ |
| C2 | **Broken `og:image`.** Fallback `assets/uploads/default-og.png` did not exist, and `post_image()` can return a `data:` SVG URI which social crawlers cannot fetch. | file check: not found | Generated `assets/img/default-og.png` (1200×630); `seo_head()` normalises data-URIs to a real absolute URL. | ✅ |
| C3 | **Invalid ISO 8601 dates in Article JSON-LD** (`2026-05-01 10:00:00` — space separator, no timezone) and `article:published_time`. | `post.php:138` | New `iso8601()` helper → `date('c')` output. | ✅ |
| C4 | **Zero internal links inside article bodies** (0 of 24 posts contain `/post/` links). No contextual crawl paths between cluster articles. | DB query `content LIKE '%/post/%'` → 0 | Render-time contextual auto-linker with a curated phrase→article map (max 3/article, skips code blocks and existing links; stored content untouched). | ✅ |

## High

| # | Issue | Evidence | Fix | Status |
|---|-------|----------|-----|--------|
| H1 | **Canonical strips every query parameter** — `/blog?page=2` canonicalised to `/blog` (paginated pages should self-canonical), and tracking parameters were not normalised. | `seo_head()` | `canonical_url()` keeps meaningful params (`page≥2`, `q`), drops tracking params, normalises trailing slash/duplicate slashes. | ✅ |
| H2 | **Dead `?cat=` parameter on `/blog`** produced duplicate-content URLs (identical rendering, only chip styling changed). | `blog.php:32` | Parameter removed. | ✅ |
| H3 | **Empty category / tag / author archives returned 200 + index** — thin, near-duplicate URLs. | `category.php`, `tag.php`, `author.php` | `seo_noindex()` when result count is 0; out-of-range `?page=` now returns real 404 instead of silently clamping to page 1. | ✅ |
| H4 | **Related articles used `RAND()`** — non-topical and non-deterministic (differs per crawler visit). | `post.php:88` | `get_related_posts()`: shared-tag score → same category → recency. | ✅ |
| H5 | **No post carries `meta_title`/`meta_desc`** (25/25 empty) and `assets/img/favicon.svg` was missing (favicon 404). | DB audit, file check | Fallback generation retained and verified; favicon generated; admin SEO workflow extended (see M1). | ✅ (per-post meta: 🔶 editor fill-in) |
| H6 | **XML sitemap lacked `lastmod` for categories**, and indexable tag pages were absent while low-value empty tags were indexable. | `sitemap-xml.php` | Categories/tags get accurate `lastmod` (max post `updated_at`); only tags with ≥1 published post are listed; empty archives noindexed. | ✅ |

## Medium

| # | Issue | Evidence | Fix | Status |
|---|-------|----------|-----|--------|
| M1 | **Stale `reading_time`** (stored 2 min for ~115-word articles). | DB audit | Reading time now computed from actual content at render time (cards, article, JSON-LD `timeRequired`) and recomputed on admin save. | ✅ |
| M2 | **No table of contents** and no stable heading IDs on article pages. | `post.php` | `prepare_article_html()` assigns unique slug IDs to H2/H3 and builds a TOC; rendered only when the article has ≥3 anchors **and** ≥350 words (short articles stay clean, per spec). | ✅ |
| M3 | **Cover image lacked `width`/`height`/`fetchpriority`** → CLS/LCP risk on the article page. | `post.php:207` | Attributes added + `cover_alt` DB field for accurate alt text. | ✅ |
| M4 | **Thin content:** avg ≈115 words/post (worst: 31 words). Headings exist (24/24 have H2) but depth is insufficient for the target queries. | DB audit | Per-article content audit + prioritised roadmap: `docs/EDITORIAL_ROADMAP.md`; reviewable draft provided for the worst article; admin editorial checklist added. Bulk rewrite deliberately **not** performed. | ⛔ rewrite 🔶 human review |
| M5 | **Search uses `LIKE '%…%'` over LONGTEXT** although a FULLTEXT index exists. | `get_posts()` | Kept deliberately: at 25 posts the scan is <1 ms and `LIKE` matches short queries ("php") that `innodb_ft_min_token_size=3` would miss. Revisit with `MATCH…AGAINST` when the corpus exceeds ~500 posts. | ⛔ documented decision |
| M6 | **Ticker re-ran `get_posts()` on every request.** | `header.php` | Query result statically cached per request. | ✅ |
| M7 | **No `X-Robots-Tag` on admin responses** (meta `noindex` present only). | `admin/includes/layout-top.php` | Header added in `.htaccess` for `/admin/`. | 🟡 Apache only (verify on host) |

## Low

| # | Issue | Fix | Status |
|---|-------|-----|--------|
| L1 | Organization `logo` pointed at the (missing) favicon. | Favicon created; logo still points to `/assets/img/favicon.svg` — replace with a full logo asset when one exists. | 🟡 |
| L2 | `<meta name="keywords">` is ignored by Google. | Retained (harmless, used by some secondary engines); can be removed later. | ⛔ |
| L3 | No analytics configured. | `ga_measurement_id` setting exists and stays empty — **no ID fabricated**. GA4 snippet renders only when an ID is saved. | ⛔ needs client ID 🔶 |
| L4 | HTML pages send no `Cache-Control`. | Deliberate: session/flash-dependent output; static assets already cached 6–12 months via `.htaccess`. | ⛔ documented |

## Verified working (no change needed)

- Pretty URLs via `router.php` (dev) + `.htaccess` (cPanel); clean `/post/{slug}` scheme.
- Real **404 status codes**: missing post/category/tag/author (all call `http_response_code(404)`), unknown routes via router/`.htaccess`.
- `robots.txt` dynamic, `Disallow: /admin|config|tools|includes|search`, correct `Sitemap:` line.
- JSON-LD: WebSite + SearchAction, Organization (sitewide); Article + BreadcrumbList (posts); CollectionPage (category/tag); ProfilePage/Person (author); Blog (blog listing). Syntax validated by test suite.
- PDO prepared statements everywhere; `password_hash()`; CSRF (419 on failure); honeypots; login rate-limit; `sanitize_html()` allowlist on content save; output escaping verified against stored XSS.
- `.htaccess` blocks `config|tools|includes` and `.sql|.md|.log` from web access; security headers (`X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`).
- Static asset caching + gzip; fonts preconnected with `display=swap`; JS bundle 4 KB, CSS 55 KB.
- Skip link, `:focus-visible` outlines, `prefers-reduced-motion`, semantic landmarks (`<main>`, `<nav aria-label>`), labelled forms.

---

## Production verification steps (cannot be run locally)

1. `DEBUG=false`, `SITE_URL=https://yourdomain.com` in `config/config.php`.
2. PageSpeed Insights / Lighthouse on `/`, one article, one category (desktop + mobile) — record LCP/INP/CLS.
3. Rich Results Test + Schema Validator on 3 article URLs.
4. Search Console → URL Inspection → live test of one article (confirm rendered canonical, robots meta, JSON-LD).
5. Submit `https://yourdomain.com/sitemap.xml`, monitor Coverage + Performance weekly.
