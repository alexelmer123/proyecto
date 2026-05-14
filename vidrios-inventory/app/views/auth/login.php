<?php /** @var ?string $error */ ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Acceso · <?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght,SOFT,WONK@9..144,300..900,0..100,0..1&family=Manrope:wght@300;400;500;600;700&family=JetBrains+Mono:wght@400;500;700&display=swap">
    <link rel="stylesheet" href="<?= BASE_URL ?>/public/css/custom.css">
</head>
<body class="auth-body">

<main class="auth">
    <section class="auth__card">
        <div class="auth__avatar" aria-hidden="true">
            <img src="<?= BASE_URL ?>/public/img/logo-removebg-preview.png"
                 alt="<?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?>">
        </div>

        <h1 class="auth__brand"><?= htmlspecialchars(APP_NAME, ENT_QUOTES, 'UTF-8') ?></h1>
        <p class="auth__brand-sub">Acceso al panel de inventario</p>

        <?php if ($error): ?>
            <div class="flash flash--error auth__error" role="alert">
                <span class="flash__bar"></span>
                <span class="flash__msg"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        <?php endif; ?>

        <form method="post" action="<?= BASE_URL ?>/auth/login" class="auth__form">
            <label class="auth__field">
                <span class="auth__field-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="5" width="18" height="14" rx="2"/>
                        <path d="M3 7l9 6 9-6"/>
                    </svg>
                </span>
                <input class="auth__field-input"
                       type="email"
                       name="email"
                       autocomplete="username"
                       required
                       placeholder="Correo electrónico">
            </label>

            <label class="auth__field">
                <span class="auth__field-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="4" y="11" width="16" height="10" rx="2"/>
                        <path d="M8 11V8a4 4 0 1 1 8 0v3"/>
                    </svg>
                </span>
                <input class="auth__field-input"
                       type="password"
                       name="password"
                       autocomplete="current-password"
                       required
                       placeholder="Contraseña">
            </label>

            <button type="submit" class="auth__cta">
                INGRESAR
            </button>
        </form>

        <p class="auth__footnote">
            Demo: <code>admin@vitralia.co</code> · <code>vidrio123</code>
        </p>
    </section>
</main>

</body>
</html>
