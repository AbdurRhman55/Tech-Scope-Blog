<?php
/**
 *  Shared legal page template
 *  Variables required: $legal_title, $legal_updated, $legal_sections[]
 *  Each section: ['h' => heading, 'b' => array of paragraph strings]
 */

require_once __DIR__ . '/functions.php';

$page_title = $legal_title . ' | ' . setting('site_name', SITE_NAME);
$page_desc  = $legal_title . ' for ' . setting('site_name', SITE_NAME) . '.';
require __DIR__ . '/header.php';
?>

<section class="border-b border-line bg-surface">
  <div class="mx-auto max-w-4xl px-6 py-14">
    <nav class="kicker text-muted">
      <a href="<?= url('/') ?>" class="transition hover:text-accent">Home</a>
      <span class="mx-2">/</span>
      <span class="text-accent"><?= e($legal_title) ?></span>
    </nav>
    <h1 class="headline-xl mt-4"><?= e($legal_title) ?></h1>
    <p class="mt-4 text-sm text-muted">Last updated: <time><?= e($legal_updated) ?></time></p>
  </div>
</section>

<section class="mx-auto max-w-4xl px-6 py-14">
  <!-- TOC -->
  <nav class="rounded-2xl border border-line bg-surface p-6">
    <p class="kicker text-accent">Contents</p>
    <ol class="mt-3 grid gap-1.5 text-sm sm:grid-cols-2">
      <?php foreach ($legal_sections as $i => $sec): ?>
        <li>
          <a href="#sec-<?= $i + 1 ?>" class="font-semibold text-ink-soft transition hover:text-accent">
            <?= $i + 1 ?>. <?= e($sec['h']) ?>
          </a>
        </li>
      <?php endforeach; ?>
    </ol>
  </nav>

  <div class="prose-ed mt-12">
    <?php foreach ($legal_sections as $i => $sec): ?>
      <h2 id="sec-<?= $i + 1 ?>"><?= e($sec['h']) ?></h2>
      <?php foreach ($sec['b'] as $para): ?>
        <p><?= $para /* pre-sanitised content */ ?></p>
      <?php endforeach; ?>
    <?php endforeach; ?>
  </div>

  <div class="mt-14 rounded-2xl border border-line bg-ink p-7 text-paper">
    <p class="kicker text-accent">Questions?</p>
    <p class="mt-2 text-sm text-paper/70">
      If anything here is unclear, contact us at
      <a href="<?= url('/contact') ?>" class="font-semibold text-paper underline underline-offset-4 hover:text-accent">our contact page</a>.
    </p>
  </div>
</section>

<?php require __DIR__ . '/footer.php'; ?>
