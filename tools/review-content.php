<?php
/**
 *  Review gate for docs/content/<slug>.html before they are applied.
 *  Checks: allowed tags only, no <h1>, word counts, banned accuracy phrases,
 *  internal links must be in the published whitelist, external link domains.
 *  Usage: php tools/review-content.php [--verbose]
 */
require dirname(__DIR__) . '/includes/functions.php';

$verbose = in_array('--verbose', $argv, true);
$dir     = dirname(__DIR__) . '/docs/content';
$files   = glob($dir . '/*.html') ?: [];

$whitelist = array_column(
    db()->query("SELECT slug FROM posts WHERE status = 'published'")->fetchAll(),
    'slug'
);
$whitelistSet = array_flip($whitelist);

$allowedTags = ['p','br','h2','h3','h4','strong','b','em','i','u','s','blockquote',
    'ul','ol','li','a','pre','code','hr','img','span','figure','figcaption',
    'table','thead','tbody','tr','th','td'];

$issues = 0;
printf("%-58s %6s %4s %s\n", 'SLUG', 'WORDS', 'H2', 'ISSUES');
foreach ($files as $file) {
    $slug = basename($file, '.html');
    $html = (string) file_get_contents($file);
    $errs = [];
    $warn = [];

    // 1. tag allowlist (same rules as sanitize_html: strip_tags comparison)
    preg_match_all('/<\s*([a-z0-9]+)(\s|>)/i', $html, $m);
    foreach (array_unique($m[1]) as $tag) {
        if (!in_array($tag, $allowedTags, true)) {
            $errs[] = "tag <$tag> not allowed";
        }
    }
    // 2. no h1
    if (preg_match('/<\s*h1/i', $html)) {
        $errs[] = 'has <h1> (template renders title)';
    }
    // 3. accuracy red flags — phrases that suggest invented evidence
    $flagPatterns = [
        '/\bstud(y|ies|ied)\s+(by|shows|found|conducted)\b/i' => 'study claim',
        '/\bresearch(ers)?\s+(found|show|at the university)/i'  => 'research claim',
        '/\baccording to (a|the) (survey|study|report)\b/i'     => 'unattributed source',
        '/\bwe (benchmarked|tested|ran|measured)\b/i'           => 'first-person test claim',
        '/\bincrease[ds]?\s+(revenue|retention|performance)\s+by\s+\d+%/i' => 'stat claim',
        '/\b\d+(\.\d+)?\s?%/i'                                  => null, // counted below
    ];
    foreach ($flagPatterns as $re => $label) {
        if ($label === null) {
            continue;
        }
        if (preg_match_all($re, $html, $mm)) {
            $errs[] = count($mm[0]) . "x $label: '" . mb_substr($mm[0][0], 0, 50) . "'";
        }
    }
    // percent figures — warn only; the spec allows labeled illustrative/hypothetical
    // numbers. Each warning is verified manually in review (see session notes).
    preg_match_all('/\b\d+(\.\d+)?\s?%/', $html, $pm);
    $hasExtSource = (bool) preg_match('/href="https?:\/\/(?!localhost)[^"]+"/', $html);
    if ($pm[0] && !$hasExtSource) {
        $warn[] = count($pm[0]) . ' percent figure(s) — verify each is labeled illustrative';
    }
    // 4. internal links must target published slugs
    preg_match_all('/href="\/post\/([a-z0-9\-]+)"/i', $html, $lm);
    foreach ($lm[1] as $target) {
        if (!isset($whitelistSet[$target])) {
            $errs[] = "internal link to unknown slug: $target";
        }
        if ($target === $slug) {
            $errs[] = 'links to itself';
        }
    }
    // 5. external links — flag unknown hosts for a human look
    preg_match_all('/href="(https?:\/\/[^"]+)"/i', $html, $em);
    foreach ($em[1] as $u) {
        $host = parse_url($u, PHP_URL_HOST) ?: '';
        if (!preg_match('/\b(dev\.mysql\.com|php\.net|developer\.mozilla\.org|docs\.python\.org|git-scm\.com|w3\.org|schema\.org|cloud\.google\.com|docs\.aws\.amazon\.com|learn\.microsoft\.com|fidoalliance\.org|whatwg\.org|postgresql\.org|mdn\.web\.dev)\b$/', $host)) {
            $errs[] = "external host to verify: $host";
        }
    }
    // 6. local images must exist on disk
    preg_match_all('/src="\/(assets\/img\/[^"]+)"/i', $html, $im);
    foreach ($im[1] as $src) {
        if (!file_exists(dirname(__DIR__) . '/' . $src)) {
            $errs[] = "missing image file: $src";
        }
    }
    // 7. bare ampersands (breaks XML-ish rendering)
    if (preg_match('/&(?!amp;|lt;|gt;|quot;|#?\w+;)/', $html)) {
        $errs[] = 'bare & found';
    }

    $words = str_word_count(strip_tags($html));
    $h2s   = preg_match_all('/<h2[\s>]/i', $html);
    if ($words < 1200) {
        $errs[] = "thin: $words words (<1200)";
    }

    printf("%-58s %6d %4d %s\n", $slug, $words, $h2s, $errs ? implode(' | ', $errs) : ($warn ? '[warn] ' . implode(' | ', $warn) : 'ok'));
    $issues += count($errs);
}
echo "\nfiles: " . count($files) . "   issues: $issues\n";
exit($issues ? 1 : 0);
