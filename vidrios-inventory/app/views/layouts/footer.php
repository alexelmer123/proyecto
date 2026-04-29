<footer class="footnote">
    <span class="footnote__line"></span>
    <span class="footnote__text">
        <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>
        — Hecho a mano con PHP puro, MySQL y CSS sin frameworks.
    </span>
</footer>

<?php $jsV = @filemtime(ROOT . '/public/js/app.js') ?: time(); ?>
<script src="<?= BASE_URL ?>/public/js/app.js?v=<?= $jsV ?>" defer></script>
</body>
</html>
