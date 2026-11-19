</main>

<footer class="site-footer">
  <div class="container footer-grid">
    <div class="footer-brand">
      <a class="brand" href="<?= e(url()) ?>">
        <?= logo_html() ?>
        <span class="brand-text"><strong><?= e(site_name()) ?></strong><small><?= e(setting('site_title')) ?></small></span>
      </a>
      <p class="muted"><?= e(setting('footer_text')) ?></p>
      <div class="social-row">
        <?php foreach (social_links() as $s): ?>
          <a class="icon-btn" href="<?= e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= e($s['label']) ?>"><?= icon($s['icon']) ?></a>
        <?php endforeach; ?>
      </div>
    </div>
    <div>
      <h4>Explore</h4>
      <ul class="footer-links">
        <li><a href="<?= e(url('projects.php')) ?>">Projects</a></li>
        <li><a href="<?= e(url('research.php')) ?>">Research</a></li>
        <li><a href="<?= e(url('gallery.php')) ?>">Gallery</a></li>
        <li><a href="<?= e(url('downloads.php')) ?>">Downloads</a></li>
      </ul>
    </div>
    <div>
      <h4>About</h4>
      <ul class="footer-links">
        <li><a href="<?= e(url('about.php')) ?>">About me</a></li>
        <li><a href="<?= e(url('cv.php')) ?>">Curriculum Vitae</a></li>
        <li><a href="<?= e(url('contact.php')) ?>">Contact</a></li>
      </ul>
    </div>
    <div>
      <h4>Get in touch</h4>
      <ul class="footer-links">
        <?php if (setting('contact_email') !== ''): ?><li><a href="mailto:<?= e(setting('contact_email')) ?>"><?= icon('mail') ?> <?= e(setting('contact_email')) ?></a></li><?php endif; ?>
        <?php if (setting('contact_phone') !== ''): ?><li><a href="tel:<?= e(preg_replace('/[^0-9+]/', '', setting('contact_phone'))) ?>"><?= icon('phone') ?> <?= e(setting('contact_phone')) ?></a></li><?php endif; ?>
        <?php if (setting('contact_location') !== ''): ?><li><span><?= icon('map-pin') ?> <?= e(setting('contact_location')) ?></span></li><?php endif; ?>
        <li><a href="<?= e(url('contact.php')) ?>"><?= icon('send') ?> Send a message</a></li>
      </ul>
    </div>
  </div>
  <div class="container footer-bottom">
    <span>&copy; <?= date('Y') ?> <?= e(site_name()) ?>. All rights reserved.</span>
    <a href="#top" class="back-top" aria-label="Back to top"><?= icon('arrow-up') ?></a>
  </div>
</footer>

<div class="lightbox" data-lightbox hidden>
  <button class="icon-btn lb-close" type="button" data-lb-close aria-label="Close"><?= icon('x-close') ?></button>
  <button class="icon-btn lb-prev" type="button" data-lb-prev aria-label="Previous"><?= icon('arrow-left') ?></button>
  <figure><img alt=""><figcaption></figcaption></figure>
  <button class="icon-btn lb-next" type="button" data-lb-next aria-label="Next"><?= icon('arrow-right') ?></button>
</div>

<script src="<?= e(asset('js/main.js')) ?>" defer></script>
</body>
</html>
