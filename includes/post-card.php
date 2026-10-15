<?php
/**
 *  Shared post card — expects $post (row with category/author joins)
 *  Optional: $i (index for stagger reveal delay)
 *
 *  Alignment guarantees:
 *   - fixed 16:10 cover, equal-height cards (grid stretch)
 *   - 3-line clamped excerpts → titles/excerpts always align
 *   - footer pinned to the bottom with mt-auto
 */

if (!empty($post)):
    $ci   = $i ?? 0;
    $href = url('/post/' . $post['slug']);
?>
<article class="card-hover group flex flex-col overflow-hidden rounded-2xl border border-line bg-surface"
         data-reveal style="--reveal-delay: <?= ($ci % 3) * 0.07 ?>s">

  <a href="<?= $href ?>" class="zoom-frame block aspect-[16/10] overflow-hidden border-b border-line bg-paper">
    <img src="<?= e(post_image($post)) ?>" alt="<?= e($post['title']) ?>" loading="lazy" width="800" height="500"
         class="h-full w-full object-cover">
  </a>

  <div class="flex flex-1 flex-col p-5 sm:p-6">
    <div class="flex items-center gap-3">
      <?php if (!empty($post['category_name'])): ?>
        <a href="<?= url('/category/' . $post['category_slug']) ?>" class="kicker text-primary transition hover:text-accent">
          <?= e($post['category_name']) ?>
        </a>
      <?php endif; ?>
      <span class="h-1 w-1 rounded-full bg-line"></span>
      <span class="text-xs text-muted"><?= reading_time($post['content']) ?> min read</span>
    </div>

    <h3 class="mt-2.5 font-display text-[1.3rem] font-semibold leading-snug">
      <a href="<?= $href ?>" class="transition group-hover:text-primary"><?= e($post['title']) ?></a>
    </h3>

    <p class="mt-2 line-clamp-3 text-sm leading-relaxed text-muted">
      <?= e($post['excerpt'] ?: excerpt_text($post['content'], 150)) ?>
    </p>

    <div class="mt-auto flex items-center justify-between gap-3 border-t border-line pt-4 text-xs text-muted">
      <a href="<?= url('/author/' . $post['author_slug']) ?>" class="font-bold text-ink transition hover:text-accent">
        <?= e($post['author_name']) ?>
      </a>
      <time datetime="<?= e($post['published_at'] ?? '') ?>"><?= e(time_ago($post['published_at'] ?? 'now')) ?></time>
    </div>
  </div>
</article>
<?php endif; ?>
