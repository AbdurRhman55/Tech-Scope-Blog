<?php
/**
 * TechScope — Deep review: pages, links, forms, CRUD smoke tests.
 * Run: php tools/deep_review.php   (exit 1 = failures found)
 */

declare(strict_types=1);

const BASE  = 'http://localhost/Blog-website';
const ADMIN = BASE . '/admin';

$pass = 0; $fail = 0; $failures = [];

function check(bool $cond, string $label, string $detail = ''): void
{
    global $pass, $fail, $failures;
    if ($cond) {
        $pass++;
        echo "  [PASS] $label\n";
    } else {
        $fail++;
        $failures[] = $label . ($detail !== '' ? " — $detail" : '');
        echo "  [FAIL] $label" . ($detail !== '' ? "  << $detail" : '') . "\n";
    }
}

function note(string $msg): void { echo "\n== $msg ==\n"; }

/* ---------------- HTTP client (shared cookie jar) ---------------- */
$cookieFile = sys_get_temp_dir() . '/ts_review_cookies.txt';
@unlink($cookieFile);

function http(string $url, ?array $post = null): array
{
    global $cookieFile;
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_COOKIEJAR      => $cookieFile,
        CURLOPT_COOKIEFILE     => $cookieFile,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS      => 6,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => ['Expect:'],
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err  = curl_error($ch);
    curl_close($ch);
    return [$code, is_string($body) ? $body : '', $err];
}

