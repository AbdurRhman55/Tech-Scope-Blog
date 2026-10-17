<?php
/**
 *  Admin — Add / Edit post
 */

require_once __DIR__ . '/auth.php';

$admin_user = require_role(['admin', 'editor', 'author']);
$pdo        = db();

$id   = (int) ($_GET['id'] ?? 0);
$post = null;

if ($id) {
    $stmt = $pdo->prepare('SELECT * FROM posts WHERE id = ?');
    $stmt->execute([$id]);
    $post = $stmt->fetch();
    if (!$post) {
        flash_set('error', 'Post not found.');
        header('Location: ' . admin_url('posts.php'));
        exit;
    }
    // Authors can only edit their own posts
    if ($admin_user['role'] === 'author' && (int) $post['author_id'] !== (int) $admin_user['id']) {
        http_response_code(403);
        die('You can only edit your own posts.');
    }
}

/* ---------------------------------------------------------------
 *  Save
 * --------------------------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $title      = trim((string) ($_POST['title'] ?? ''));
    $slugInput  = trim((string) ($_POST['slug'] ?? ''));
    $excerpt    = trim((string) ($_POST['excerpt'] ?? ''));
    $content    = (string) ($_POST['content'] ?? '');
    $categoryId = (int) ($_POST['category_id'] ?? 0) ?: null;
    $status     = in_array($_POST['status'] ?? '', ['draft', 'published', 'scheduled'], true) ? $_POST['status'] : 'draft';
    $featured   = !empty($_POST['is_featured']) ? 1 : 0;
    $allowCmt   = !empty($_POST['allow_comments']) ? 1 : 0;
    $metaTitle  = trim((string) ($_POST['meta_title'] ?? ''));
    $metaDesc   = trim((string) ($_POST['meta_desc'] ?? ''));
    $coverImage = trim((string) ($_POST['cover_image'] ?? ''));
    $coverAlt   = trim((string) ($_POST['cover_alt'] ?? ''));
    $tagNames   = array_filter(array_map('trim', explode(',', (string) ($_POST['tags'] ?? ''))));
    $publishAt  = trim((string) ($_POST['published_at'] ?? ''));

    /* SEO: every post must ship with a meta title + meta description.
       If the editor left the fields empty we derive them from the
       title/excerpt so the DB (and Google) never see an empty value. */
    if ($metaTitle === '') {
        $metaTitle = mb_substr($title, 0, 190);
    }
    if ($metaDesc === '') {
        $base = $excerpt !== '' ? $excerpt : excerpt_text($content, 155);
        $metaDesc = mb_substr($base, 0, 300);
    }

    /* --- Editorial brief fields (audience / intent / outline / review) --- */
    $briefIntent = in_array($_POST['brief_intent'] ?? '',
        ['informational', 'tutorial', 'troubleshooting', 'comparison', 'evaluation', 'reference', 'career'], true)
        ? (string) $_POST['brief_intent'] : null;
    $briefStatus = in_array($_POST['brief_status'] ?? '',
        ['none', 'brief_ready', 'draft_ready', 'in_review', 'approved', 'needs_revision'], true)
        ? (string) $_POST['brief_status'] : 'none';
    $brief = [
        'audience' => mb_substr(trim((string) ($_POST['brief_audience'] ?? '')), 0, 300),
        'question' => mb_substr(trim((string) ($_POST['brief_question'] ?? '')), 0, 300),
        'intent'   => $briefIntent,
        'support'  => mb_substr(trim((string) ($_POST['brief_supporting'] ?? '')), 0, 6000),
        'outline'  => mb_substr(trim((string) ($_POST['brief_outline'] ?? '')), 0, 6000),
        'refs'     => mb_substr(trim((string) ($_POST['brief_references'] ?? '')), 0, 8000),
        'related'  => trim((string) ($_POST['brief_related'] ?? '')),
        'status'   => $briefStatus,
        'notes'    => mb_substr(trim((string) ($_POST['brief_notes'] ?? '')), 0, 6000),
    ];

    $errors = [];
    if ($title === '' || mb_strlen($title) > 200)  $errors[] = 'Title is required (max 200 chars).';
    if (mb_strlen($excerpt) > 500)                 $errors[] = 'Excerpt max 500 characters.';
    if (mb_strlen($metaTitle) > 190)               $errors[] = 'SEO title max 190 characters.';
    if (mb_strlen($metaDesc) > 300)                $errors[] = 'Meta description max 300 characters.';
    if (mb_strlen($coverAlt) > 200)                $errors[] = 'Image alt text max 200 characters.';
    if (trim(strip_tags($content)) === '')         $errors[] = 'Content cannot be empty.';

    // Sanitise HTML — allowlist
    $content = sanitize_html($content);

    if (!$errors) {
        $slug = $slugInput !== '' ? slugify($slugInput) : slugify($title);
        $slug = unique_slug('posts', $slug, $post ? (int) $post['id'] : null);

        // Reading time always derived from the actual text
        $reading = max(1, (int) ceil(str_word_count(strip_tags($content)) / 220));

        if ($post) {
            $pdo->prepare(
                'UPDATE posts SET title=?, slug=?, excerpt=?, content=?, cover_image=?, cover_alt=?, category_id=?,
                        status=?, is_featured=?, allow_comments=?, reading_time=?,
                        meta_title=?, meta_desc=?, published_at=?, updated_at=NOW()
                 WHERE id=?'
            )->execute([
                $title, $slug, $excerpt, $content,
                $coverImage !== '' ? $coverImage : null,
                $coverAlt !== '' ? $coverAlt : null,
                $categoryId, $status, $featured, $allowCmt, $reading,
                $metaTitle ?: null, $metaDesc ?: null,
                ($status === 'published' ? ($publishAt ?: ($post['published_at'] ?: date('Y-m-d H:i:s'))) : ($publishAt ?: $post['published_at'])),
                (int) $post['id'],
            ]);
            $postId = (int) $post['id'];
        } else {
            $pdo->prepare(
                'INSERT INTO posts (title, slug, excerpt, content, cover_image, cover_alt, author_id, category_id,
                                    status, is_featured, allow_comments, reading_time,
                                    meta_title, meta_desc, published_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)'
            )->execute([
                $title, $slug, $excerpt, $content,
                $coverImage !== '' ? $coverImage : null,
                $coverAlt !== '' ? $coverAlt : null,
                (int) $admin_user['id'], $categoryId,
                $status, $featured, $allowCmt, $reading,
                $metaTitle ?: null, $metaDesc ?: null,
                $status === 'published' ? ($publishAt ?: date('Y-m-d H:i:s')) : null,
            ]);
            $postId = (int) $pdo->lastInsertId();
        }

        // Sync tags
        $pdo->prepare('DELETE FROM post_tags WHERE post_id = ?')->execute([$postId]);
        foreach ($tagNames as $tagName) {
            $tagName = mb_substr($tagName, 0, 60);
            if ($tagName === '') continue;
            $tSlug = slugify($tagName);
            $ts = $pdo->prepare('SELECT id FROM tags WHERE slug = ?');
            $ts->execute([$tSlug]);
            $tagId = $ts->fetchColumn();
            if (!$tagId) {
                $pdo->prepare('INSERT INTO tags (name, slug) VALUES (?, ?)')->execute([$tagName, $tSlug]);
                $tagId = $pdo->lastInsertId();
            }
            $pdo->prepare('INSERT IGNORE INTO post_tags (post_id, tag_id) VALUES (?, ?)')
                ->execute([$postId, (int) $tagId]);
        }

        /* --- Editorial brief upsert -------------------------------------
         * Only real published slugs may appear in related_slugs so the
         * brief can never point at a nonexistent or draft page.
         * A row is written only when the brief holds data (or already
         * existed, so editors can clear fields again).                */
        $relSlugs = [];
        foreach (array_filter(array_map('trim', explode(',', $brief['related']))) as $rs) {
            $rs = slugify($rs);
            if ($rs === '' || in_array($rs, $relSlugs, true)) {
                continue;
            }
            $chk = $pdo->prepare("SELECT 1 FROM posts WHERE slug = ? AND status = 'published'");
            $chk->execute([$rs]);
            if ($chk->fetchColumn()) {
                $relSlugs[] = $rs;
            }
            if (count($relSlugs) >= 8) {
                break;
            }
        }
        $hasBriefData = $brief['audience'] !== '' || $brief['question'] !== '' || $brief['intent'] !== null
            || $brief['support'] !== '' || $brief['outline'] !== '' || $brief['refs'] !== ''
            || $relSlugs !== [] || $brief['status'] !== 'none' || $brief['notes'] !== '';
        $chk = $pdo->prepare('SELECT 1 FROM editorial_briefs WHERE post_id = ?');
        $chk->execute([$postId]);
        if ($hasBriefData || $chk->fetchColumn()) {
            $pdo->prepare(
                'INSERT INTO editorial_briefs (post_id, target_audience, primary_question, search_intent,
                        supporting_questions, outline, references_text, related_slugs,
                        review_status, review_notes, updated_by, updated_at)
                 VALUES (?,?,?,?,?,?,?,?,?,?,?,NOW())
                 ON DUPLICATE KEY UPDATE
                        target_audience=VALUES(target_audience), primary_question=VALUES(primary_question),
                        search_intent=VALUES(search_intent), supporting_questions=VALUES(supporting_questions),
                        outline=VALUES(outline), references_text=VALUES(references_text),
                        related_slugs=VALUES(related_slugs), review_status=VALUES(review_status),
                        review_notes=VALUES(review_notes), updated_by=VALUES(updated_by), updated_at=NOW()'
            )->execute([
                $postId,
                $brief['audience'] ?: null,
                $brief['question'] ?: null,
                $brief['intent'],
                $brief['support'] ?: null,
                $brief['outline'] ?: null,
                $brief['refs'] ?: null,
                implode(',', $relSlugs) ?: null,
                $brief['status'],
                $brief['notes'] ?: null,
                (int) $admin_user['id'],
            ]);
        }

        flash_set('success', $post ? 'Post updated.' : 'Post created.');
        // IndexNow: tell participating engines a published post changed.
        if ($status === 'published') {
            indexnow_submit([url('/post/' . $slug)]);
        }
        header('Location: ' . admin_url('post-edit.php?id=' . $postId));
        exit;
    }
}

