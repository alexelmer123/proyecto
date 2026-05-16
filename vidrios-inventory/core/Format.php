<?php
declare(strict_types=1);

/**
 * Helpers globales de formato numérico.
 * Usados desde las vistas y los controladores para evitar duplicación.
 */

/**
 * Formatea cantidades de stock/movimiento:
 *   18    → "18"
 *   1.5   → "1.50"
 *   2.00  → "2"
 *   "3.5" → "3.50"
 */
function fmt_cantidad(float|int|string|null $n): string
{
    if ($n === null || $n === '') return '0';
    $num = (float) $n;
    return fmod($num, 1.0) === 0.0
        ? (string) (int) $num
        : number_format($num, 2, '.', '');
}
