-- ============================================================================
-- 002 · Categorías nuevas, geografía jerárquica, descripción de proveedor,
--       y tabla de pedidos (estados: pendiente, pagado, deuda).
-- Idempotente.
-- ============================================================================

USE `vidrios_inventory`;
SET NAMES utf8mb4;

-- ── Categorías nuevas ──────────────────────────────────────────────────────
INSERT IGNORE INTO `categorias` (`nombre`, `descripcion`, `estado`, `created_at`) VALUES
  ('Venta de productos',     'Productos terminados disponibles para venta directa.',  1, NOW()),
  ('Vidrios',                'Vidrios crudos, templados, laminados y de seguridad.',  1, NOW()),
  ('Silicona',               'Sellantes, siliconas estructurales y adhesivos.',       1, NOW()),
  ('Instalaciones y armados','Servicios de instalación y armado en obra.',            1, NOW()),
  ('Venta de cortes',        'Venta de cortes a medida y retales.',                   1, NOW());

-- ── Geografía: país → departamento → provincia → distrito → ciudad ────────
CREATE TABLE IF NOT EXISTS `paises` (
    `id`     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(80) NOT NULL UNIQUE,
    `codigo` VARCHAR(3)  NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `departamentos` (
    `id`       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `pais_id`  INT UNSIGNED NOT NULL,
    `nombre`   VARCHAR(120) NOT NULL,
    INDEX `idx_dep_pais` (`pais_id`),
    UNIQUE KEY `uniq_dep` (`pais_id`, `nombre`),
    CONSTRAINT `fk_dep_pais` FOREIGN KEY (`pais_id`) REFERENCES `paises`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `provincias` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `departamento_id` INT UNSIGNED NOT NULL,
    `nombre`          VARCHAR(120) NOT NULL,
    INDEX `idx_prov_dep` (`departamento_id`),
    UNIQUE KEY `uniq_prov` (`departamento_id`, `nombre`),
    CONSTRAINT `fk_prov_dep` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `distritos` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `provincia_id` INT UNSIGNED NOT NULL,
    `nombre`       VARCHAR(120) NOT NULL,
    INDEX `idx_dist_prov` (`provincia_id`),
    UNIQUE KEY `uniq_dist` (`provincia_id`, `nombre`),
    CONSTRAINT `fk_dist_prov` FOREIGN KEY (`provincia_id`) REFERENCES `provincias`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `ciudades` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `distrito_id` INT UNSIGNED NOT NULL,
    `nombre`      VARCHAR(120) NOT NULL,
    INDEX `idx_ciu_dist` (`distrito_id`),
    UNIQUE KEY `uniq_ciu` (`distrito_id`, `nombre`),
    CONSTRAINT `fk_ciu_dist` FOREIGN KEY (`distrito_id`) REFERENCES `distritos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ── Seed de geografía (Colombia y Perú, mínimo viable) ─────────────────────
INSERT IGNORE INTO `paises` (`nombre`, `codigo`) VALUES
  ('Colombia','COL'),
  ('Perú',    'PER'),
  ('México',  'MEX');

-- Colombia
INSERT IGNORE INTO `departamentos` (`pais_id`, `nombre`)
SELECT id, 'Cundinamarca' FROM paises WHERE nombre = 'Colombia';
INSERT IGNORE INTO `departamentos` (`pais_id`, `nombre`)
SELECT id, 'Antioquia'    FROM paises WHERE nombre = 'Colombia';
INSERT IGNORE INTO `departamentos` (`pais_id`, `nombre`)
SELECT id, 'Valle del Cauca' FROM paises WHERE nombre = 'Colombia';

INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT d.id, 'Sabana Centro' FROM departamentos d JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Colombia' AND d.nombre = 'Cundinamarca';
INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT d.id, 'Bogotá D.C.' FROM departamentos d JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Colombia' AND d.nombre = 'Cundinamarca';
INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT d.id, 'Valle de Aburrá' FROM departamentos d JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Colombia' AND d.nombre = 'Antioquia';
INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT d.id, 'Sur del Valle' FROM departamentos d JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Colombia' AND d.nombre = 'Valle del Cauca';

INSERT IGNORE INTO `distritos` (`provincia_id`, `nombre`)
SELECT pr.id, 'Bogotá' FROM provincias pr WHERE pr.nombre = 'Bogotá D.C.';
INSERT IGNORE INTO `distritos` (`provincia_id`, `nombre`)
SELECT pr.id, 'Chía'   FROM provincias pr WHERE pr.nombre = 'Sabana Centro';
INSERT IGNORE INTO `distritos` (`provincia_id`, `nombre`)
SELECT pr.id, 'Medellín' FROM provincias pr WHERE pr.nombre = 'Valle de Aburrá';
INSERT IGNORE INTO `distritos` (`provincia_id`, `nombre`)
SELECT pr.id, 'Cali'   FROM provincias pr WHERE pr.nombre = 'Sur del Valle';

INSERT IGNORE INTO `ciudades` (`distrito_id`, `nombre`)
SELECT d.id, 'Bogotá'   FROM distritos d WHERE d.nombre = 'Bogotá';
INSERT IGNORE INTO `ciudades` (`distrito_id`, `nombre`)
SELECT d.id, 'Chía'     FROM distritos d WHERE d.nombre = 'Chía';
INSERT IGNORE INTO `ciudades` (`distrito_id`, `nombre`)
SELECT d.id, 'Medellín' FROM distritos d WHERE d.nombre = 'Medellín';
INSERT IGNORE INTO `ciudades` (`distrito_id`, `nombre`)
SELECT d.id, 'Cali'     FROM distritos d WHERE d.nombre = 'Cali';

-- Perú (estructura nativa de 5 niveles)
INSERT IGNORE INTO `departamentos` (`pais_id`, `nombre`)
SELECT id, 'Lima'     FROM paises WHERE nombre = 'Perú';
INSERT IGNORE INTO `departamentos` (`pais_id`, `nombre`)
SELECT id, 'Arequipa' FROM paises WHERE nombre = 'Perú';

INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT d.id, 'Lima'      FROM departamentos d JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Perú' AND d.nombre = 'Lima';
INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT d.id, 'Arequipa'  FROM departamentos d JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Perú' AND d.nombre = 'Arequipa';

INSERT IGNORE INTO `distritos` (`provincia_id`, `nombre`)
SELECT pr.id, 'Miraflores' FROM provincias pr JOIN departamentos d ON d.id = pr.departamento_id
JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Perú' AND pr.nombre = 'Lima';
INSERT IGNORE INTO `distritos` (`provincia_id`, `nombre`)
SELECT pr.id, 'San Isidro' FROM provincias pr JOIN departamentos d ON d.id = pr.departamento_id
JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Perú' AND pr.nombre = 'Lima';
INSERT IGNORE INTO `distritos` (`provincia_id`, `nombre`)
SELECT pr.id, 'Cercado de Arequipa' FROM provincias pr JOIN departamentos d ON d.id = pr.departamento_id
JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Perú' AND pr.nombre = 'Arequipa';

INSERT IGNORE INTO `ciudades` (`distrito_id`, `nombre`)
SELECT d.id, 'Miraflores' FROM distritos d WHERE d.nombre = 'Miraflores';
INSERT IGNORE INTO `ciudades` (`distrito_id`, `nombre`)
SELECT d.id, 'San Isidro' FROM distritos d WHERE d.nombre = 'San Isidro';
INSERT IGNORE INTO `ciudades` (`distrito_id`, `nombre`)
SELECT d.id, 'Arequipa'   FROM distritos d WHERE d.nombre = 'Cercado de Arequipa';

-- México (mínimo)
INSERT IGNORE INTO `departamentos` (`pais_id`, `nombre`)
SELECT id, 'Ciudad de México' FROM paises WHERE nombre = 'México';
INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT d.id, 'CDMX' FROM departamentos d JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'México' AND d.nombre = 'Ciudad de México';
INSERT IGNORE INTO `distritos` (`provincia_id`, `nombre`)
SELECT pr.id, 'Cuauhtémoc' FROM provincias pr WHERE pr.nombre = 'CDMX';
INSERT IGNORE INTO `ciudades` (`distrito_id`, `nombre`)
SELECT d.id, 'Ciudad de México' FROM distritos d WHERE d.nombre = 'Cuauhtémoc';

-- ── ALTER proveedores: descripción + ubicación ─────────────────────────────
-- Las columnas se agregan solo si no existen (consultando information_schema).
SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = 'vidrios_inventory' AND TABLE_NAME = 'proveedores'
               AND COLUMN_NAME = 'descripcion_productos');
SET @sql := IF(@col = 0,
    'ALTER TABLE `proveedores` ADD COLUMN `descripcion_productos` TEXT NULL AFTER `direccion`',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

SET @col := (SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = 'vidrios_inventory' AND TABLE_NAME = 'proveedores'
               AND COLUMN_NAME = 'pais_id');
SET @sql := IF(@col = 0,
    'ALTER TABLE `proveedores`
        ADD COLUMN `pais_id`          INT UNSIGNED NULL,
        ADD COLUMN `departamento_id`  INT UNSIGNED NULL,
        ADD COLUMN `provincia_id`     INT UNSIGNED NULL,
        ADD COLUMN `distrito_id`      INT UNSIGNED NULL,
        ADD COLUMN `ciudad_id`        INT UNSIGNED NULL,
        ADD CONSTRAINT `fk_prov_pais`         FOREIGN KEY (`pais_id`)         REFERENCES `paises`(`id`)         ON DELETE SET NULL,
        ADD CONSTRAINT `fk_prov_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos`(`id`) ON DELETE SET NULL,
        ADD CONSTRAINT `fk_prov_provincia`    FOREIGN KEY (`provincia_id`)    REFERENCES `provincias`(`id`)    ON DELETE SET NULL,
        ADD CONSTRAINT `fk_prov_distrito`     FOREIGN KEY (`distrito_id`)     REFERENCES `distritos`(`id`)     ON DELETE SET NULL,
        ADD CONSTRAINT `fk_prov_ciudad`       FOREIGN KEY (`ciudad_id`)       REFERENCES `ciudades`(`id`)      ON DELETE SET NULL',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ── Pedidos a proveedores ──────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `pedidos` (
    `id`             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `numero`         VARCHAR(40)  NOT NULL UNIQUE,
    `proveedor_id`   INT UNSIGNED NULL,
    `usuario_id`     INT UNSIGNED NULL,
    `fecha_pedido`   DATE         NOT NULL,
    `fecha_entrega`  DATE         NULL,
    `total`          DECIMAL(12,2) NOT NULL DEFAULT 0,
    `pagado`         DECIMAL(12,2) NOT NULL DEFAULT 0,
    `estado`         ENUM('pendiente','pagado','deuda') NOT NULL DEFAULT 'pendiente',
    `observacion`    VARCHAR(500) NULL,
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ped_estado`    (`estado`),
    INDEX `idx_ped_proveedor` (`proveedor_id`),
    INDEX `idx_ped_fecha`     (`fecha_pedido`),
    CONSTRAINT `fk_ped_proveedor` FOREIGN KEY (`proveedor_id`) REFERENCES `proveedores`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ped_usuario`   FOREIGN KEY (`usuario_id`)   REFERENCES `usuarios`(`id`)    ON DELETE SET NULL
) ENGINE=InnoDB;

-- ── Permisos nuevos para los módulos agregados ────────────────────────────
INSERT IGNORE INTO `permisos` (`codigo`, `nombre`, `modulo`) VALUES
  ('pedido.ver',      'Ver pedidos',         'pedido'),
  ('pedido.crear',    'Crear pedidos',       'pedido'),
  ('pedido.editar',   'Editar pedidos',      'pedido'),
  ('pedido.estado',   'Cambiar estado',      'pedido'),
  ('reporte.ventas',  'Reporte ventas',      'reporte'),
  ('reporte.consolidado_proveedor', 'Reporte consolidado de proveedores', 'reporte');

INSERT IGNORE INTO `roles_permisos` (`rol_id`, `permiso_id`)
SELECT r.id, p.id FROM `roles` r CROSS JOIN `permisos` p
WHERE r.nombre = 'admin'
  AND p.codigo IN ('pedido.ver','pedido.crear','pedido.editar','pedido.estado',
                   'reporte.ventas','reporte.consolidado_proveedor');

INSERT IGNORE INTO `roles_permisos` (`rol_id`, `permiso_id`)
SELECT r.id, p.id FROM `roles` r JOIN `permisos` p
  ON p.codigo IN ('pedido.ver','reporte.ventas','reporte.consolidado_proveedor')
WHERE r.nombre = 'operador';