/* --- Data for form --------------------------------------------------- */
$categories = get_categories_nested();   // tree order + depth for the select
$postTags   = $post ? implode(', ', array_column(get_post_tags((int) $post['id']), 'name')) : '';
$media      = $pdo->query('SELECT * FROM media ORDER BY created_at DESC LIMIT 60')->fetchAll();

$briefRow = null;
if ($post) {
    $bs = $pdo->prepare('SELECT * FROM editorial_briefs WHERE post_id = ?');
    $bs->execute([(int) $post['id']]);
    $briefRow = $bs->fetch() ?: null;
}
$intents = [
    ''              => 'Not set',
    'informational' => 'Informational — explain a concept',
    'tutorial'      => 'Tutorial — teach a procedure',
    'troubleshooting' => 'Troubleshooting — solve a problem',
    'comparison'    => 'Comparison — explain differences & trade-offs',
    'evaluation'    => 'Evaluation — help the reader choose',
    'reference'     => 'Reference — precise technical info',
    'career'        => 'Career / workflow guidance',
];
$reviewStatuses = [
    'none'           => 'Not set',
    'brief_ready'    => 'Brief ready',
    'draft_ready'    => 'Draft ready',
    'in_review'      => 'In review',
    'approved'       => 'Approved',
    'needs_revision' => 'Needs revision',
];

