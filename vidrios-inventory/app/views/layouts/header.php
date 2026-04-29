<?php /** @var array|null $usuario */ /** @var int $stockBajoCount */ /** @var array|null $flash */ ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="base-url" content="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars(($titulo ?? '') . ' · ' . APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>

    <script>
        (function () {
            try {
                var t = localStorage.getItem('vitralia-theme') || 'dark';
                if (t !== 'dark' && t !== 'light') t = 'dark';
                document.documentElement.setAttribute('data-theme', t);
            } catch (e) {}
        })();
    </script>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght,SOFT,WONK@9..144,300..900,0..100,0..1&family=Manrope:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap">

    <?php $cssV = @filemtime(ROOT . '/public/css/custom.css') ?: time(); ?>
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/custom.css?v=<?= $cssV ?>">
</head>
<body>
<div class="grain"></div>

<header class="topbar">
    <div class="topbar__brand">
        <span class="topbar__mark" aria-hidden="true">
            <img src="<?= BASE_URL ?>/public/img/logo-removebg-preview.png"
                 alt="<?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>"
                 class="topbar__logo">
        </span>
        <div class="topbar__titles">
            <span class="topbar__name"><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></span>
            <span class="topbar__sub"><?= htmlspecialchars(APP_TAGLINE, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>

    <div class="topbar__actions">
        <button type="button" class="theme-toggle" id="themeToggle"
                aria-label="Cambiar tema" title="Cambiar tema (claro / oscuro)">
            <span class="theme-toggle__icon theme-toggle__icon--moon" aria-hidden="true">
                <svg viewBox="0 0 24 24"><path d="M21 12.8A9 9 0 1 1 11.2 3a7 7 0 0 0 9.8 9.8z"/></svg>
            </span>
            <span class="theme-toggle__icon theme-toggle__icon--sun" aria-hidden="true">
                <svg viewBox="0 0 24 24">
                    <circle cx="12" cy="12" r="4"/>
                    <path d="M12 2v2M12 20v2M2 12h2M20 12h2M4.9 4.9l1.4 1.4M17.7 17.7l1.4 1.4M4.9 19.1l1.4-1.4M17.7 6.3l1.4-1.4"/>
                </svg>
            </span>
        </button>
        <?php if ($usuario): ?>
            <a href="<?= BASE_URL ?>/reporte/stockBajo" class="badge badge--alert" id="badgeStockBajo"
               title="Productos con stock crítico">
                <span class="badge__dot"></span>
                <span class="badge__label">Stock crítico</span>
                <span class="badge__count" data-stock-bajo><?= (int) $stockBajoCount ?></span>
            </a>
            <div class="user-pill">
                <span class="user-pill__avatar"><?= htmlspecialchars(strtoupper(substr((string) $usuario['nombre'], 0, 1)), ENT_QUOTES, 'UTF-8') ?></span>
                <span class="user-pill__meta">
                    <span class="user-pill__name"><?= htmlspecialchars((string) $usuario['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span class="user-pill__role"><?= htmlspecialchars((string) $usuario['rol'], ENT_QUOTES, 'UTF-8') ?></span>
                </span>
            </div>
            <a href="<?= BASE_URL ?>/auth/logout" class="btn btn--ghost">Salir</a>
        <?php endif; ?>
    </div>
</header>

<?php if ($flash): ?>
    <div class="flash flash--<?= htmlspecialchars((string) $flash['type'], ENT_QUOTES, 'UTF-8') ?>" role="status">
        <span class="flash__bar"></span>
        <span class="flash__msg"><?= htmlspecialchars((string) $flash['msg'], ENT_QUOTES, 'UTF-8') ?></span>
    </div>
<?php endif; ?>