$pdo = new PDO('mysql:host=127.0.0.1;dbname=pro_blog;charset=utf8mb4', 'root', '', [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

function csrfFrom(string $html): string
{
    if (preg_match('/name="csrf_token"\s+value="([^"]+)"/', $html, $m)) return $m[1];
    if (preg_match("/name='csrf_token'\s+value='([^']+)'/", $html, $m)) return $m[1];
    return '';
}

function warningText(string $html): string
{
    if (preg_match_all('/<b>(Warning|Fatal error|Parse error|Deprecated|Notice)<\/b>\s*([^<]*)/', $html, $m)) {
        $out = [];
        foreach ($m[1] as $i => $type) $out[] = $type . ': ' . trim($m[2][$i]);
        return implode(' | ', $out);
    }
    return '';
}

/** Parse a rendered <form> into name => value (browser-equivalent submission). */
function form_fields(string $formHtml): array
{
    $fields = [];
    // text-like + hidden inputs
    if (preg_match_all('/<input\b[^>]*>/i', $formHtml, $im)) {
        foreach ($im[0] as $tag) {
            if (!preg_match('/name="([^"]+)"/', $tag, $n)) continue;
            $type = preg_match('/type="([^"]+)"/', $tag, $t) ? strtolower($t[1]) : 'text';
            if (in_array($type, ['submit', 'button', 'file'], true)) continue;
            if ($type === 'checkbox' || $type === 'radio') {
                if (stripos($tag, 'checked') === false) continue; // unchecked → not sent
            }
            $val = '';
            if (preg_match('/value="([^"]*)"/', $tag, $v)) $val = html_entity_decode($v[1], ENT_QUOTES);
            if ($type === 'checkbox' && $val === '') $val = '1';
            $fields[$n[1]] = $val;
        }
    }
    if (preg_match_all('/<textarea\b[^>]*name="([^"]+)"[^>]*>(.*?)<\/textarea>/is', $formHtml, $tm)) {
        foreach ($tm[1] as $i => $name) $fields[$name] = html_entity_decode(strip_tags($tm[2][$i]), ENT_QUOTES);
    }
    if (preg_match_all('/<select\b[^>]*name="([^"]+)"[^>]*>(.*?)<\/select>/is', $formHtml, $sm)) {
        foreach ($sm[1] as $i => $name) {
            $inner = $sm[2][$i];
            if (preg_match('/<option\b[^>]*value="([^"]*)"[^>]*\bselected/i', $inner, $o)) $fields[$name] = $o[1];
            elseif (preg_match('/<option\b[^>]*\bselected[^>]*value="([^"]*)"/i', $inner, $o)) $fields[$name] = $o[1];
        }
    }
    return $fields;
}

function first_form_for(string $pageHtml, string $actionNeedle): ?string
{
    if (!preg_match_all('/<form\b[^>]*>(.*?)<\/form>/is', $pageHtml, $fm)) return null;
    foreach ($fm[0] as $full) {
        if (stripos($full, $actionNeedle) !== false) return $full;
    }
    return null;
}

/** Non-published posts 404 on the frontend by design — not a crawl failure. */
function expected_404(string $abs): bool
{
    global $pdo;
    if (!preg_match('#/post/([^/?]+)#', $abs, $m)) return false;
    $st = $pdo->prepare('SELECT status FROM posts WHERE slug = ?');
    $st->execute([urldecode($m[1])]);
    $status = $st->fetchColumn();
    return $status !== false && $status !== 'published';
}

/* ================================================================
 *  A. Frontend pages
 * ================================================================ */
note('A. Frontend pages');

$frontPages = ['/', '/blog', '/categories', '/about', '/contact', '/sitemap', '/privacy', '/terms', '/disclaimer', '/search?q=ai'];
$catSlug  = (string) $pdo->query("SELECT slug FROM categories ORDER BY id LIMIT 1")->fetchColumn();
$authSlug = (string) $pdo->query("SELECT slug FROM users ORDER BY id LIMIT 1")->fetchColumn();
$postSlug = (string) $pdo->query("SELECT slug FROM posts WHERE status='published' ORDER BY id LIMIT 1")->fetchColumn();
if ($catSlug  !== '') $frontPages[] = '/category/' . $catSlug;
if ($authSlug !== '') $frontPages[] = '/author/' . $authSlug;
if ($postSlug !== '') $frontPages[] = '/post/' . $postSlug;

$rawLinks = [];
foreach ($frontPages as $p) {
    [$code, $body] = http(BASE . $p);
    check($code === 200, "GET $p → 200", "got $code");
    $w = warningText($body);
    check($w === '', "GET $p → no PHP warnings", $w);
    check(strpos($body, '< ?') === false, "GET $p → no corruption text");
    if (preg_match_all('/(?:href|src)="([^"]*)"/', $body, $m)) {
        foreach ($m[1] as $u) $rawLinks[$u] = true;
    }
}

/* ================================================================
 *  B. Frontend link crawl
 * ================================================================ */
note('B. Frontend link crawl');

$checked = []; $dead = [];
foreach (array_keys($rawLinks) as $u) {
    $u = trim($u);
    if ($u === '' || $u === '#' || str_starts_with($u, 'javascript:')) { $dead[] = "'$u' (dead link)"; continue; }
    if ($u[0] === '#' || str_starts_with($u, 'mailto:') || str_starts_with($u, 'tel:')
        || str_starts_with($u, 'data:') || str_starts_with($u, '//')) continue;
    if (preg_match('#^https?://#i', $u) && !str_starts_with($u, BASE)) continue; // external
    $abs = preg_match('#^https?://#i', $u) ? $u : BASE . ($u[0] === '/' ? $u : '/' . $u);
    $abs = (string) preg_replace('/#.*$/', '', $abs);
    if ($abs === BASE || isset($checked[$abs])) continue;
    $checked[$abs] = true;
    [$code] = http($abs);
    if ($code !== 200 && !expected_404($abs)) $dead[] = "$abs → $code";
}
check($dead === [], 'all crawled frontend links/assets → 200', implode(' | ', array_unique($dead)));
echo '  (checked ' . count($checked) . " unique URLs)\n";

/* ================================================================
 *  C. Admin login + pages + forms
 * ================================================================ */
note('C. Admin login & pages');

[, $loginHtml] = http(ADMIN . '/login.php');
$token = csrfFrom($loginHtml);
[$lcode] = http(ADMIN . '/login.php', [
    'email' => 'admin@example.com', 'password' => 'Admin@123', 'csrf_token' => $token,
]);
check($lcode === 200, 'admin login → session established', "code $lcode");

$adminPages = ['index.php', 'posts.php', 'post-edit.php', 'categories.php', 'tags.php', 'users.php',
               'comments.php', 'media.php', 'messages.php', 'seo.php', 'settings.php', 'profile.php'];
$adminLinks = [];
foreach ($adminPages as $pg) {
    [$code, $body] = http(ADMIN . '/' . $pg);
    check($code === 200, "GET admin/$pg → 200", "got $code");
    $w = warningText($body);
    check($w === '', "GET admin/$pg → no PHP warnings", $w);
    check(strpos($body, '< ?') === false, "GET admin/$pg → no corruption text");

    // every POST form must carry a CSRF token
    $missing = 0; $total = 0;
    if (preg_match_all('/<form\b[^>]*method="post"[^>]*>.*?<\/form>/is', $body, $fm)) {
        foreach ($fm[0] as $f) { $total++; if (strpos($f, 'csrf_token') === false) $missing++; }
    }
    check($missing === 0, "admin/$pg → all $total POST forms have CSRF", "$missing missing");

    if (preg_match_all('/(?:href|src)="([^"]*)"/', $body, $m)) {
        foreach ($m[1] as $u) $adminLinks[$u] = true;
    }
}

$deadA = [];
foreach (array_keys($adminLinks) as $u) {
    if ($u === '' || $u === '#' || str_starts_with($u, 'javascript:') || $u[0] === '#'
        || str_starts_with($u, 'mailto:') || str_starts_with($u, 'data:')) {
        if ($u === '' || $u === '#' || str_starts_with($u, 'javascript:')) $deadA[] = "'$u'";
        continue;
    }
    if (preg_match('#^https?://#i', $u) && !str_starts_with($u, BASE)) continue;
    if (preg_match('#^https?://#i', $u)) $abs = $u;
    elseif ($u[0] === '?') continue;                       // query on current page
    elseif ($u[0] === '/') $abs = BASE . $u;
    elseif (preg_match('/^[a-z0-9_-]+\.php/i', $u)) $abs = ADMIN . '/' . $u;
    else continue;
    $abs = (string) preg_replace('/#.*$/', '', $abs);
    if (isset($checked[$abs])) continue;
    $checked[$abs] = true;
    [$code] = http($abs);
    if ($code !== 200 && !expected_404($abs)) $deadA[] = "$abs → $code";
}
check($deadA === [], 'all admin links/assets → 200', implode(' | ', array_unique($deadA)));

/* ================================================================
 *  D. AJAX search endpoint
 * ================================================================ */
note('D. AJAX search endpoint');
[$code, $body] = http(BASE . '/search?ajax=1&q=ai');
$json = json_decode($body, true);
check($code === 200 && is_array($json) && array_key_exists('results', $json),
    '/search?ajax=1 → JSON with results', substr(str_replace("\n", ' ', $body), 0, 140));

/* ================================================================
 *  E. CRUD smoke tests
 * ================================================================ */
note('E. CRUD smoke tests');

/* --- E1. Category: create → update → delete --- */
[, $catPage] = http(ADMIN . '/categories.php');
$token = csrfFrom($catPage);
http(ADMIN . '/categories.php', [
    'csrf_token' => $token, 'action' => 'save', 'name' => 'ZZ Review Category',
    'parent_id' => '0', 'description' => 'deep review test', 'sort_order' => '999',
    'meta_title' => '', 'meta_desc' => '',
]);
$catId = (int) $pdo->query("SELECT id FROM categories WHERE name='ZZ Review Category'")->fetchColumn();
check($catId > 0, 'category CREATE', "id=$catId");

if ($catId > 0) {
    http(ADMIN . '/categories.php', [
        'csrf_token' => $token, 'action' => 'save', 'id' => (string) $catId,
        'name' => 'ZZ Review Category Renamed', 'parent_id' => '0',
        'description' => 'renamed', 'sort_order' => '999', 'meta_title' => '', 'meta_desc' => '',
    ]);
    $nm = (string) $pdo->query("SELECT name FROM categories WHERE id=$catId")->fetchColumn();
    check($nm === 'ZZ Review Category Renamed', 'category UPDATE', "name=$nm");

    http(ADMIN . '/categories.php', ['csrf_token' => $token, 'action' => 'delete', 'id' => (string) $catId]);
    check((int) $pdo->query("SELECT COUNT(*) FROM categories WHERE id=$catId")->fetchColumn() === 0, 'category DELETE');
}

/* --- E2. Tag: create → delete --- */
[, $tagPage] = http(ADMIN . '/tags.php');
$token = csrfFrom($tagPage);
http(ADMIN . '/tags.php', ['csrf_token' => $token, 'action' => 'create', 'name' => 'zz-review-tag']);
$tagId = (int) $pdo->query("SELECT id FROM tags WHERE name='zz-review-tag'")->fetchColumn();
check($tagId > 0, 'tag CREATE', "id=$tagId");
if ($tagId > 0) {
    http(ADMIN . '/tags.php', ['csrf_token' => $token, 'action' => 'delete', 'id' => (string) $tagId]);
    check((int) $pdo->query("SELECT COUNT(*) FROM tags WHERE id=$tagId")->fetchColumn() === 0, 'tag DELETE');
}

/* --- E3. Post: create (publish) → update (draft) → delete --- */
[, $pePage] = http(ADMIN . '/post-edit.php');
$token = csrfFrom($pePage);
$catIdAny = (string) ($pdo->query("SELECT id FROM categories ORDER BY id LIMIT 1")->fetchColumn() ?: '0');
$postFields = [
    'csrf_token' => $token, 'title' => 'ZZ Review Post', 'slug' => 'zz-review-post',
    'excerpt' => 'Deep review excerpt', 'content' => '<p>Deep review test content.</p>',
    'meta_title' => 'ZZ Review Post meta', 'meta_desc' => 'Meta description for the deep review smoke test post.',
    'category_id' => $catIdAny, 'tags' => 'zz-review', 'cover_image' => '', 'cover_alt' => '',
    'allow_comments' => '1', 'published_at' => '',
];
http(ADMIN . '/post-edit.php', $postFields + ['action' => 'publish', 'status' => 'published']);
$pid = (int) $pdo->query("SELECT id FROM posts WHERE slug='zz-review-post'")->fetchColumn();
check($pid > 0, 'post CREATE (publish)', "id=$pid");

if ($pid > 0) {
    http(ADMIN . "/post-edit.php?id=$pid", array_merge($postFields, [
        'action' => 'draft', 'status' => 'draft', 'title' => 'ZZ Review Post Edited',
    ]));
    $row = $pdo->query("SELECT title, status FROM posts WHERE id=$pid")->fetch();
    check(($row['title'] ?? '') === 'ZZ Review Post Edited' && ($row['status'] ?? '') === 'draft',
        'post UPDATE (title + draft)', json_encode($row));

    [, $postsPage] = http(ADMIN . '/posts.php');
    $token = csrfFrom($postsPage);
    http(ADMIN . '/posts.php', ['csrf_token' => $token, 'ids' => [$pid], 'action' => 'delete']);
    check((int) $pdo->query("SELECT COUNT(*) FROM posts WHERE id=$pid")->fetchColumn() === 0, 'post DELETE (bulk)');
}

/* --- E4. Comment: frontend create → row approve → bulk trash → row delete --- */
if ($postSlug !== '') {
    [, $postHtml] = http(BASE . '/post/' . $postSlug);
    $token = csrfFrom($postHtml);
    $cpid = preg_match('/name="post_id" value="(\d+)"/', $postHtml, $m) ? $m[1] : '';
    http(BASE . '/post/' . $postSlug, [
        'csrf_token' => $token, 'action' => 'add_comment', 'post_id' => $cpid,
        'name' => 'ZZ Reviewer', 'email' => 'zzreview@example.com',
        'content' => 'Deep review test comment.', 'website' => '',
    ]);
    $cid = (int) $pdo->query("SELECT id FROM comments WHERE email='zzreview@example.com' ORDER BY id DESC LIMIT 1")->fetchColumn();
    check($cid > 0, 'comment CREATE (frontend form)', "id=$cid");

    if ($cid > 0) {
        [, $cPage] = http(ADMIN . '/comments.php?status=pending');
        $token = csrfFrom($cPage);
        // row-level approve button (approve_id — button-value pattern)
        http(ADMIN . '/comments.php?status=pending', ['csrf_token' => $token, 'approve_id' => (string) $cid]);
        $st = (string) $pdo->query("SELECT status FROM comments WHERE id=$cid")->fetchColumn();
        check($st === 'approved', 'comment row APPROVE button', "status=$st");

        // bulk trash via ids[]
        http(ADMIN . '/comments.php?status=approved', ['csrf_token' => $token, 'ids' => [$cid], 'action' => 'trash']);
        $st = (string) $pdo->query("SELECT status FROM comments WHERE id=$cid")->fetchColumn();
        check($st === 'trash', 'comment bulk TRASH button', "status=$st");

        // row-level delete button (delete_id)
        http(ADMIN . '/comments.php?status=trash', ['csrf_token' => $token, 'delete_id' => (string) $cid]);
        check((int) $pdo->query("SELECT COUNT(*) FROM comments WHERE id=$cid")->fetchColumn() === 0, 'comment row DELETE button');
    }
} else {
    check(false, 'comment flow', 'no published post found to comment on');
}

/* --- E5. Message: frontend create → row mark-read → row delete --- */
[, $contactHtml] = http(BASE . '/contact');
$token = csrfFrom($contactHtml);
http(BASE . '/contact', [
    'csrf_token' => $token, 'action' => 'add_message',
    'name' => 'ZZ Reviewer', 'email' => 'zzreview@example.com',
    'subject' => 'Deep review test subject', 'body' => 'Deep review test body.', 'website' => '',
]);
$mid = (int) $pdo->query("SELECT id FROM messages WHERE subject='Deep review test subject' ORDER BY id DESC LIMIT 1")->fetchColumn();
check($mid > 0, 'message CREATE (contact form)', "id=$mid");

if ($mid > 0) {
    [, $mPage] = http(ADMIN . '/messages.php');
    $token = csrfFrom($mPage);
    http(ADMIN . '/messages.php', ['csrf_token' => $token, 'read_id' => (string) $mid]);
    check((int) $pdo->query("SELECT is_read FROM messages WHERE id=$mid")->fetchColumn() === 1, 'message row MARK-READ button');

    http(ADMIN . '/messages.php', ['csrf_token' => $token, 'delete_id' => (string) $mid]);
    check((int) $pdo->query("SELECT COUNT(*) FROM messages WHERE id=$mid")->fetchColumn() === 0, 'message row DELETE button');
}

/* --- E6. User: create → delete --- */
[, $uPage] = http(ADMIN . '/users.php');
$token = csrfFrom($uPage);
http(ADMIN . '/users.php', [
    'csrf_token' => $token, 'action' => 'save', 'name' => 'ZZ Review User',
    'email' => 'zzreview@example.com', 'role' => 'author', 'status' => '1',
    'password' => 'Review@12345', 'bio' => 'deep review', 'website' => '',
]);
$uid = (int) $pdo->query("SELECT id FROM users WHERE email='zzreview@example.com'")->fetchColumn();
check($uid > 0, 'user CREATE', "id=$uid");
if ($uid > 0) {
    [, $uPage2] = http(ADMIN . '/users.php');
    $token = csrfFrom($uPage2);
    http(ADMIN . '/users.php', ['csrf_token' => $token, 'action' => 'delete', 'id' => (string) $uid]);
    check((int) $pdo->query("SELECT COUNT(*) FROM users WHERE id=$uid")->fetchColumn() === 0, 'user DELETE');
}

/* --- E7. Settings save: idempotent (data must not change) + restore safety --- */
$snap = $pdo->query('SELECT setting_key, setting_value FROM settings ORDER BY setting_key')
            ->fetchAll(PDO::FETCH_KEY_PAIR);
[, $sPage] = http(ADMIN . '/settings.php');
$token = csrfFrom($sPage);
$sForm = first_form_for($sPage, 'settings.php');
if ($sForm !== null) {
    $fields = form_fields($sForm);
    $fields['csrf_token'] = $token;
    http(ADMIN . '/settings.php', $fields);
    $now = $pdo->query('SELECT setting_key, setting_value FROM settings ORDER BY setting_key')
               ->fetchAll(PDO::FETCH_KEY_PAIR);
    if ($now == $snap) {
        check(true, 'settings SAVE (idempotent — no data loss/change)');
    } else {
        // restore original settings to avoid damage, then report
        foreach ($snap as $k => $v) {
            $pdo->prepare('UPDATE settings SET setting_value=? WHERE setting_key=?')->execute([$v, $k]);
        }
        $changed = [];
        foreach ($now as $k => $v) if (($snap[$k] ?? null) !== $v) $changed[] = $k;
        foreach ($snap as $k => $v) if (!array_key_exists($k, $now)) $changed[] = "$k (missing)";
        check(false, 'settings SAVE altered data (restored)', implode(', ', $changed));
    }
} else {
    check(false, 'settings form found on page');
}

/* --- E8. Profile info save: idempotent --- */
$uro = $pdo->query('SELECT name, email, bio, website FROM users WHERE id=1')->fetch();
[, $prPage] = http(ADMIN . '/profile.php');
$token = csrfFrom($prPage);
$pForm = first_form_for($prPage, 'profile.php');
if ($pForm !== null) {
    $fields = form_fields($pForm);
    if (isset($fields['password'])) unset($fields['password']); // never touch password in this test
    $fields['csrf_token'] = $token;
    http(ADMIN . '/profile.php', $fields);
    $now = $pdo->query('SELECT name, email, bio, website FROM users WHERE id=1')->fetch();
    check($now == $uro, 'profile SAVE (idempotent)', json_encode(['before' => $uro, 'after' => $now]));
} else {
    check(false, 'profile form found on page');
}

/* --- E9. SEO save: idempotent --- */
[, $seoPage] = http(ADMIN . '/seo.php');
$token = csrfFrom($seoPage);
$seoForm = first_form_for($seoPage, 'seo.php');
if ($seoForm !== null) {
    $fields = form_fields($seoForm);
    $fields['csrf_token'] = $token;
    http(ADMIN . '/seo.php', $fields);
    $now = $pdo->query('SELECT setting_key, setting_value FROM settings ORDER BY setting_key')
               ->fetchAll(PDO::FETCH_KEY_PAIR);
    if ($now == $snap) {
        check(true, 'seo SAVE (idempotent — no data loss/change)');
    } else {
        foreach ($snap as $k => $v) {
            $pdo->prepare('UPDATE settings SET setting_value=? WHERE setting_key=?')->execute([$v, $k]);
        }
        check(false, 'seo SAVE altered settings (restored)');
    }
} else {
    check(false, 'seo form found on page');
}

/* ================================================================
 *  Report
 * ================================================================ */
echo "\n========================================\n";
echo "PASS: $pass    FAIL: $fail\n";
if ($failures) {
    echo "FAILURES:\n";
    foreach ($failures as $f) echo "  - $f\n";
}
exit($fail > 0 ? 1 : 0);