$admin_title = $post ? 'Edit Post' : 'Add New Post';
$admin_page  = $post ? 'posts' : 'new-post';
require __DIR__ . '/includes/layout-top.php';
?>

<?php if (!empty($errors)): ?>
  <div class="mb-6 rounded-xl border border-accent bg-accent-soft px-5 py-3.5 text-sm font-semibold text-accent">
    <?php foreach ($errors as $err): ?><p><?= e($err) ?></p><?php endforeach; ?>
  </div>
<?php endif; ?>

<form method="post" action="<?= admin_url('post-edit.php' . ($post ? '?id=' . (int) $post['id'] : '')) ?>" id="post-form">
  <?= csrf_field() ?>

  <div class="grid gap-6 xl:grid-cols-[1fr_340px]">

    <!-- ============ MAIN COLUMN ============ -->
    <div class="space-y-5">

      <div class="rounded-xl border border-line bg-surface p-5">
        <label class="kicker text-muted" for="title">Title *</label>
        <input id="title" name="title" required maxlength="200" value="<?= e($_POST['title'] ?? ($post['title'] ?? '')) ?>"
               placeholder="A clear, compelling headline…"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-3 font-display text-xl outline-none transition focus:border-primary"
               oninput="autoSlug(this.value)">

        <label class="kicker mt-4 block text-muted" for="slug">Slug (URL)</label>
        <div class="mt-1.5 flex items-center rounded-lg border border-line bg-paper focus-within:border-primary">
          <span class="pl-4 text-sm text-muted">/post/</span>
          <input id="slug" name="slug" value="<?= e($_POST['slug'] ?? ($post['slug'] ?? '')) ?>"
                 class="w-full bg-transparent px-1.5 py-3 text-sm outline-none" placeholder="auto-generated-from-title">
        </div>
      </div>

      <div class="rounded-xl border border-line bg-surface p-5">
        <label class="kicker text-muted" for="excerpt">Excerpt <span class="normal-case tracking-normal opacity-60">(used in cards &amp; meta description)</span></label>
        <textarea id="excerpt" name="excerpt" rows="2" maxlength="500"
                  class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none transition focus:border-primary"
                  placeholder="One-sentence summary…"><?= e($_POST['excerpt'] ?? ($post['excerpt'] ?? '')) ?></textarea>
        <p class="mt-1 text-right text-xs text-muted"><span id="excerpt-count">0</span>/500</p>
      </div>

      <!-- Editor -->
      <div class="rounded-xl border border-line bg-surface p-5">
        <label class="kicker text-muted">Content *</label>

        <!-- Toolbar -->
        <div class="mt-1.5 flex flex-wrap gap-1 rounded-t-lg border border-b-0 border-line bg-paper p-2" id="editor-toolbar">
          <button type="button" data-cmd="bold" title="Bold (Ctrl+B)" class="rounded px-2.5 py-1.5 text-sm font-bold hover:bg-ink hover:text-paper">B</button>
          <button type="button" data-cmd="italic" title="Italic (Ctrl+I)" class="rounded px-2.5 py-1.5 text-sm italic hover:bg-ink hover:text-paper">I</button>
          <button type="button" data-cmd="createLink" title="Insert link" class="rounded px-2.5 py-1.5 text-sm hover:bg-ink hover:text-paper">Link</button>
          <span class="mx-1 w-px bg-line"></span>
          <button type="button" data-block="h2" class="rounded px-2.5 py-1.5 text-sm font-semibold hover:bg-ink hover:text-paper">H2</button>
          <button type="button" data-block="h3" class="rounded px-2.5 py-1.5 text-sm font-semibold hover:bg-ink hover:text-paper">H3</button>
          <button type="button" data-block="p" class="rounded px-2.5 py-1.5 text-sm hover:bg-ink hover:text-paper">¶</button>
          <span class="mx-1 w-px bg-line"></span>
          <button type="button" data-cmd="formatBlock" data-val="blockquote" title="Quote" class="rounded px-2.5 py-1.5 text-sm hover:bg-ink hover:text-paper">❝</button>
          <button type="button" data-cmd="insertUnorderedList" title="Bullet list" class="rounded px-2.5 py-1.5 text-sm hover:bg-ink hover:text-paper">• List</button>
          <button type="button" data-cmd="insertOrderedList" title="Numbered list" class="rounded px-2.5 py-1.5 text-sm hover:bg-ink hover:text-paper">1. List</button>
          <span class="mx-1 w-px bg-line"></span>
          <button type="button" id="btn-code" title="Code block" class="rounded px-2.5 py-5 py-1.5 text-sm font-mono hover:bg-ink hover:text-paper">&lt;/&gt;</button>
          <button type="button" id="btn-hr" title="Divider" class="rounded px-2.5 py-1.5 text-sm hover:bg-ink hover:text-paper">—</button>
          <span class="mx-1 w-px bg-line"></span>
          <button type="button" data-cmd="removeFormat" title="Clear formatting" class="rounded px-2.5 py-1.5 text-sm hover:bg-ink hover:text-paper">Tx</button>
        </div>

        <div id="editor" contenteditable="true"
             class="editor-content min-h-[420px] rounded-b-lg border border-line bg-paper px-4 py-4 text-[0.98rem] leading-relaxed outline-none focus:border-primary"><?= $post['content'] ?? ($_POST['content'] ?? '') ?></div>

        <textarea name="content" id="content-input" class="hidden"><?= e($post['content'] ?? ($_POST['content'] ?? '')) ?></textarea>
      </div>

      <!-- SEO -->
      <div class="rounded-xl border border-line bg-surface p-5">
        <p class="kicker text-accent">SEO</p>
        <div class="mt-4 space-y-4">
          <div>
            <label class="kicker text-muted" for="meta-title">Meta title <span class="normal-case tracking-normal opacity-60">(required — auto-fills from the post title)</span></label>
            <input id="meta-title" name="meta_title" maxlength="190" value="<?= e($_POST['meta_title'] ?? ($post['meta_title'] ?? '')) ?>"
                   class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary"
                   placeholder="Defaults to post title">
            <p class="mt-1 text-[0.7rem] text-muted"><span id="mt-count" class="font-bold text-primary">0</span> / 60 recommended — Google truncates longer titles</p>
          </div>
          <div>
            <label class="kicker text-muted" for="meta-desc">Meta description <span class="normal-case tracking-normal opacity-60">(required — auto-fills from the excerpt)</span></label>
            <textarea id="meta-desc" name="meta_desc" rows="2" maxlength="300"
                      class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary"
                      placeholder="Defaults to excerpt…"><?= e($_POST['meta_desc'] ?? ($post['meta_desc'] ?? '')) ?></textarea>
            <p class="mt-1 text-[0.7rem] text-muted"><span id="md-count" class="font-bold text-primary">0</span> / 160 recommended — aim for 140–160 characters</p>
          </div>
          <div class="rounded-lg border border-line bg-paper p-4">
            <p class="text-xs text-muted">Google preview</p>
            <p class="mt-1.5 text-sm text-primary" id="preview-title"><?= e($metaPreview = ($_POST['meta_title'] ?? $post['meta_title'] ?? '') ?: ($_POST['title'] ?? $post['title'] ?? 'Post title')) ?></p>
            <p class="text-xs text-muted">example.com › post › slug</p>
            <p class="mt-1 text-xs text-ink-soft" id="preview-desc"><?= e(excerpt_text(($_POST['meta_desc'] ?? $post['meta_desc'] ?? '') ?: ($_POST['excerpt'] ?? $post['excerpt'] ?? ''), 155)) ?></p>
          </div>
        </div>
      </div>

      <!-- Editorial checklist (SEO workflow) -->
      <details class="rounded-xl border border-line bg-surface p-5" <?= $post ? '' : 'open' ?>>
        <summary class="kicker cursor-pointer select-none text-accent">Editorial checklist</summary>
        <ul class="mt-4 space-y-2 text-sm text-ink-soft">
          <li>☐ One clear H1 (post title) describing the actual subject</li>
          <li>☐ Opening paragraph answers the reader's main question directly</li>
          <li>☐ H2 sections follow the real structure of the topic (no keyword stuffing)</li>
          <li>☐ At least one practical example, code sample, table or step-by-step block</li>
          <li>☐ 2–3 contextual links to related TechScope articles (auto-links are added at render)</li>
          <li>☐ Unique meta title (≤ 60 chars) + meta description (140–160 chars)</li>
          <li>☐ Featured image with accurate alt text; lazy-loading handled automatically</li>
          <li>☐ Claims sourced; version numbers/dates included for fast-moving topics</li>
          <li>☐ Reviewed by a human before publishing — never publish unreviewed drafts</li>
        </ul>
      </details>

      <!-- Editorial brief (planning & review workflow) -->
      <details class="rounded-xl border border-line bg-surface p-5">
        <summary class="kicker cursor-pointer select-none text-accent">Editorial brief &amp; review</summary>
        <div class="mt-4 space-y-4">
          <div>
            <label class="kicker text-muted" for="brief-audience">Target audience</label>
            <input id="brief-audience" name="brief_audience" maxlength="300"
                   value="<?= e($_POST['brief_audience'] ?? ($briefRow['target_audience'] ?? '')) ?>"
                   placeholder="e.g. Backend developers evaluating indexing strategies"
                   class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
          </div>
          <div>
            <label class="kicker text-muted" for="brief-question">Primary question the article answers</label>
            <input id="brief-question" name="brief_question" maxlength="300"
                   value="<?= e($_POST['brief_question'] ?? ($briefRow['primary_question'] ?? '')) ?>"
                   placeholder="e.g. When does adding an index make a query slower?"
                   class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
          </div>
          <div>
            <label class="kicker text-muted" for="brief-intent">Search intent</label>
            <select id="brief-intent" name="brief_intent"
                    class="mt-1.5 w-full rounded-lg border border-line bg-paper px-3 py-2.5 text-sm outline-none focus:border-primary">
              <?php $selIntent = $_POST['brief_intent'] ?? ($briefRow['search_intent'] ?? ''); ?>
              <?php foreach ($intents as $val => $lab): ?>
                <option value="<?= e($val) ?>" <?= $selIntent === $val ? 'selected' : '' ?>><?= e($lab) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div>
            <label class="kicker text-muted" for="brief-supporting">Supporting questions <span class="normal-case tracking-normal opacity-60">(one per line)</span></label>
            <textarea id="brief-supporting" name="brief_supporting" rows="3"
                      placeholder="What makes an index slow to use?&#10;How do composite indexes change the plan?"
                      class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary"><?= e($_POST['brief_supporting'] ?? ($briefRow['supporting_questions'] ?? '')) ?></textarea>
          </div>
          <div>
            <label class="kicker text-muted" for="brief-outline">Proposed outline <span class="normal-case tracking-normal opacity-60">(H2/H3, one per line)</span></label>
            <textarea id="brief-outline" name="brief_outline" rows="4"
                      placeholder="How indexes actually speed up reads&#10;Composite indexes and column order"
                      class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary"><?= e($_POST['brief_outline'] ?? ($briefRow['outline'] ?? '')) ?></textarea>
          </div>
          <div>
            <label class="kicker text-muted" for="brief-references">References <span class="normal-case tracking-normal opacity-60">(one per line: “Label - https://…” or bare URL; shown publicly)</span></label>
            <textarea id="brief-references" name="brief_references" rows="3"
                      placeholder="MySQL 8.0 Reference — https://dev.mysql.com/…"
                      class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary"><?= e($_POST['brief_references'] ?? ($briefRow['references_text'] ?? '')) ?></textarea>
          </div>
          <div>
            <label class="kicker text-muted" for="brief-related">Related articles <span class="normal-case tracking-normal opacity-60">(comma-separated published slugs — invalid ones are dropped)</span></label>
            <input id="brief-related" name="brief_related" maxlength="500"
                   value="<?= e($_POST['brief_related'] ?? ($briefRow['related_slugs'] ?? '')) ?>"
                   placeholder="sql-indexing-practical-guide, feature-engineering-tabular-data"
                   class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary">
          </div>
          <div class="grid gap-4 sm:grid-cols-2">
            <div>
              <label class="kicker text-muted" for="brief-status">Review status</label>
              <select id="brief-status" name="brief_status"
                      class="mt-1.5 w-full rounded-lg border border-line bg-paper px-3 py-2.5 text-sm outline-none focus:border-primary">
                <?php $selStatus = $_POST['brief_status'] ?? ($briefRow['review_status'] ?? 'none'); ?>
                <?php foreach ($reviewStatuses as $val => $lab): ?>
                  <option value="<?= e($val) ?>" <?= $selStatus === $val ? 'selected' : '' ?>><?= e($lab) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="flex items-end text-xs text-muted">
              <?php if (!empty($briefRow['updated_at'])): ?>
                Last edited by editor #<?= (int) $briefRow['updated_by'] ?> on <?= e(date('M j, Y H:i', strtotime($briefRow['updated_at']))) ?>
              <?php else: ?>
                No brief saved yet.
              <?php endif; ?>
            </div>
          </div>
          <div>
            <label class="kicker text-muted" for="brief-notes">Review notes <span class="normal-case tracking-normal opacity-60">(internal — not shown publicly)</span></label>
            <textarea id="brief-notes" name="brief_notes" rows="2"
                      placeholder="Needs a worked EXPLAIN example before approval."
                      class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-2.5 text-sm outline-none focus:border-primary"><?= e($_POST['brief_notes'] ?? ($briefRow['review_notes'] ?? '')) ?></textarea>
          </div>
        </div>
      </details>
    </div>

    <!-- ============ SIDEBAR ============ -->
    <div class="space-y-5">

      <!-- Publish -->
      <div class="rounded-xl border border-line bg-surface p-5">
        <label class="kicker text-muted" for="status">Status</label>
        <select id="status" name="status" class="mt-1.5 w-full rounded-lg border border-line bg-paper px-3 py-2.5 text-sm outline-none focus:border-primary">
          <option value="draft"     <?= (($post['status'] ?? 'draft') === 'draft') ? 'selected' : '' ?>>Draft</option>
          <option value="published" <?= (($post['status'] ?? '') === 'published') ? 'selected' : '' ?>>Published</option>
          <option value="scheduled" <?= (($post['status'] ?? '') === 'scheduled') ? 'selected' : '' ?>>Scheduled</option>
        </select>

        <label class="kicker mt-4 block text-muted" for="published_at">Publish date</label>
        <input id="published_at" type="datetime-local" name="published_at"
               value="<?= e(!empty($post['published_at']) ? date('Y-m-d\TH:i', strtotime($post['published_at'])) : '') ?>"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-3 py-2.5 text-sm outline-none focus:border-primary">

        <div class="mt-4 space-y-2.5 border-t border-line pt-4 text-sm">
          <label class="flex cursor-pointer items-center gap-2.5">
            <input type="checkbox" name="is_featured" value="1" <?= !empty($post['is_featured']) ? 'checked' : '' ?> class="accent-accent">
            <span class="font-semibold">Featured on homepage</span>
          </label>
          <label class="flex cursor-pointer items-center gap-2.5">
            <input type="checkbox" name="allow_comments" value="1" <?= ($post['allow_comments'] ?? 1) ? 'checked' : '' ?> class="accent-accent">
            <span class="font-semibold">Allow comments</span>
          </label>
        </div>

        <div class="mt-5 grid grid-cols-2 gap-2.5 border-t border-line pt-4">
          <button name="action" value="draft" class="rounded-lg border border-line py-2.5 text-xs font-bold transition hover:border-ink">Save draft</button>
          <button name="action" value="publish" class="rounded-lg bg-ink py-2.5 text-xs font-bold text-paper transition hover:bg-accent">
            <?= $post ? 'Update' : 'Publish' ?>
          </button>
        </div>
        <?php if ($post): ?>
          <a href="<?= url('/post/' . $post['slug']) ?>" target="_blank"
             class="mt-2.5 block rounded-lg border border-line py-2.5 text-center text-xs font-bold transition hover:border-primary hover:text-primary">
            View live post &rarr;
          </a>
        <?php endif; ?>
      </div>

      <!-- Category -->
      <div class="rounded-xl border border-line bg-surface p-5">
        <label class="kicker text-muted" for="category_id">Category</label>
        <select id="category_id" name="category_id" class="mt-1.5 w-full rounded-lg border border-line bg-paper px-3 py-2.5 text-sm outline-none focus:border-primary">
          <option value="0">— None —</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int) $c['id'] ?>" <?= ($categoryId ?? ($post['category_id'] ?? 0)) == $c['id'] ? 'selected' : '' ?>>
              <?= str_repeat('— ', (int) ($c['depth'] ?? 0)) . e($c['name']) ?>
            </option>
          <?php endforeach; ?>
        </select>

        <div class="mt-2.5 flex flex-wrap gap-2">
          <button type="button" id="add-cat-toggle"
                  class="rounded-lg border border-line px-3 py-1.5 text-xs font-bold transition hover:border-ink hover:bg-ink hover:text-paper">
            + New category
          </button>
          <button type="button" id="add-sub-toggle"
                  class="rounded-lg border border-line px-3 py-1.5 text-xs font-bold transition hover:border-ink hover:bg-ink hover:text-paper">
            + Subcategory
          </button>
          <a href="<?= admin_url('categories.php') ?>" target="_blank" rel="noopener"
             class="rounded-lg px-3 py-1.5 text-xs font-semibold text-primary transition hover:text-accent">Manage all →</a>
        </div>

        <!-- inline quick-creator (no page reload, no losing the draft) -->
        <div id="cat-creator" class="mt-3 hidden rounded-lg border border-line bg-paper p-3">
          <label class="kicker text-muted" for="cat-new-name">Name *</label>
          <input id="cat-new-name" maxlength="80" placeholder="e.g. Neural Networks"
                 autocomplete="off"
                 class="mt-1 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm outline-none focus:border-primary">
          <label class="kicker mt-2.5 block text-muted" for="cat-new-parent">Parent</label>
          <select id="cat-new-parent"
                  class="mt-1 w-full rounded-lg border border-line bg-surface px-3 py-2 text-sm outline-none focus:border-primary">
            <option value="0">— Top level (main navbar category) —</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= (int) $c['id'] ?>"><?= str_repeat('— ', (int) ($c['depth'] ?? 0)) . e($c['name']) ?></option>
            <?php endforeach; ?>
          </select>
          <div class="mt-3 flex gap-2">
            <button type="button" id="cat-create" class="flex-1 rounded-lg bg-ink py-2 text-xs font-bold text-paper transition hover:bg-accent">
              Create &amp; select
            </button>
            <button type="button" id="cat-cancel" class="rounded-lg border border-line px-3 py-2 text-xs font-bold transition hover:border-ink">
              Cancel
            </button>
          </div>
          <p id="cat-msg" class="mt-2 hidden text-xs"></p>
        </div>

        <p class="mt-2.5 text-[0.7rem] leading-relaxed text-muted">
          Subcategories appear in the navbar dropdown instantly and get their own page at
          <span class="font-semibold">/category/…</span> — parent pages automatically include their posts.
        </p>
      </div>

      <!-- Tags -->
      <div class="rounded-xl border border-line bg-surface p-5">
        <label class="kicker text-muted" for="tags">Tags <span class="normal-case tracking-normal opacity-60">(comma separated)</span></label>
        <input id="tags" name="tags" value="<?= e($postTags !== '' ? $postTags : ($_POST['tags'] ?? '')) ?>"
               placeholder="ai, tutorial, php"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-3 py-2.5 text-sm outline-none focus:border-primary">
        <div class="mt-2.5 flex flex-wrap gap-1.5" id="tag-suggestions">
          <?php foreach (array_slice(get_popular_tags(10), 0, 10) as $t): ?>
            <button type="button" data-tag="<?= e($t['name']) ?>"
                    class="rounded border border-line px-2 py-0.5 text-[0.7rem] font-semibold transition hover:border-accent hover:text-accent">
              +<?= e($t['name']) ?>
            </button>
          <?php endforeach; ?>
        </div>
      </div>

      <!-- Cover image -->
      <?php
      // Preview src must resolve to a URL; the hidden input keeps the raw stored value
      $_cvRaw = (string) ($_POST['cover_image'] ?? ($post['cover_image'] ?? ''));
      $_cvSrc = $_cvRaw;
      if ($_cvSrc !== '' && !str_starts_with($_cvSrc, 'http://') && !str_starts_with($_cvSrc, 'https://') && !str_starts_with($_cvSrc, 'data:')) {
          if (str_starts_with($_cvSrc, 'assets/')) {
              $_cvSrc = url('/' . ltrim($_cvSrc, '/'));
          } elseif (str_starts_with($_cvSrc, '/')) {
              $_cvSrc = url($_cvSrc);
          } else {
              $_cvSrc = UPLOADS_URL . '/' . ltrim($_cvSrc, '/');
          }
      }
      ?>
      <div class="rounded-xl border border-line bg-surface p-5">
        <label class="kicker text-muted">Cover image</label>
        <div class="mt-2 rounded-lg border border-dashed border-line p-3 text-center">
          <img id="cover-preview"<?= $_cvSrc !== '' ? ' src="' . e($_cvSrc) . '"' : '' ?>
               class="<?= $_cvSrc !== '' ? '' : 'hidden ' ?>mx-auto mb-2 max-h-36 w-full rounded object-cover">
          <p id="cover-empty" class="<?= $_cvSrc !== '' ? 'hidden ' : '' ?>text-xs text-muted">No image selected (placeholder will be generated)</p>
        </div>
        <input type="hidden" name="cover_image" id="cover-input" value="<?= e($_POST['cover_image'] ?? ($post['cover_image'] ?? '')) ?>">
        <label class="kicker mt-3 block text-muted" for="cover-alt">Image alt text</label>
        <input id="cover-alt" name="cover_alt" maxlength="200"
               value="<?= e($_POST['cover_alt'] ?? ($post['cover_alt'] ?? '')) ?>"
               placeholder="Describe the image (leave empty for auto)"
               class="mt-1.5 w-full rounded-lg border border-line bg-paper px-3 py-2 text-xs outline-none focus:border-primary">
        <button type="button" id="open-media" class="mt-2.5 w-full rounded-lg border border-line py-2.5 text-xs font-bold transition hover:border-ink">
          Choose from media library
        </button>
        <button type="button" id="clear-cover" class="mt-2 w-full rounded-lg border border-line py-2 text-xs font-semibold text-muted transition hover:border-accent hover:text-accent">
          Remove image
        </button>
      </div>
    </div>
  </div>
