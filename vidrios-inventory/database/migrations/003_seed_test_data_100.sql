-- ============================================================================
-- 003 · 100 filas de prueba por tabla
-- ----------------------------------------------------------------------------
-- Pre-requisitos: schema.sql cargado + migraciones 001 y 002 aplicadas.
-- Carga:  mysql -u root vidrios_inventory < 003_seed_test_data_100.sql
--
-- Idempotente:
--   · Tablas con UNIQUE → INSERT IGNORE (re-run seguro).
--   · Tablas sin UNIQUE (movimientos, auditoria, proveedores) → guardadas
--     con un WHERE NOT EXISTS sobre un marcador conocido para no duplicar.
--
-- Login de los 100 usuarios sintéticos:
--   email:    user001@demo.local … user100@demo.local
--   password: password   (hash bcrypt cost-10 incluido abajo)
-- ============================================================================

USE `vidrios_inventory`;
SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- 1. usuarios (100)  ·  unique: email
-- ----------------------------------------------------------------------------
WITH RECURSIVE n(i) AS (
    SELECT 1 UNION ALL SELECT i + 1 FROM n WHERE i < 100
)
INSERT IGNORE INTO `usuarios`
    (`nombre`, `email`, `password`, `rol`, `activo`, `ultimo_acceso`, `created_at`)
SELECT
    CONCAT('Empleado Demo ', LPAD(i, 3, '0')),
    CONCAT('user', LPAD(i, 3, '0'), '@demo.local'),
    '$2y$10$N9qo8uLOickgx2ZMRZoMyeIjZAgcfl7p92ldGxad68LJZdL17lhWy',
    IF(i MOD 5 = 0, 'admin', 'operador'),
    IF(i MOD 11 = 0, 0, 1),
    DATE_SUB(NOW(),  INTERVAL (i * 3) HOUR),
    DATE_SUB(NOW(),  INTERVAL i DAY)
FROM n;

-- ----------------------------------------------------------------------------
-- 2. roles (100)  ·  unique: nombre
-- ----------------------------------------------------------------------------
WITH RECURSIVE n(i) AS (
    SELECT 1 UNION ALL SELECT i + 1 FROM n WHERE i < 100
)
INSERT IGNORE INTO `roles` (`nombre`, `descripcion`, `activo`)
SELECT
    CONCAT('rol_demo_', LPAD(i, 3, '0')),
    CONCAT('Rol sintético de prueba número ', i),
    IF(i MOD 13 = 0, 0, 1)
FROM n;

-- ----------------------------------------------------------------------------
-- 3. permisos (100)  ·  unique: codigo
-- ----------------------------------------------------------------------------
WITH RECURSIVE n(i) AS (
    SELECT 1 UNION ALL SELECT i + 1 FROM n WHERE i < 100
)
INSERT IGNORE INTO `permisos` (`codigo`, `nombre`, `descripcion`, `modulo`)
SELECT
    CONCAT('demo.permiso.', LPAD(i, 3, '0')),
    CONCAT('Permiso de prueba ', LPAD(i, 3, '0')),
    'Permiso sintético usado para pruebas de RBAC.',
    ELT((i MOD 6) + 1, 'producto', 'categoria', 'proveedor', 'movimiento', 'reporte', 'demo')
FROM n;

-- ----------------------------------------------------------------------------
-- 4. roles_permisos (100)  ·  PK compuesta (rol_id, permiso_id)
--    Asocia rol_demo_NNN ↔ demo.permiso.NNN
-- ----------------------------------------------------------------------------
WITH RECURSIVE n(i) AS (
    SELECT 1 UNION ALL SELECT i + 1 FROM n WHERE i < 100
)
INSERT IGNORE INTO `roles_permisos` (`rol_id`, `permiso_id`)
SELECT r.id, p.id
FROM n
JOIN `roles`    r ON r.nombre = CONCAT('rol_demo_',     LPAD(n.i, 3, '0'))
JOIN `permisos` p ON p.codigo = CONCAT('demo.permiso.', LPAD(n.i, 3, '0'));

-- ----------------------------------------------------------------------------
-- 5. paises (100)  ·  unique: nombre, codigo
-- ----------------------------------------------------------------------------
WITH RECURSIVE n(i) AS (
    SELECT 1 UNION ALL SELECT i + 1 FROM n WHERE i < 100
)
INSERT IGNORE INTO `paises` (`nombre`, `codigo`)
SELECT
    CONCAT('País Demo ', LPAD(i, 3, '0')),
    CONCAT('X', LPAD(HEX(i), 2, '0'))
FROM n;

