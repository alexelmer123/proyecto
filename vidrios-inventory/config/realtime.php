<?php
/**
 * Configuración del subsistema en tiempo real (WebSocket).
 *
 * Arquitectura:
 *   - El daemon `bin/ws-server.php` abre DOS sockets sobre el mismo event loop:
 *       (a) WS público en REALTIME_WS_HOST:REALTIME_WS_PORT  → al que se conectan
 *           los navegadores de los usuarios para recibir eventos.
 *       (b) HTTP interno en 127.0.0.1:REALTIME_PUSH_PORT     → al que cada
 *           request PHP empuja un evento después de un cambio de stock.
 *
 *   - Los controladores nunca hablan con los navegadores directamente: empujan
 *     el evento al puerto interno y el daemon hace el broadcast.
 *
 *   - Si el daemon está caído, `Realtime::publish()` falla en silencio
 *     (timeout corto) y el request del usuario continúa normal.
 */

// Dirección donde el daemon ESCUCHA conexiones WS. Usa 0.0.0.0 para aceptar
// desde la red local (otros equipos del taller); 127.0.0.1 limita a la misma
// máquina.
define('REALTIME_WS_HOST', '0.0.0.0');
define('REALTIME_WS_PORT', 8080);

// URL pública del WS que el navegador usará. En producción detrás de HTTPS,
// cámbiala a "wss://tu-dominio/ws". En desarrollo localhost basta.
define('REALTIME_WS_URL', 'ws://' . ($_SERVER['SERVER_NAME'] ?? 'localhost') . ':8080');

// Puerto del bridge HTTP interno (sólo localhost). Los controladores hacen
// POST aquí cuando hay un cambio que difundir.
define('REALTIME_PUSH_HOST', '127.0.0.1');
define('REALTIME_PUSH_PORT', 8081);

// Secreto compartido entre los controladores y el daemon. Va en la cabecera
// X-Realtime-Secret de cada push. Cambia este valor antes de producción.
define('REALTIME_PUSH_SECRET', 'cambia-este-secreto-en-produccion');

// Master switch: si está en false, los publish() son no-op (útil cuando el
// daemon está apagado intencionalmente).
define('REALTIME_ENABLED', true);

// Timeout (ms) para el push desde PHP al daemon. Mantenerlo corto para no
// bloquear la respuesta del usuario si el daemon está caído.
define('REALTIME_TIMEOUT_MS', 250);