</form>

<!-- Media picker modal -->
<div id="media-modal" class="fixed inset-0 z-[70] hidden items-center justify-center bg-black/60 p-6">
  <div class="max-h-[80vh] w-full max-w-3xl overflow-hidden rounded-2xl bg-surface">
    <div class="flex items-center justify-between border-b border-line px-5 py-4">
      <h3 class="font-display text-lg font-semibold">Media library</h3>
      <button id="close-media" class="text-xl text-muted hover:text-ink">&times;</button>
    </div>
    <div class="max-h-[60vh] overflow-y-auto p-5">
      <?php if (!$media): ?>
        <p class="py-10 text-center text-sm text-muted">Library is empty — upload images in <a href="<?= admin_url('media.php') ?>" class="font-semibold text-primary">Media</a>.</p>
      <?php else: ?>
        <div class="grid grid-cols-3 gap-3 sm:grid-cols-4">
          <?php foreach ($media as $m): ?>
            <button type="button" data-src="<?= url('/assets/uploads/' . $m['path']) ?>" class="media-pick group overflow-hidden rounded-lg border border-line transition hover:border-accent">
              <img src="<?= url('/assets/uploads/' . $m['path']) ?>" alt="<?= e($m['alt_text'] ?? '') ?>" class="h-24 w-full object-cover">
            </button>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<script>
