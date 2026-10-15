# =====================================================================
#  TechScope — smoke + SEO regression test suite
#
#  Run:   powershell -ExecutionPolicy Bypass -File tests\smoke-tests.ps1
#  Needs: Laragon Apache serving C:\laragon\www\Blog-website (http://localhost/Blog-website)
#         + MySQL (pro_blog). SITE_URL in config.php must match $base below.
# =====================================================================

$ErrorActionPreference = 'SilentlyContinue'
$base = 'http://localhost/Blog-website'
$script:pass = 0
$script:fail = 0
$script:sessionFail = 0

function Check([string]$label, [bool]$cond) {
    if ($cond) { $script:pass++; "  [PASS] $label" }
    else       { $script:fail++; "  [FAIL] $label" }
}
function Fetch([string]$path) {
    try { return Invoke-WebRequest ($base + $path) -UseBasicParsing -TimeoutSec 20 }
    catch {
        # PS 5.1 throws on 4xx/5xx — surface the real status code instead
        $resp = $_.Exception.Response
        if ($resp -and $resp.StatusCode) {
            return [pscustomobject]@{ StatusCode = [int]$resp.StatusCode; Content = '' }
        }
        return $null
    }
}
function Clean($html) { return -not ($html -match 'Fatal error|Parse error|<b>Warning|Undefined (variable|array key)|Deprecated:') }

"=================== 1. PAGE STATUS + CLEAN RENDER ==================="
$ok200 = @('/', '/blog', '/categories', '/about', '/contact', '/privacy', '/terms',
           '/disclaimer', '/sitemap', '/sitemap.xml', '/robots.txt', '/search',
           '/category/artificial-intelligence', '/category/programming', '/category/cloud-computing',
           '/tag/ai', '/author/abd-ur-rahman',
           '/post/clean-code-php-patterns', '/post/how-large-language-models-actually-work')
foreach ($u in $ok200) {
    $r = Fetch $u
    Check "$u -> 200 + no PHP errors" ($r -and $r.StatusCode -eq 200 -and (Clean $r.Content))
}

"=================== 2. REAL 404s ==================="
$notFound = @('/post/this-slug-does-not-exist', '/category/not-a-category',
              '/tag/not-a-tag', '/author/nobody-here',
              '/blog?page=99', '/category/programming?page=99',
              '/tag/ai?page=99', '/author/abd-ur-rahman?page=99',
              '/search?q=x&page=99', '/definitely-not-a-route')
foreach ($u in $notFound) {
    $r = Fetch $u
    Check "$u -> 404" ($r -and $r.StatusCode -eq 404)
}

"=================== 3. CANONICAL / ROBOTS META ==================="
$r = Fetch '/blog?page=2'
Check '/blog?page=2 self-canonical' ($r.Content -match ('<link rel="canonical" href="' + [regex]::Escape($base) + '/blog\?page=2">'))
$r = Fetch '/search?q=php'
Check '/search?q=php is noindex' ($r.Content -match '<meta name="robots" content="noindex, follow">')
$r = Fetch '/blog'
Check '/blog is index,follow' ($r.Content -match 'content="index, follow, max-image-preview:large"')
Check 'tracking params dropped from canonical' ((Fetch '/blog?utm_source=x').Content -match ('canonical" href="' + [regex]::Escape($base) + '/blog">'))

"=================== 4. STRUCTURED DATA ==================="
foreach ($u in @('/', '/post/clean-code-php-patterns', '/category/programming', '/author/abd-ur-rahman', '/blog')) {
    $c = (Fetch $u).Content
    $blocks = [regex]::Matches($c, '<script type="application/ld\+json">(.*?)</script>', 'Singleline')
    $valid = 0
    foreach ($b in $blocks) { try { $null = $b.Groups[1].Value | ConvertFrom-Json; $valid++ } catch {} }
    Check "$u : all $($blocks.Count) JSON-LD blocks valid" ($blocks.Count -gt 0 -and $valid -eq $blocks.Count)
}
$r = (Fetch '/post/clean-code-php-patterns').Content
Check 'Article datePublished ISO 8601'     ($r -match '"datePublished":"\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}')
Check 'Article dateModified ISO 8601'      ($r -match '"dateModified":"\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}')
Check 'BreadcrumbList present on article'  ($r -match '"@type":"BreadcrumbList"')
Check 'og:image absolute http URL'         ($r -match 'og:image" content="https?://')
Check 'article:published_time ISO'         ($r -match 'article:published_time" content="\d{4}-\d{2}-\d{2}T')