-- ----------------------------------------------------------------------------
-- 6. departamentos (100)  ·  FK pais_id  ·  unique (pais_id, nombre)
-- ----------------------------------------------------------------------------
WITH RECURSIVE n(i) AS (
    SELECT 1 UNION ALL SELECT i + 1 FROM n WHERE i < 100
)
INSERT IGNORE INTO `departamentos` (`pais_id`, `nombre`)
SELECT pa.id, CONCAT('Departamento Demo ', LPAD(n.i, 3, '0'))
FROM n
JOIN `paises` pa ON pa.nombre = CONCAT('País Demo ', LPAD(n.i, 3, '0'));

-- ----------------------------------------------------------------------------
-- 7. provincias (100)  ·  FK departamento_id  ·  unique (departamento_id, nombre)
-- ----------------------------------------------------------------------------
WITH RECURSIVE n(i) AS (
    SELECT 1 UNION ALL SELECT i + 1 FROM n WHERE i < 100
)
INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT de.id, CONCAT('Provincia Demo ', LPAD(n.i, 3, '0'))
FROM n
JOIN `departamentos` de ON de.nombre = CONCAT('Departamento Demo ', LPAD(n.i, 3, '0'));

-- ----------------------------------------------------------------------------
-- 8. distritos (100)  ·  FK provincia_id  ·  unique (provincia_id, nombre)
-- ----------------------------------------------------------------------------
WITH RECURSIVE n(i) AS (
    SELECT 1 UNION ALL SELECT i + 1 FROM n WHERE i < 100
)
INSERT IGNORE INTO `distritos` (`provincia_id`, `nombre`)
SELECT pr.id, CONCAT('Distrito Demo ', LPAD(n.i, 3, '0'))
FROM n
JOIN `provincias` pr ON pr.nombre = CONCAT('Provincia Demo ', LPAD(n.i, 3, '0'));

-- ----------------------------------------------------------------------------
-- 9. ciudades (100)  ·  FK distrito_id  ·  unique (distrito_id, nombre)
-- ----------------------------------------------------------------------------
WITH RECURSIVE n(i) AS (
    SELECT 1 UNION ALL SELECT i + 1 FROM n WHERE i < 100
)
INSERT IGNORE INTO `ciudades` (`distrito_id`, `nombre`)
SELECT di.id, CONCAT('Ciudad Demo ', LPAD(n.i, 3, '0'))
FROM n
JOIN `distritos` di ON di.nombre = CONCAT('Distrito Demo ', LPAD(n.i, 3, '0'));

-- ----------------------------------------------------------------------------
-- 10. categorias (100)  ·  unique: nombre
-- ----------------------------------------------------------------------------
WITH RECURSIVE n(i) AS (
    SELECT 1 UNION ALL SELECT i + 1 FROM n WHERE i < 100
)
INSERT IGNORE INTO `categorias` (`nombre`, `descripcion`, `estado`)
SELECT
    CONCAT('Categoría Demo ', LPAD(i, 3, '0')),
    CONCAT('Categoría sintética de prueba para listados y filtros (', LPAD(i, 3, '0'), ').'),
    IF(i MOD 17 = 0, 0, 1)
FROM n;

-- ----------------------------------------------------------------------------
-- 11. proveedores (100)  ·  sin UNIQUE → guard por marcador
--     Cada Proveedor Demo NNN se ancla 1-a-1 a su jerarquía geográfica NNN.
-- ----------------------------------------------------------------------------
WITH RECURSIVE n(i) AS (
    SELECT 1 UNION ALL SELECT i + 1 FROM n WHERE i < 100
)
INSERT INTO `proveedores`
    (`nombre`, `contacto`, `telefono`, `email`, `direccion`, `estado`,
     `descripcion_productos`, `pais_id`, `departamento_id`, `provincia_id`,
     `distrito_id`, `ciudad_id`)
SELECT
    CONCAT('Proveedor Demo ', LPAD(n.i, 3, '0')),
    CONCAT('Contacto ', LPAD(n.i, 3, '0')),
    CONCAT('+51 9', LPAD((n.i * 137) MOD 100000000, 8, '0')),
    CONCAT('contacto', LPAD(n.i, 3, '0'), '@proveedor.demo'),
    CONCAT('Av. Demo ', n.i * 7, ' #', n.i, ' — Of. ', LPAD(n.i MOD 50, 2, '0')),
    IF(n.i MOD 19 = 0, 0, 1),
    CONCAT('Provee insumos sintéticos · lote ', LPAD(n.i, 3, '0'), '.'),
    pa.id, de.id, pr.id, di.id, ci.id