/* ---- Auto slug ---- */
let slugTouched = <?= !empty($post['slug']) ? 'true' : 'false' ?>;
document.getElementById('slug')?.addEventListener('input', () => slugTouched = true);

function autoSlug(v) {
  if (slugTouched) return;
  document.getElementById('slug').value = v.toLowerCase().trim()
    .replace(/[^a-z0-9\s-]/g, '').replace(/[\s]+/g, '-').replace(/-+/g, '-').replace(/^-|-$/g, '');
}

/* ---- Excerpt counter ---- */
const exc = document.getElementById('excerpt');
exc?.addEventListener('input', () => document.getElementById('excerpt-count').textContent = exc.value.length);

/* ---- Tag suggestions ---- */
document.querySelectorAll('#tag-suggestions [data-tag]').forEach(btn => {
  btn.addEventListener('click', () => {
    const inp = document.getElementById('tags');
    const cur = inp.value.split(',').map(s => s.trim()).filter(Boolean);
    if (!cur.includes(btn.dataset.tag)) { cur.push(btn.dataset.tag); inp.value = cur.join(', '); }
  });
});

/* ---- Cover picker ---- */
const modal = document.getElementById('media-modal');
document.getElementById('open-media')?.addEventListener('click', () => { modal.classList.remove('hidden'); modal.classList.add('flex'); });
document.getElementById('close-media')?.addEventListener('click', () => { modal.classList.add('hidden'); modal.classList.remove('flex'); });
modal?.addEventListener('click', e => { if (e.target === modal) { modal.classList.add('hidden'); modal.classList.remove('flex'); } });