"=================== 5. SITEMAP / ROBOTS ==================="
$r = (Fetch '/sitemap.xml').Content
try { $null = [xml]$r; Check 'sitemap.xml well-formed XML' $true } catch { Check 'sitemap.xml well-formed XML' $false }
Check 'sitemap has lastmod entries'      ($r -match '<lastmod>\d{4}-\d{2}-\d{2}</lastmod>')
Check 'sitemap lists categories'         ($r -match '/category/')
Check 'sitemap lists populated tags'     ($r -match '/tag/')
Check 'sitemap excludes admin & search'  ($r -notmatch '/admin|/search')
$robot = (Fetch '/robots.txt').Content
Check 'robots.txt disallows /admin/'     ($robot -match 'Disallow: /admin/')
Check 'robots.txt references sitemap'    ($robot -match ('Sitemap: ' + [regex]::Escape($base) + '/sitemap.xml'))

"=================== 6. ARTICLE EXPERIENCE ==================="
$r = (Fetch '/post/how-large-language-models-actually-work').Content
Check 'H1 exactly once'                  (([regex]::Matches($r, '<h1[\s>]')).Count -eq 1)
Check 'H2 headings have stable IDs'      ($r -match '<h2[^>]*\sid="[a-z0-9\-]+"')
Check 'reading time computed from text'  ($r -match '\d+ min read<' -and $r -notmatch '"timeRequired":"PT0M"')
Check 'cover image has width+height'     ($r -match '<img src="[^"]+" alt="[^"]*" width="1200" height="630"')
Check 'cover fetchpriority=high'         ($r -match 'fetchpriority="high"')
$r2 = (Fetch '/post/clean-code-php-patterns').Content   # mid-list article: must have both neighbours
Check 'prev/next navigation present'     ($r2 -match 'Previous' -and $r2 -match 'Next')
Check 'related section (relevance, no RAND)' ($r -match 'Keep reading')
Check 'skip-link present'                ($r -match 'class="skip-link"')
Check 'main landmark has id'             ($r -match '<main id="main-content"')
Check 'breadcrumb nav visible'           ($r -match 'aria-label="Breadcrumb"')
# contextual internal link inside the prose body — either writer-authored (editorial)
# or injected by auto_internal_links(); duplicates/self-links are intentionally skipped
$prose = ''
$pi = $r.IndexOf('<div class="prose-ed">')
if ($pi -ge 0) {
    $pj = $r.Length
    foreach ($marker in @('<!-- Tags -->', '<!-- Prev / Next navigation -->', '<!-- References')) {
        $j = $r.IndexOf($marker, $pi)
        if ($j -ge 0 -and $j -lt $pj) { $pj = $j }
    }
    $prose = $r.Substring($pi, $pj - $pi)
}
$ilinks = [regex]::Matches($prose, '<a href="' + [regex]::Escape($base) + '/post/[^"]+"')
Check "contextual internal link in article prose ($($ilinks.Count) found)" ($ilinks.Count -ge 1)
Check 'no links injected inside <code>'  ($r -notmatch ('<code>[^<]*<a href="' + [regex]::Escape($base) + '/post'))

"=================== 7. LISTING PAGES ==================="
$r = (Fetch '/blog').Content
Check 'blog: one H1'                     (([regex]::Matches($r, '<h1[\s>]')).Count -eq 1)
Check 'blog: cards render images'        ($r -match 'assets/img/topics/')
Check 'categories page: photos + overlay'((Fetch '/categories').Content -match 'category_cover_image|assets/img/topics')
Check 'homepage: one H1'                 (([regex]::Matches((Fetch '/').Content, '<h1[\s>]')).Count -eq 1)

"=================== 8. ASSETS ==================="
foreach ($u in @('/assets/img/default-og.png', '/assets/img/favicon.svg',
                 '/assets/css/app.css', '/assets/js/main.js', '/assets/img/topics/programming-1.jpg')) {
    $r = Fetch $u
    Check "$u -> 200" ($r -and $r.StatusCode -eq 200)
}

"=================== 9. ADMIN: AUTH + EDITORIAL SAVE ==================="
$jar = "$env:TEMP\smoke-cookies.txt"
Remove-Item $jar -Force -ErrorAction SilentlyContinue

# 1. login page + CSRF token
$html = (curl.exe -s -c $jar "$base/admin/login.php") -join ''
$csrf = if ($html -match 'name="csrf_token" value="([^"]+)"') { $Matches[1] } else { '' }
Check 'admin login page + CSRF token' ($csrf.Length -gt 10)

# 2. login POST -> 302 -> dashboard
$code = curl.exe -s -o NUL -w '%{http_code}' -b $jar -c $jar `
    -d "csrf_token=$csrf&email=admin%40example.com&password=Admin%40123" `
    "$base/admin/login.php"
