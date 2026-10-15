<?php
/**
 *  Contact Us — message handling (POST) + form
 */

require_once __DIR__ . '/includes/functions.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    if (($_POST['action'] ?? '') === 'add_message') {
        $name    = trim((string) ($_POST['name'] ?? ''));
        $email   = trim((string) ($_POST['email'] ?? ''));
        $subject = trim((string) ($_POST['subject'] ?? ''));
        $body    = trim((string) ($_POST['body'] ?? ''));

        if ($name === '' || mb_strlen($name) > 100)         $errors[] = 'Valid name is required.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))      $errors[] = 'Valid email is required.';
        if ($subject === '' || mb_strlen($subject) > 200)    $errors[] = 'Subject is required.';
        if ($body === '' || mb_strlen($body) > 5000)         $errors[] = 'Message is required.';

        // Honeypot
        if (!empty($_POST['website'])) {
            $errors[] = 'Invalid submission.';
        }

        if (!$errors) {
            $stmt = db()->prepare(
                'INSERT INTO messages (name, email, subject, body, ip_address) VALUES (?, ?, ?, ?, INET6_ATON(?))'
            );
            $stmt->execute([
                $name, $email, $subject, $body,
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            ]);
            flash_set('success', 'Thanks for reaching out! We will get back to you soon.');
            header('Location: ' . url('/contact'));
            exit;
        }
    }
}

$page_title = 'Contact Us | ' . setting('site_name', SITE_NAME);
$page_desc  = 'Get in touch with the ' . setting('site_name') . ' team — questions, feedback, guest posts and partnerships.';
require __DIR__ . '/includes/header.php';
?>

<section class="border-b border-line bg-surface">
  <div class="mx-auto max-w-[96rem] px-6 py-14">
    <p class="kicker text-accent reveal reveal-1">Contact</p>
    <h1 class="headline-xl mt-3 reveal reveal-2">Let&rsquo;s talk</h1>
    <p class="mt-4 max-w-2xl text-muted reveal reveal-3">
      Whether you have a question, feedback, a correction, or want to contribute — we read every message.
    </p>
  </div>
</section>

<section class="mx-auto max-w-[96rem] px-6 py-14">
  <div class="grid gap-12 lg:grid-cols-[1fr_1.6fr]">

    <!-- Info column -->
    <aside class="space-y-7">
      <div class="rounded-2xl border border-line bg-surface p-6">
        <h2 class="kicker text-accent">Direct details</h2>
        <ul class="mt-4 space-y-4 text-sm">
          <?php if (setting('contact_email')): ?>
            <li class="flex items-start gap-3">
              <svg class="mt-0.5 h-5 w-5 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75"/></svg>
              <div>
                <p class="kicker text-muted">Email</p>
                <a href="mailto:<?= e(setting('contact_email')) ?>" class="font-semibold text-ink transition hover:text-accent"><?= e(setting('contact_email')) ?></a>
              </div>
            </li>
          <?php endif; ?>
          <?php if (setting('contact_phone')): ?>
            <li class="flex items-start gap-3">
              <svg class="mt-0.5 h-5 w-5 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293a.75.75 0 0 1-.987.146 12.04 12.04 0 0 1-5.673-5.673.75.75 0 0 1 .146-.987l1.293-.97c.362-.271.527-.734.417-1.173L7.09 3.102A1.125 1.125 0 0 0 6 2.25H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z"/></svg>
              <div>
                <p class="kicker text-muted">Phone</p>
                <a href="tel:<?= e(setting('contact_phone')) ?>" class="font-semibold text-ink transition hover:text-accent"><?= e(setting('contact_phone')) ?></a>
              </div>
            </li>
          <?php endif; ?>
          <?php if (setting('contact_address')): ?>
            <li class="flex items-start gap-3">
              <svg class="mt-0.5 h-5 w-5 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z"/></svg>
              <div>
                <p class="kicker text-muted">Address</p>
                <p class="font-semibold text-ink"><?= e(setting('contact_address')) ?></p>
              </div>
            </li>
          <?php endif; ?>
          <li class="flex items-start gap-3">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-primary" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            <div>
              <p class="kicker text-muted">Response time</p>
              <p class="font-semibold text-ink">Within 24–48 hours</p>
            </div>
          </li>
        </ul>
      </div>

      <div class="rounded-2xl border border-line bg-ink p-6 text-paper">
        <h2 class="kicker text-accent">Before you write</h2>
        <ul class="mt-4 space-y-2.5 text-sm text-paper/70">
          <li>· Bug reports help — include browser + steps.</li>
          <li>· Correction requests are priority.</li>
          <li>· Guest post pitches: include 2 topic ideas.</li>
        </ul>
      </div>
    </aside>

    <!-- Form column -->
    <div class="rounded-2xl border border-line bg-surface p-7 sm:p-9">
      <?php if ($errors): ?>
        <div class="mb-6 rounded-xl border border-accent bg-accent-soft px-5 py-3.5 text-sm font-semibold text-accent">
          <?php foreach ($errors as $err): ?>
            <p><?= e($err) ?></p>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>

      <h2 class="headline-lg">Send a message</h2>
      <p class="mt-2 text-sm text-muted">All fields marked * are required.</p>

      <form method="post" action="<?= url('/contact') ?>" class="mt-7 space-y-5">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add_message">
        <!-- honeypot -->
        <input type="text" name="website" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

        <div class="grid gap-5 sm:grid-cols-2">
          <div>
            <label for="f-name" class="kicker text-muted">Name *</label>
            <input id="f-name" name="name" required maxlength="100" value="<?= e($_POST['name'] ?? '') ?>"
                   class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-3 text-sm outline-none transition focus:border-primary">
          </div>
          <div>
            <label for="f-email" class="kicker text-muted">Email *</label>
            <input id="f-email" type="email" name="email" required maxlength="190" value="<?= e($_POST['email'] ?? '') ?>"
                   class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-3 text-sm outline-none transition focus:border-primary">
          </div>
        </div>

        <div>
          <label for="f-subject" class="kicker text-muted">Subject *</label>
          <input id="f-subject" name="subject" required maxlength="200" value="<?= e($_POST['subject'] ?? '') ?>"
                 class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-3 text-sm outline-none transition focus:border-primary">
        </div>

        <div>
          <label for="f-body" class="kicker text-muted">Message *</label>
          <textarea id="f-body" name="body" required rows="7" maxlength="5000"
                    class="mt-1.5 w-full rounded-lg border border-line bg-paper px-4 py-3 text-sm outline-none transition focus:border-primary"><?= e($_POST['body'] ?? '') ?></textarea>
        </div>

        <div class="flex flex-wrap items-center gap-4">
          <button class="rounded-lg bg-ink px-7 py-3.5 text-sm font-bold text-paper transition hover:bg-accent">
            Send message
          </button>
          <span class="text-xs text-muted">We never share your details.</span>
        </div>
      </form>
    </div>
  </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