function setCover(src) {
  document.getElementById('cover-input').value = src.replace('/assets/uploads/', '');
  const img = document.getElementById('cover-preview');
  img.src = src; img.classList.remove('hidden');
  document.getElementById('cover-empty').classList.add('hidden');
}
document.querySelectorAll('.media-pick').forEach(b => b.addEventListener('click', () => {
  setCover(b.dataset.src);
  modal.classList.add('hidden'); modal.classList.remove('flex');
}));
document.getElementById('clear-cover')?.addEventListener('click', () => {
  document.getElementById('cover-input').value = '';
  const img = document.getElementById('cover-preview');
  img.classList.add('hidden'); img.removeAttribute('src');
  document.getElementById('cover-empty').classList.remove('hidden');
});

/* ---- SEO preview ---- */
const mt = document.getElementById('meta-title'), md = document.getElementById('meta-desc'),
      tt = document.getElementById('title'), ex = document.getElementById('excerpt');
function seoPreview() {
  document.getElementById('preview-title').textContent = (mt.value || tt.value) || 'Post title';
  document.getElementById('preview-desc').textContent = (md.value || ex.value).slice(0, 155);
}
[mt, md, tt, ex].forEach(el => el?.addEventListener('input', seoPreview));

/* ---- SEO counters (green = in the ideal range, red = off) ---- */
function seoCounts() {
  const mc = document.getElementById('mt-count'), dc = document.getElementById('md-count');
  if (mc && mt) {
    const n = mt.value.length;
    mc.textContent = n;
    mc.className = (n > 0 && n <= 60) ? 'font-bold text-primary' : 'font-bold text-accent';
  }
  if (dc && md) {
    const n = md.value.length;
    dc.textContent = n;
    dc.className = (n >= 140 && n <= 160) ? 'font-bold text-primary' : 'font-bold text-accent';
  }
}
[mt, md].forEach(el => el?.addEventListener('input', seoCounts));
seoCounts();