Check "login POST -> 302 redirect (got $code)" ($code -eq '302')
$html = curl.exe -s -b $jar "$base/admin/"
Check 'dashboard 200 + clean' (($html -join '') -notmatch 'Fatal error|Parse error|<b>Warning')
Check 'admin noindex meta' (($html -join '') -match 'content="noindex, nofollow"')

# 3. create draft with meta + cover_alt
$html = (curl.exe -s -b $jar "$base/admin/post-edit.php") -join ''
$csrf = if ($html -match 'name="csrf_token" value="([^"]+)"') { $Matches[1] } else { '' }
Check 'post-edit form + CSRF token' ($csrf.Length -gt 10)
$code = curl.exe -s -o NUL -w '%{http_code}' -b $jar `
    --data-urlencode "csrf_token=$csrf" `
    --data-urlencode "title=ZZ Smoke Test Article" `
    --data-urlencode "slug=zz-smoke-test-article" `
    --data-urlencode "excerpt=Testing the SEO save pipeline end to end." `
    --data-urlencode "content=<p>Smoke test content for the SEO suite. Enough words to matter a little bit here.</p>" `
    --data-urlencode "category_id=1" `
    --data-urlencode "status=draft" `
    --data-urlencode "meta_title=ZZ Smoke Title For SERP" `
    --data-urlencode "meta_desc=A unique meta description used only by the smoke test suite." `
    --data-urlencode "cover_alt=A descriptive alt text for testing" `
    --data-urlencode "tags=smoke-test" `
    "$base/admin/post-edit.php"
