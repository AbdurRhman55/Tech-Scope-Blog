<?php
/**
 *  Breadcrumb — expects $crumbs = ['Label' => '/path', ...]
 *  Renders visible nav (SEO) — schema is added by the calling page.
 */

if (!empty($crumbs) && is_array($crumbs)): ?>
<nav aria-label="Breadcrumb" class="border-b border-line bg-surface">
  <div class="mx-auto max-w-[96rem] px-6 py-3">
    <ol class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-semibold text-muted">
      <?php
      $last = count($crumbs) - 1;
      $i    = 0;
      foreach ($crumbs as $label => $path):
          $isLast = $i++ === $last;
      ?>
        <li class="flex items-center gap-2">
          <?php if ($i > 1): ?>
            <svg class="h-3 w-3 text-line" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5"/></svg>
          <?php endif; ?>
          <?php if ($isLast): ?>
            <span class="text-ink"><?= e($label) ?></span>
          <?php else: ?>
            <a href="<?= url($path) ?>" class="transition hover:text-accent"><?= e($label) ?></a>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</nav>
<?php endif; ?>
