<?php
/**
 * The contact inbox.
 *
 * Email notification is best-effort and may be disabled by the host outright,
 * so this screen is where a message is reliably seen. That is also why the
 * dashboard carries the unread count.
 *
 * @var list<\App\Domain\Message\Message> $messages
 * @var array<string, int> $counts
 * @var bool   $showArchived
 * @var string $csrf
 */
?>

<header class="admin-page-head">
  <p class="t-eyebrow">Messages</p>
  <h1 class="t-display-3 u-mt-3"><?= $showArchived ? 'Archived' : 'Inbox' ?></h1>
  <p class="t-body-sm t-muted u-mt-4 admin-section__intro">
    Everything sent through the contact form is stored here. The email
    notification is a convenience &mdash; many hosts disable PHP&rsquo;s mail
    function entirely, and nothing is lost when they do, because this is the
    real channel.
  </p>
</header>

<section class="admin-section" aria-labelledby="inbox-title">
  <h2 class="admin-section__title" id="inbox-title">
    <?= $showArchived ? 'Archived' : 'Inbox' ?>
    <span class="admin-badge admin-badge--<?= ($counts['unread'] ?? 0) > 0 ? 'warn' : 'ok' ?>">
      <?= e((string) ($counts['unread'] ?? 0)) ?> unread
    </span>
    <span class="admin-badge admin-badge--muted"><?= e((string) ($counts['read'] ?? 0)) ?> read</span>
    <span class="admin-badge admin-badge--muted"><?= e((string) ($counts['archived'] ?? 0)) ?> archived</span>
  </h2>

  <p class="u-mt-5">
    <?php if ($showArchived): ?>
      <a class="btn btn--secondary btn--sm" href="<?= e(route_url('/admin/messages')) ?>">
        Back to the inbox
      </a>
    <?php else: ?>
      <a class="btn btn--ghost btn--sm" href="<?= e(route_url('/admin/messages?archived=1')) ?>">
        View archived (<?= e((string) ($counts['archived'] ?? 0)) ?>)
      </a>
    <?php endif; ?>
  </p>

  <?php if ($messages === []): ?>
    <p class="t-body-sm t-muted u-mt-6">
      <?= $showArchived
          ? 'Nothing archived yet.'
          : 'No messages yet. The form is live on the home page.' ?>
    </p>
  <?php else: ?>
    <ul class="admin-list u-mt-5">
      <?php foreach ($messages as $message): ?>
        <li class="admin-list__item">
          <span class="admin-list__body">
            <a class="admin-list__title" href="<?= e(route_url('/admin/messages/' . $message->id)) ?>">
              <?= e($message->displaySubject()) ?>
            </a>
            <span class="admin-list__meta">
              <?= e($message->name) ?> &middot; <?= e($message->email) ?>
              &middot; <?= e($message->createdAt) ?>
            </span>
            <span class="admin-list__meta"><?= e($message->preview()) ?></span>
          </span>

          <span class="admin-list__state">
            <?php if ($message->isUnread()): ?>
              <span class="admin-badge admin-badge--warn">unread</span>
            <?php elseif ($message->isArchived()): ?>
              <span class="admin-badge admin-badge--muted">archived</span>
            <?php else: ?>
              <span class="admin-badge admin-badge--ok">read</span>
            <?php endif; ?>
          </span>

          <span class="admin-list__links">
            <a class="t-link" href="<?= e(route_url('/admin/messages/' . $message->id)) ?>">Open</a>
          </span>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</section>