FROM n
JOIN `paises`        pa ON pa.nombre = CONCAT('País Demo ',         LPAD(n.i, 3, '0'))
JOIN `departamentos` de ON de.nombre = CONCAT('Departamento Demo ', LPAD(n.i, 3, '0'))
JOIN `provincias`    pr ON pr.nombre = CONCAT('Provincia Demo ',    LPAD(n.i, 3, '0'))
JOIN `distritos`     di ON di.nombre = CONCAT('Distrito Demo ',     LPAD(n.i, 3, '0'))
JOIN `ciudades`      ci ON ci.nombre = CONCAT('Ciudad Demo ',       LPAD(n.i, 3, '0'))
WHERE NOT EXISTS (
    SELECT 1 FROM `proveedores` WHERE `nombre` = 'Proveedor Demo 001'
);

-- ----------------------------------------------------------------------------
-- 12. productos (100)  ·  unique: codigo
-- ----------------------------------------------------------------------------
WITH RECURSIVE n(i) AS (
    SELECT 1 UNION ALL SELECT i + 1 FROM n WHERE i < 100
)
INSERT IGNORE INTO `productos`
    (`codigo`, `nombre`, `descripcion`, `categoria_id`, `proveedor_id`,
     `unidad`, `ancho`, `alto`, `grosor`, `precio_compra`, `precio_venta`,
     `stock_actual`, `stock_minimo`, `estado`)
SELECT
    CONCAT('TEST-', LPAD(n.i, 5, '0')),
    CONCAT('Producto Demo ', LPAD(n.i, 3, '0')),
    CONCAT('Lámina/insumo sintético de prueba número ', LPAD(n.i, 3, '0'), '.'),
    c.id, prov.id,
    ELT((n.i MOD 4) + 1, 'lámina', 'm²', 'u', 'kg'),
    600  + ((n.i * 7)  MOD 1500),
    600  + ((n.i * 11) MOD 2000),
    3    + (n.i MOD 18),
    ROUND(50000  + ((n.i * 3137) MOD 350000), 2),
    ROUND(80000  + ((n.i * 5113) MOD 600000), 2),
    ((n.i * 13) MOD 80) + 1,
    (n.i MOD 8) + 2,
    IF(n.i MOD 23 = 0, 0, 1)
FROM n
JOIN `categorias` c
    ON c.nombre = CONCAT('Categoría Demo ', LPAD(n.i, 3, '0'))
JOIN (SELECT MIN(id) AS id, nombre FROM `proveedores` GROUP BY nombre) prov
    ON prov.nombre = CONCAT('Proveedor Demo ', LPAD(n.i, 3, '0'));

-- ----------------------------------------------------------------------------
-- 13. movimientos (100)  ·  sin UNIQUE → guard por marcador en observacion
-- ----------------------------------------------------------------------------
WITH RECURSIVE n(i) AS (
    SELECT 1 UNION ALL SELECT i + 1 FROM n WHERE i < 100
)
INSERT INTO `movimientos`
    (`producto_id`, `tipo`, `cantidad`, `stock_anterior`, `stock_nuevo`,
     `usuario_id`, `proveedor_id`, `observacion`, `created_at`)
SELECT
    pr.id,
    ELT((n.i MOD 3) + 1, 'entrada', 'salida', 'ajuste'),
    (n.i MOD 25) + 1,
    100,
    CASE ((n.i MOD 3) + 1)
        WHEN 1 THEN 100 + ((n.i MOD 25) + 1)
        WHEN 2 THEN GREATEST(0, 100 - ((n.i MOD 25) + 1))
        ELSE        (n.i MOD 30) + 50
    END,
    u.id,
    prov.id,
    CONCAT('[seed_v1] Movimiento sintético #', LPAD(n.i, 3, '0')),
    DATE_SUB(NOW(), INTERVAL (n.i * 5) HOUR)
FROM n
JOIN `productos` pr
    ON pr.codigo = CONCAT('TEST-', LPAD(n.i, 5, '0'))
JOIN `usuarios` u
    ON u.email = CONCAT('user', LPAD(n.i, 3, '0'), '@demo.local')
LEFT JOIN (SELECT MIN(id) AS id, nombre FROM `proveedores` GROUP BY nombre) prov
    ON prov.nombre = CONCAT('Proveedor Demo ', LPAD(n.i, 3, '0'))
WHERE NOT EXISTS (
    SELECT 1 FROM `movimientos`
    WHERE `observacion` = '[seed_v1] Movimiento sintético #001'
);

