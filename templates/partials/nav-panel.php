<?php /** @var bool $isHome */ $root = $isHome ? '' : route_url('/'); ?>
<div class="nav__panel" id="nav-panel" data-nav-panel>
  <ul class="nav__list">
    <li><a class="nav__link" href="<?= e($root) ?>#work">Work</a></li>
    <li><a class="nav__link" href="<?= e($root) ?>#about">About</a></li>
    <li><a class="nav__link" href="<?= e($root) ?>#services">Services</a></li>
    <li><a class="nav__link" href="<?= e($root) ?>#contact">Contact</a></li>
  </ul>
  <a class="btn btn--primary" href="<?= e($root) ?>#contact">Get in touch</a>
</div>