Check "post save -> 302 (got $code)" ($code -eq '302')
$row = (& "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -u root -N -e "USE pro_blog; SELECT CONCAT(meta_title,'|',cover_alt,'|',reading_time) FROM posts WHERE slug='zz-smoke-test-article';")
Check "DB persisted meta_title + cover_alt + reading_time ($row)" ($row -match 'ZZ Smoke Title' -and $row -match 'descriptive alt' -and $row -match '\|\d+$')

# 4. draft invisible publicly + excluded from sitemap
$r = Fetch '/post/zz-smoke-test-article'
Check 'draft post is 404 publicly' ($r -and $r.StatusCode -eq 404)
Check 'draft excluded from sitemap' ((Fetch '/sitemap.xml').Content -notmatch 'zz-smoke-test-article')

# 5. cleanup — delete the test post
$postId = (& "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -u root -N -e "USE pro_blog; SELECT id FROM posts WHERE slug='zz-smoke-test-article';")
$html = (curl.exe -s -b $jar "$base/admin/posts.php") -join ''
$csrf = if ($html -match 'name="csrf_token" value="([^"]+)"') { $Matches[1] } else { '' }
$code = curl.exe -s -o NUL -w '%{http_code}' -b $jar `
    --data-urlencode "csrf_token=$csrf" `
    --data-urlencode "action=delete" `
    --data-urlencode "ids[]=$postId" `
    "$base/admin/posts.php"
$left = (& "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -u root -N -e "USE pro_blog; SELECT COUNT(*) FROM posts WHERE slug='zz-smoke-test-article';")
Check "test post cleaned up (delete -> $code, remaining: $left)" ($left -eq '0')
Remove-Item $jar -Force -ErrorAction SilentlyContinue

"=================== 10. SECURITY SPOT-CHECKS ==================="
# CSRF rejection while authenticated (fresh session)
$jar2 = "$env:TEMP\smoke-cookies2.txt"
$html = (curl.exe -s -c $jar2 "$base/admin/login.php") -join ''
$csrf = if ($html -match 'name="csrf_token" value="([^"]+)"') { $Matches[1] } else { '' }
curl.exe -s -o NUL -b $jar2 -c $jar2 -d "csrf_token=$csrf&email=admin%40example.com&password=Admin%40123" "$base/admin/login.php" | Out-Null
$code = curl.exe -s -o NUL -w '%{http_code}' -b $jar2 --data-urlencode 'csrf_token=bogus' --data-urlencode 'title=x' "$base/admin/post-edit.php"
Check "invalid CSRF -> 419 (got $code)" ($code -eq '419')
Remove-Item $jar2 -Force -ErrorAction SilentlyContinue

# stored XSS must render escaped
$evil = "ZZ<script>alert(1)</script>"
$enc  = [uri]::EscapeDataString($evil)
# (the seed content has no XSS; verify output escaping on a user-controlled field instead)
$r = (Fetch "/search?q=%3Cscript%3Ealert(1)%3C/script%3E").Content
Check 'search query reflected escaped' ($r -notmatch '<script>alert\(1\)</script>')

# data intact (categories = 10: original 8 + user-created "Cloud Computing" + "App Development")
# comments: minimum check — the owner actively comments, so the count only grows
$c = (& "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe" -u root -N -e "USE pro_blog; SELECT CONCAT((SELECT COUNT(*) FROM posts), '/', (SELECT COUNT(*) FROM posts WHERE status='published'), '/', (SELECT COUNT(*) FROM categories), '/', (SELECT COUNT(*) FROM comments));")
$cParts = $c -split '/'
Check "data intact (posts/published/categories = $($cParts[0..2] -join '/'))" (($cParts[0] -eq '25') -and ($cParts[1] -eq '24') -and ($cParts[2] -eq '10'))
Check "comments preserved ($($cParts[3]) >= 11)" ([int]$cParts[3] -ge 11)

"=================== 11. EDITORIAL BRIEF WORKFLOW ==================="
$my = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$jar3 = "$env:TEMP\smoke-cookies3.txt"
Remove-Item $jar3 -Force -ErrorAction SilentlyContinue
$html = (curl.exe -s -c $jar3 "$base/admin/login.php") -join ''
$csrf = if ($html -match 'name="csrf_token" value="([^"]+)"') { $Matches[1] } else { '' }
curl.exe -s -o NUL -b $jar3 -c $jar3 -d "csrf_token=$csrf&email=admin%40example.com&password=Admin%40123" "$base/admin/login.php" | Out-Null

# create draft WITH a full editorial brief (incl. a fake related slug that must be dropped)
$html = (curl.exe -s -b $jar3 "$base/admin/post-edit.php") -join ''
$csrf = if ($html -match 'name="csrf_token" value="([^\"]+)\"') { $Matches[1] } else { '' }
$code = curl.exe -s -o NUL -w '%{http_code}' -b $jar3 `
    --data-urlencode "csrf_token=$csrf" `
    --data-urlencode "title=ZZ Brief Test Article" `
    --data-urlencode "slug=zz-brief-test-article" `
    --data-urlencode "excerpt=Brief workflow test." `
    --data-urlencode "content=<p>Brief workflow test content body.</p>" `
    --data-urlencode "category_id=1" `
    --data-urlencode "status=draft" `
    --data-urlencode "brief_audience=Backend developers" `
    --data-urlencode "brief_question=When should an editor fill this in?" `
    --data-urlencode "brief_intent=comparison" `
    --data-urlencode "brief_supporting=Q1`nQ2" `
    --data-urlencode "brief_outline=H2 One`nH2 Two" `
    --data-urlencode "brief_references=Official docs - https://example.com/docs`nnot a link" `
    --data-urlencode "brief_related=clean-code-php-patterns, this-slug-does-not-exist" `
    --data-urlencode "brief_status=in_review" `
    --data-urlencode "brief_notes=Check facts before approval." `
    "$base/admin/post-edit.php"
Check "brief save -> 302 (got $code)" ($code -eq '302')

$row = (& $my -u root -N -e "USE pro_blog; SELECT CONCAT(COALESCE(eb.target_audience,'~'),'|',COALESCE(eb.search_intent,'~'),'|',eb.review_status,'|',COALESCE(eb.related_slugs,'~'),'|',IF(eb.references_text LIKE '%example.com/docs%','REF-OK','REF-MISSING')) FROM editorial_briefs eb JOIN posts p ON p.id=eb.post_id WHERE p.slug='zz-brief-test-article';")
Check "brief row persisted (audience|intent|status) = $row" ($row -match 'Backend developers\|comparison\|in_review\|')
Check 'related_slugs keeps valid slug, drops fake one' ($row -match 'clean-code-php-patterns' -and $row -notmatch 'this-slug-does-not-exist')
Check 'references_text stored' ($row -match 'REF-OK')

# review badge appears in the posts list
$list = (curl.exe -s -b $jar3 "$base/admin/posts.php") -join ''
Check 'posts list shows review-status badge' ($list -match 'in review')

# cleanup: deleting the post must cascade the brief row
$postId = (& $my -u root -N -e "USE pro_blog; SELECT id FROM posts WHERE slug='zz-brief-test-article';")
$html = (curl.exe -s -b $jar3 "$base/admin/posts.php") -join ''
$csrf = if ($html -match 'name="csrf_token" value="([^\"]+)\"') { $Matches[1] } else { '' }
curl.exe -s -o NUL -b $jar3 --data-urlencode "csrf_token=$csrf" --data-urlencode "action=delete" --data-urlencode "ids[]=$postId" "$base/admin/posts.php" | Out-Null
$leftP = (& $my -u root -N -e "USE pro_blog; SELECT COUNT(*) FROM posts WHERE slug='zz-brief-test-article';")
$leftB = (& $my -u root -N -e "USE pro_blog; SELECT COUNT(*) FROM editorial_briefs WHERE post_id=$postId;")
Check "test post deleted ($leftP) + brief cascaded ($leftB)" ($leftP -eq '0' -and $leftB -eq '0')
Remove-Item $jar3 -Force -ErrorAction SilentlyContinue

"=================== 12. DYNAMIC NAV + SUBCATEGORIES ==================="
$my = "C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe"
$jar4 = "$env:TEMP\smoke-cookies4.txt"
Remove-Item $jar4 -Force -ErrorAction SilentlyContinue
$html = (curl.exe -s -c $jar4 "$base/admin/login.php") -join ''
$csrf = if ($html -match 'name="csrf_token" value="([^"]+)"') { $Matches[1] } else { '' }
curl.exe -s -o NUL -b $jar4 -c $jar4 -d "csrf_token=$csrf&email=admin%40example.com&password=Admin%40123" "$base/admin/login.php" | Out-Null

# create a subcategory under Technology (id 2)
$html = (curl.exe -s -b $jar4 "$base/admin/categories.php") -join ''
$csrf = if ($html -match 'name="csrf_token" value="([^"]+)"') { $Matches[1] } else { '' }
$code = curl.exe -s -o NUL -w '%{http_code}' -b $jar4 --data-urlencode "csrf_token=$csrf" --data-urlencode "action=save" --data-urlencode "name=ZZ Nav Test Sub" --data-urlencode "parent_id=2" "$base/admin/categories.php"
Check "subcategory created -> 302 (got $code)" ($code -eq '302')
$parentId = (& $my -u root -N -e "USE pro_blog; SELECT id FROM categories WHERE slug='zz-nav-test-sub';")
Check 'sub row stored with parent_id=2' ((& $my -u root -N -e "USE pro_blog; SELECT parent_id FROM categories WHERE id=$parentId;") -eq '2')

# homepage nav must render it as a dropdown child of Technology
$homeHtml = (curl.exe -s "$base/") -join ''
Check 'nav shows subcategory link' ($homeHtml -match '/category/zz-nav-test-sub')
Check 'nav renders dropdown panel markup' ($homeHtml -match 'group-hover:visible')

# cycle guard: try making Technology its own child -> must be rejected
$html = (curl.exe -s -b $jar4 "$base/admin/categories.php?edit=2") -join ''
$csrf = if ($html -match 'name="csrf_token" value="([^"]+)"') { $Matches[1] } else { '' }
curl.exe -s -o NUL -b $jar4 --data-urlencode "csrf_token=$csrf" --data-urlencode "action=save" --data-urlencode "id=2" --data-urlencode "name=Technology" --data-urlencode "parent_id=$parentId" "$base/admin/categories.php" | Out-Null
Check 'cycle rejected (Technology parent stays NULL)' ((& $my -u root -N -e "USE pro_blog; SELECT IF(parent_id IS NULL,'ok','bad') FROM categories WHERE id=2;") -eq 'ok')

# parent archive still renders (with descendant filter in place)
$p = Fetch '/category/technology'
Check 'parent archive 200' ($p.StatusCode -eq 200)

# cleanup: delete subcategory, verify nav link gone + counts restored
$html = (curl.exe -s -b $jar4 "$base/admin/categories.php") -join ''
$csrf = if ($html -match 'name="csrf_token" value="([^"]+)"') { $Matches[1] } else { '' }
curl.exe -s -o NUL -b $jar4 --data-urlencode "csrf_token=$csrf" --data-urlencode "action=delete" --data-urlencode "id=$parentId" "$base/admin/categories.php" | Out-Null
$homeHtml = (curl.exe -s "$base/") -join ''
Check 'sub link gone after delete' ($homeHtml -notmatch '/category/zz-nav-test-sub')
$c2 = (& $my -u root -N -e "USE pro_blog; SELECT COUNT(*) FROM categories;")
Check "categories restored ($c2 = 10)" ($c2 -eq '10')
Remove-Item $jar4 -Force -ErrorAction SilentlyContinue

"`n=================== RESULT ==================="
"PASS: $script:pass   FAIL: $script:fail"
if ($script:fail -eq 0) { 'ALL TESTS PASSED'; exit 0 } else { 'THERE ARE FAILURES'; exit 1 }