-- ----------------------------------------------------------------------------
-- 14. pedidos (100)  ·  unique: numero
--     · estado round-robin: pendiente / pagado / deuda
--     · pagado ∈ [0 .. total) para que la métrica de deuda sea coherente
-- ----------------------------------------------------------------------------
WITH RECURSIVE n(i) AS (
    SELECT 1 UNION ALL SELECT i + 1 FROM n WHERE i < 100
)
INSERT IGNORE INTO `pedidos`
    (`numero`, `proveedor_id`, `usuario_id`, `fecha_pedido`, `fecha_entrega`,
     `total`, `pagado`, `estado`, `observacion`)
SELECT
    CONCAT('PED-DEMO-', LPAD(n.i, 3, '0')),
    prov.id,
    u.id,
    DATE_SUB(CURDATE(), INTERVAL (n.i * 2) DAY),
    IF(n.i MOD 4 = 0, NULL, DATE_SUB(CURDATE(), INTERVAL n.i DAY)),
    ROUND(100000 + ((n.i * 7919) MOD 1500000), 2),
    ROUND(
        CASE (n.i MOD 3)
            WHEN 0 THEN 0
            WHEN 1 THEN 100000 + ((n.i * 7919) MOD 1500000)
            ELSE        (100000 + ((n.i * 7919) MOD 1500000)) * (n.i MOD 100) / 100
        END,
    2),
    ELT((n.i MOD 3) + 1, 'pendiente', 'pagado', 'deuda'),
    CONCAT('Pedido sintético de prueba ', LPAD(n.i, 3, '0'))
FROM n
LEFT JOIN (SELECT MIN(id) AS id, nombre FROM `proveedores` GROUP BY nombre) prov
    ON prov.nombre = CONCAT('Proveedor Demo ', LPAD(n.i, 3, '0'))
LEFT JOIN `usuarios` u
    ON u.email = CONCAT('user', LPAD(n.i, 3, '0'), '@demo.local');

-- ----------------------------------------------------------------------------
-- 15. auditoria (100)  ·  sin UNIQUE → guard por marcador en descripcion
-- ----------------------------------------------------------------------------
WITH RECURSIVE n(i) AS (
    SELECT 1 UNION ALL SELECT i + 1 FROM n WHERE i < 100
)
INSERT INTO `auditoria`
    (`usuario_id`, `usuario_email`, `accion`, `entidad`, `entidad_id`,
     `descripcion`, `ip`, `user_agent`, `created_at`)
SELECT
    u.id, u.email,
    ELT((n.i MOD 5) + 1, 'crear', 'editar', 'eliminar', 'login', 'ajustar'),
    ELT((n.i MOD 6) + 1, 'producto', 'categoria', 'proveedor', 'movimiento', 'pedido', 'auth'),
    CAST(((n.i * 13) MOD 200) + 1 AS CHAR),
    CONCAT('[seed_v1] Acción de prueba número ', LPAD(n.i, 3, '0')),
    CONCAT('192.168.', (n.i MOD 254) + 1, '.', ((n.i * 7) MOD 254) + 1),
    'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0 Safari/537.36',
    DATE_SUB(NOW(), INTERVAL (n.i * 17) MINUTE)
FROM n
JOIN `usuarios` u
    ON u.email = CONCAT('user', LPAD(n.i, 3, '0'), '@demo.local')
WHERE NOT EXISTS (
    SELECT 1 FROM `auditoria`
    WHERE `descripcion` = '[seed_v1] Acción de prueba número 001'
);

-- ============================================================================
-- Verificación rápida (descomenta para ver los conteos):
-- ============================================================================
-- SELECT 'usuarios'       AS tabla, COUNT(*) FROM usuarios       UNION ALL
-- SELECT 'roles',                  COUNT(*) FROM roles           UNION ALL
-- SELECT 'permisos',               COUNT(*) FROM permisos        UNION ALL
-- SELECT 'roles_permisos',         COUNT(*) FROM roles_permisos  UNION ALL
-- SELECT 'paises',                 COUNT(*) FROM paises          UNION ALL
-- SELECT 'departamentos',          COUNT(*) FROM departamentos   UNION ALL
-- SELECT 'provincias',             COUNT(*) FROM provincias      UNION ALL
-- SELECT 'distritos',              COUNT(*) FROM distritos       UNION ALL
-- SELECT 'ciudades',               COUNT(*) FROM ciudades        UNION ALL
-- SELECT 'categorias',             COUNT(*) FROM categorias      UNION ALL
-- SELECT 'proveedores',            COUNT(*) FROM proveedores     UNION ALL
-- SELECT 'productos',              COUNT(*) FROM productos       UNION ALL
-- SELECT 'movimientos',            COUNT(*) FROM movimientos     UNION ALL
-- SELECT 'pedidos',                COUNT(*) FROM pedidos         UNION ALL
-- SELECT 'auditoria',              COUNT(*) FROM auditoria;
