<?php
declare(strict_types=1);

/**
 * Iconos SVG inline · estilo "stroke 1.6, feather-like".
 * Renderizado vía <use href="#icon-<name>"/> contra el sprite del layout.
 *
 * Uso:
 *   echo icon('plus');
 *   echo icon('edit', 16);
 *   echo icon('trash', 18, 'btn__icon');
 */
function icon(string $name, int $size = 18, string $extraClass = ''): string
{
    $class = trim('icon ' . $extraClass);
    return sprintf(
        '<svg class="%s" width="%d" height="%d" aria-hidden="true" focusable="false"><use href="#icon-%s"/></svg>',
        htmlspecialchars($class, ENT_QUOTES, 'UTF-8'),
        $size,
        $size,
        htmlspecialchars($name, ENT_QUOTES, 'UTF-8')
    );
}
