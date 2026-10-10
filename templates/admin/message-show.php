<?php
/**
 * One message.
 *
 * Opening it marked it read — there is no button for that, because a step
 * that exists only to be forgotten leaves an unread badge meaning nothing.
 *
 * @var \App\Domain\Message\Message $message
 * @var string $csrf
 */
?>

<header class="admin-page-head">
  <p class="t-eyebrow">Message</p>
  <h1 class="t-display-3 u-mt-3"><?= e($message->displaySubject()) ?></h1>
  <p class="t-body-sm t-muted u-mt-4">
    From <strong><?= e($message->name) ?></strong>
    &lt;<?= e($message->email) ?>&gt;
    &middot; <?= e($message->createdAt) ?>
  </p>
</header>

<section class="admin-section" aria-labelledby="body-title">
  <h2 class="admin-section__title" id="body-title">What they wrote</h2>

  <?php /* Split on blank lines and escaped per paragraph — the same handling
           case-study sections get. Never raw HTML: this is the one field on
           the site a stranger controls. */ ?>
  <div class="u-mt-5">
    <?php foreach (preg_split('/\n\s*\n/', trim($message->body)) ?: [] as $paragraph): ?>
      <p class="t-body u-mt-4"><?= nl2br(e(trim($paragraph))) ?></p>
    <?php endforeach; ?>
  </div>
</section>

<section class="admin-section" aria-labelledby="reply-title">
  <h2 class="admin-section__title" id="reply-title">Reply</h2>

  <p class="t-body-sm t-muted admin-section__intro">
    This opens your own mail client, already addressed. Replies are not sent
    from the site on purpose &mdash; mail from a shared web server usually
    lands in spam, and you would have no copy in your own Sent folder.
  </p>

  <p class="u-mt-5">
    <a class="btn btn--primary" href="<?= e_url($message->replyUrl()) ?>">
      Reply to <?= e($message->name) ?> <span class="btn__arrow" aria-hidden="true">&rarr;</span>
    </a>
  </p>
</section>

<section class="admin-section" aria-labelledby="actions-title">
  <h2 class="admin-section__title" id="actions-title">Filing</h2>

  <div class="admin-actions u-mt-4">
    <?php if (!$message->isArchived()): ?>
      <form method="post" action="<?= e(route_url('/admin/messages/' . $message->id . '/state')) ?>">
        <input type="hidden" name="_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="status" value="archived">
        <button class="btn btn--secondary btn--sm" type="submit">Archive</button>
      </form>
    <?php else: ?>
      <form method="post" action="<?= e(route_url('/admin/messages/' . $message->id . '/state')) ?>">
        <input type="hidden" name="_token" value="<?= e($csrf) ?>">
        <input type="hidden" name="status" value="read">
        <button class="btn btn--secondary btn--sm" type="submit">Back to the inbox</button>
      </form>
    <?php endif; ?>

    <form method="post" action="<?= e(route_url('/admin/messages/' . $message->id . '/state')) ?>">
      <input type="hidden" name="_token" value="<?= e($csrf) ?>">
      <input type="hidden" name="status" value="unread">
      <button class="btn btn--ghost btn--sm" type="submit">Mark unread</button>
    </form>

    <form method="post" action="<?= e(route_url('/admin/messages/' . $message->id . '/delete')) ?>"
          data-confirm="Delete this message permanently? Archiving keeps it.">
      <input type="hidden" name="_token" value="<?= e($csrf) ?>">
      <button class="btn btn--ghost btn--sm" type="submit">Delete</button>
    </form>
  </div>

  <p class="field__help u-mt-4">
    Archiving is reversible and deleting is not.
    <?php if ($message->ipAddress !== null): ?>
      Sent from <code><?= e($message->ipAddress) ?></code>.
    <?php endif; ?>
  </p>

  <p class="u-mt-5">
    <a class="btn btn--ghost" href="<?= e(route_url('/admin/messages')) ?>">Back to messages</a>
  </p>
</section>
