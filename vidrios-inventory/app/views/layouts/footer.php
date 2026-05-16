<footer class="footnote">
    <span class="footnote__line"></span>
    <span class="footnote__text">
        <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>
        — Hecho a mano con PHP puro, MySQL y CSS sin frameworks.
    </span>
</footer>

<?php $jsV = @filemtime(ROOT . '/public/js/app.js') ?: time(); ?>
<script src="<?= BASE_URL ?>/public/js/app.js?v=<?= $jsV ?>" defer></script>
<?php if (!empty($usuario) && defined('REALTIME_ENABLED') && REALTIME_ENABLED): ?>
    <?php $rtJsV = @filemtime(ROOT . '/public/js/realtime.js') ?: time(); ?>
    <script src="<?= BASE_URL ?>/public/js/realtime.js?v=<?= $rtJsV ?>" defer></script>
<?php endif; ?>
</body>
</html>