/* ---- Quick category / subcategory creation (no reload) ---- */
const catSelect  = document.getElementById('category_id');
const catCreator = document.getElementById('cat-creator');
const catMsg     = document.getElementById('cat-msg');

function showCatMsg(text, isError) {
  if (!catMsg) return;
  catMsg.textContent = text;
  catMsg.className = 'mt-2 text-xs ' + (isError ? 'text-accent font-semibold' : 'text-primary font-semibold');
}
function openCreator(mode) {                 // 'cat' = top level, 'sub' = under selected
  catCreator.classList.remove('hidden');
  const parentSel = document.getElementById('cat-new-parent');
  parentSel.value = (mode === 'sub' && catSelect && catSelect.value !== '0') ? catSelect.value : '0';
  const nameInput = document.getElementById('cat-new-name');
  nameInput.value = '';
  showCatMsg('', false);
  catMsg.classList.add('hidden');
  nameInput.focus();
}
document.getElementById('add-cat-toggle')?.addEventListener('click', () => openCreator('cat'));
document.getElementById('add-sub-toggle')?.addEventListener('click', () => openCreator('sub'));
document.getElementById('cat-cancel')?.addEventListener('click', () => catCreator.classList.add('hidden'));

document.getElementById('cat-create')?.addEventListener('click', async () => {
  const nameInput = document.getElementById('cat-new-name');
  const parentSel = document.getElementById('cat-new-parent');
  const name = nameInput.value.trim();
  if (!name) { showCatMsg('Please enter a category name.', true); nameInput.focus(); return; }

  const btn = document.getElementById('cat-create');
  btn.disabled = true;
  btn.textContent = 'Creating…';
  try {
    const res = await fetch('<?= admin_url('categories.php') ?>?ajax=1', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
      body: new URLSearchParams({
        csrf_token: '<?= e(csrf_token()) ?>',
        name: name,
        parent_id: parentSel.value
      })
    });
    const data = await res.json();
    if (data.ok) {
      const opt = document.createElement('option');
      opt.value = data.id;
      opt.textContent = (Number(data.parent) > 0 ? '— ' : '') + data.name;
      opt.selected = true;
      catSelect.appendChild(opt);
      // make it selectable as a parent for future subcategories too
      const po = document.createElement('option');
      po.value = data.id;
      po.textContent = data.name;
      parentSel.appendChild(po);
      showCatMsg('Created “' + data.name + '” and selected it.', false);
      setTimeout(() => catCreator.classList.add('hidden'), 1000);
    } else {
      showCatMsg(data.error || 'Could not create the category.', true);
    }
  } catch (err) {
    showCatMsg('Network error — please try again.', true);
  } finally {
    btn.disabled = false;
    btn.innerHTML = 'Create &amp; select';
  }
});

/* ---- Sync editor → textarea + guarantee SEO fields before submit ---- */
document.getElementById('post-form')?.addEventListener('submit', () => {
  document.getElementById('content-input').value = document.getElementById('editor').innerHTML;
  // "Must add": never save a post without meta title / meta description
  if (mt && !mt.value.trim()) mt.value = (tt?.value || '').trim().slice(0, 190);
  if (md && !md.value.trim()) {
    const fromEx = (ex?.value || '').trim();
    const fromBody = (document.getElementById('content-input').value || '')
      .replace(/<[^>]+>/g, ' ').replace(/\s+/g, ' ').trim();
    md.value = (fromEx || fromBody).slice(0, 300);
  }
  seoPreview();
  seoCounts();
});
</script>

<?php require __DIR__ . '/includes/layout-bottom.php'; ?>
