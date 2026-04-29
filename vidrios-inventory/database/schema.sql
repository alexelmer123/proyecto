-- ============================================================================
-- Vitralia · vidrios_inventory
-- MySQL 8 — InnoDB · utf8mb4
-- Schema completo: incluye todas las tablas y datos semilla.
-- Ejecutar en una base de datos nueva (DROP DATABASE IF EXISTS vidrios_inventory
-- antes de correr este script si se desea empezar de cero).
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `vidrios_inventory`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `vidrios_inventory`;
SET NAMES utf8mb4;

-- ============================================================================
-- TABLAS BASE
-- ============================================================================

-- ----------------------------------------------------------------------------
-- usuarios
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    `nombre`        VARCHAR(120)  NOT NULL,
    `email`         VARCHAR(160)  NOT NULL UNIQUE,
    `password`      VARCHAR(255)  NOT NULL,
    `rol`           ENUM('admin','operador') NOT NULL DEFAULT 'operador',
    `activo`        TINYINT(1)    NOT NULL DEFAULT 1,
    `ultimo_acceso` DATETIME      NULL,
    `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- categorias
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `categorias` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre`      VARCHAR(120) NOT NULL UNIQUE,
    `descripcion` VARCHAR(255) NULL,
    `estado`      TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- proveedores  (las columnas de geografía se agregan más abajo con ALTER)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `proveedores` (
    `id`         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre`     VARCHAR(160) NOT NULL,
    `contacto`   VARCHAR(120) NULL,
    `telefono`   VARCHAR(40)  NULL,
    `email`      VARCHAR(160) NULL,
    `direccion`  VARCHAR(255) NULL,
    `estado`     TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at` DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- productos
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `productos` (
    `id`            INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    `codigo`        VARCHAR(40)    NOT NULL UNIQUE,
    `nombre`        VARCHAR(160)   NOT NULL,
    `descripcion`   TEXT           NULL,
    `categoria_id`  INT UNSIGNED   NULL,
    `proveedor_id`  INT UNSIGNED   NULL,
    `unidad`        VARCHAR(20)    NOT NULL DEFAULT 'm²',
    `ancho`         DECIMAL(10,2)  NULL,
    `alto`          DECIMAL(10,2)  NULL,
    `grosor`        DECIMAL(10,2)  NULL,
    `precio_compra` DECIMAL(12,2)  NOT NULL DEFAULT 0,
    `precio_venta`  DECIMAL(12,2)  NOT NULL DEFAULT 0,
    `stock_actual`  INT            NOT NULL DEFAULT 0,
    `stock_minimo`  INT            NOT NULL DEFAULT 1,
    `imagen`        VARCHAR(255)   NULL,
    `estado`        TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_productos_codigo`    (`codigo`),
    INDEX `idx_productos_categoria` (`categoria_id`),
    INDEX `idx_productos_estado`    (`estado`),
    CONSTRAINT `fk_productos_categoria` FOREIGN KEY (`categoria_id`)
        REFERENCES `categorias`(`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_productos_proveedor` FOREIGN KEY (`proveedor_id`)
        REFERENCES `proveedores`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- movimientos
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `movimientos` (
    `id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `producto_id`    INT UNSIGNED    NOT NULL,
    `tipo`           ENUM('entrada','salida','ajuste') NOT NULL,
    `cantidad`       INT             NOT NULL,
    `stock_anterior` INT             NOT NULL,
    `stock_nuevo`    INT             NOT NULL,
    `usuario_id`     INT UNSIGNED    NULL,
    `proveedor_id`   INT UNSIGNED    NULL,
    `observacion`    VARCHAR(500)    NULL,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_movs_producto` (`producto_id`),
    INDEX `idx_movs_tipo`     (`tipo`),
    INDEX `idx_movs_created`  (`created_at`),
    CONSTRAINT `fk_movs_producto`  FOREIGN KEY (`producto_id`)
        REFERENCES `productos`(`id`)   ON DELETE CASCADE  ON UPDATE CASCADE,
    CONSTRAINT `fk_movs_usuario`   FOREIGN KEY (`usuario_id`)
        REFERENCES `usuarios`(`id`)    ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_movs_proveedor` FOREIGN KEY (`proveedor_id`)
        REFERENCES `proveedores`(`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB;

-- ============================================================================
-- ROLES Y PERMISOS (RBAC)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `roles` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre`      VARCHAR(50)  NOT NULL UNIQUE,
    `descripcion` VARCHAR(255) NULL,
    `activo`      TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `permisos` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `codigo`      VARCHAR(80)  NOT NULL UNIQUE,
    `nombre`      VARCHAR(120) NOT NULL,
    `descripcion` VARCHAR(255) NULL,
    `modulo`      VARCHAR(50)  NOT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_modulo` (`modulo`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `roles_permisos` (
    `rol_id`     INT UNSIGNED NOT NULL,
    `permiso_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`rol_id`, `permiso_id`),
    CONSTRAINT `fk_rp_rol`     FOREIGN KEY (`rol_id`)     REFERENCES `roles`(`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permisos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================================
-- AUDITORIA
-- ============================================================================

CREATE TABLE IF NOT EXISTS `auditoria` (
    `id`            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `usuario_id`    INT UNSIGNED    NULL,
    `usuario_email` VARCHAR(160)    NULL,
    `accion`        VARCHAR(50)     NOT NULL,
    `entidad`       VARCHAR(50)     NOT NULL,
    `entidad_id`    VARCHAR(50)     NULL,
    `descripcion`   VARCHAR(500)    NULL,
    `ip`            VARCHAR(45)     NULL,
    `user_agent`    VARCHAR(255)    NULL,
    `created_at`    DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_aud_usuario` (`usuario_id`),
    INDEX `idx_aud_entidad` (`entidad`, `entidad_id`),
    INDEX `idx_aud_fecha`   (`created_at`),
    CONSTRAINT `fk_aud_usuario` FOREIGN KEY (`usuario_id`)
        REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================================
-- GEOGRAFIA JERARQUICA
-- ============================================================================

CREATE TABLE IF NOT EXISTS `paises` (
    `id`     INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre` VARCHAR(80) NOT NULL UNIQUE,
    `codigo` VARCHAR(3)  NULL UNIQUE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `departamentos` (
    `id`      INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `pais_id` INT UNSIGNED NOT NULL,
    `nombre`  VARCHAR(120) NOT NULL,
    INDEX `idx_dep_pais` (`pais_id`),
    UNIQUE KEY `uniq_dep` (`pais_id`, `nombre`),
    CONSTRAINT `fk_dep_pais` FOREIGN KEY (`pais_id`)
        REFERENCES `paises`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `provincias` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `departamento_id` INT UNSIGNED NOT NULL,
    `nombre`          VARCHAR(120) NOT NULL,
    INDEX `idx_prov_dep` (`departamento_id`),
    UNIQUE KEY `uniq_prov` (`departamento_id`, `nombre`),
    CONSTRAINT `fk_prov_dep` FOREIGN KEY (`departamento_id`)
        REFERENCES `departamentos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `distritos` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `provincia_id` INT UNSIGNED NOT NULL,
    `nombre`       VARCHAR(120) NOT NULL,
    INDEX `idx_dist_prov` (`provincia_id`),
    UNIQUE KEY `uniq_dist` (`provincia_id`, `nombre`),
    CONSTRAINT `fk_dist_prov` FOREIGN KEY (`provincia_id`)
        REFERENCES `provincias`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `ciudades` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `distrito_id` INT UNSIGNED NOT NULL,
    `nombre`      VARCHAR(120) NOT NULL,
    INDEX `idx_ciu_dist` (`distrito_id`),
    UNIQUE KEY `uniq_ciu` (`distrito_id`, `nombre`),
    CONSTRAINT `fk_ciu_dist` FOREIGN KEY (`distrito_id`)
        REFERENCES `distritos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- Agregar columnas de geografía y descripción a proveedores
-- ----------------------------------------------------------------------------
ALTER TABLE `proveedores`
    ADD COLUMN `descripcion_productos` TEXT         NULL,
    ADD COLUMN `pais_id`               INT UNSIGNED NULL,
    ADD COLUMN `departamento_id`       INT UNSIGNED NULL,
    ADD COLUMN `provincia_id`          INT UNSIGNED NULL,
    ADD COLUMN `distrito_id`           INT UNSIGNED NULL,
    ADD COLUMN `ciudad_id`             INT UNSIGNED NULL;

-- FKs de geografía en proveedores (solo se agregan si aún no existen)
SET @existe = (SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS
               WHERE CONSTRAINT_SCHEMA = 'vidrios_inventory'
                 AND TABLE_NAME = 'proveedores'
                 AND CONSTRAINT_NAME = 'fk_prov_pais');
SET @sql = IF(@existe = 0,
    'ALTER TABLE `proveedores`
        ADD CONSTRAINT `fk_prov_pais`         FOREIGN KEY (`pais_id`)         REFERENCES `paises`(`id`)         ON DELETE SET NULL,
        ADD CONSTRAINT `fk_prov_departamento` FOREIGN KEY (`departamento_id`) REFERENCES `departamentos`(`id`) ON DELETE SET NULL,
        ADD CONSTRAINT `fk_prov_provincia`    FOREIGN KEY (`provincia_id`)    REFERENCES `provincias`(`id`)    ON DELETE SET NULL,
        ADD CONSTRAINT `fk_prov_distrito`     FOREIGN KEY (`distrito_id`)     REFERENCES `distritos`(`id`)     ON DELETE SET NULL,
        ADD CONSTRAINT `fk_prov_ciudad`       FOREIGN KEY (`ciudad_id`)       REFERENCES `ciudades`(`id`)      ON DELETE SET NULL',
    'SELECT 1');
PREPARE s FROM @sql; EXECUTE s; DEALLOCATE PREPARE s;

-- ============================================================================
-- PEDIDOS
-- ============================================================================

CREATE TABLE IF NOT EXISTS `pedidos` (
    `id`            INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    `numero`        VARCHAR(40)    NOT NULL UNIQUE,
    `proveedor_id`  INT UNSIGNED   NULL,
    `usuario_id`    INT UNSIGNED   NULL,
    `fecha_pedido`  DATE           NOT NULL,
    `fecha_entrega` DATE           NULL,
    `total`         DECIMAL(12,2)  NOT NULL DEFAULT 0,
    `pagado`        DECIMAL(12,2)  NOT NULL DEFAULT 0,
    `estado`        ENUM('pendiente','pagado','deuda') NOT NULL DEFAULT 'pendiente',
    `observacion`   VARCHAR(500)   NULL,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_ped_estado`    (`estado`),
    INDEX `idx_ped_proveedor` (`proveedor_id`),
    INDEX `idx_ped_fecha`     (`fecha_pedido`),
    CONSTRAINT `fk_ped_proveedor` FOREIGN KEY (`proveedor_id`)
        REFERENCES `proveedores`(`id`) ON DELETE SET NULL,
    CONSTRAINT `fk_ped_usuario`   FOREIGN KEY (`usuario_id`)
        REFERENCES `usuarios`(`id`)   ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================================================
-- DATOS SEMILLA
-- ============================================================================

-- El usuario admin se crea con database/install.php (hash bcrypt real).
-- No insertar aquí; ejecutar: php database/install.php

-- Categorías base
INSERT IGNORE INTO `categorias` (`nombre`, `descripcion`) VALUES
    ('Vidrio templado',         'Cristal tratado térmicamente, alta resistencia.'),
    ('Vidrio laminado',         'Capas unidas con PVB, seguridad y aislamiento acústico.'),
    ('Espejos',                 'Espejos planos y biselados.'),
    ('Cristal decorativo',      'Vidrios serigrafiados, ácidos y de color.'),
    ('Insumos y herrajes',      'Selladores, perfiles, ventosas y herramientas.'),
    ('Venta de productos',      'Productos terminados disponibles para venta directa.'),
    ('Vidrios',                 'Vidrios crudos, templados, laminados y de seguridad.'),
    ('Silicona',                'Sellantes, siliconas estructurales y adhesivos.'),
    ('Instalaciones y armados', 'Servicios de instalación y armado en obra.'),
    ('Venta de cortes',         'Venta de cortes a medida y retales.');

-- Proveedores
INSERT IGNORE INTO `proveedores` (`nombre`, `contacto`, `telefono`, `email`, `direccion`) VALUES
    ('Vidrios Andinos S.A.',  'Carolina Méndez', '+57 601 555 0101', 'ventas@andinos.co',   'Bogotá, Colombia'),
    ('Cristalería del Sur',   'Iván Rojas',      '+57 322 712 0099', 'iv.rojas@cdsur.co',   'Cali, Colombia'),
    ('Templex Internacional', 'Mariana Vega',    '+57 604 444 8210', 'pedidos@templex.com', 'Medellín, Colombia');

-- Productos
INSERT IGNORE INTO `productos`
    (`codigo`, `nombre`, `descripcion`, `categoria_id`, `proveedor_id`,
     `unidad`, `ancho`, `alto`, `grosor`, `precio_compra`, `precio_venta`,
     `stock_actual`, `stock_minimo`)
VALUES
    ('VID-00001', 'Vidrio templado claro 10mm',  'Lámina estándar 1.83 × 2.44 m',        1, 1, 'lámina', 1830, 2440, 10, 220000, 380000, 18, 5),
    ('VID-00002', 'Vidrio laminado 6+6 mm',      'Doble capa con PVB transparente',       2, 1, 'lámina', 1830, 2440, 12, 280000, 460000,  3, 4),
    ('VID-00003', 'Espejo plata 4mm',            'Lámina 2.40 × 1.50 m, canto pulido',   3, 2, 'lámina', 2400, 1500,  4, 130000, 230000, 22, 6),
    ('VID-00004', 'Cristal serigrafiado azul',   'Vidrio templado con tinta azul',        4, 3, 'lámina', 1830, 2440,  8, 260000, 420000,  5, 6),
    ('VID-00005', 'Sellador de silicona neutra', 'Cartucho 300 ml, color claro',          5, 1, 'u',      NULL, NULL, NULL, 18000,  32000, 60, 12),
    ('VID-00006', 'Ventosa doble 8"',            'Para manipulación de láminas pesadas',  5, 2, 'u',      NULL, NULL, NULL, 95000, 160000,  4,  3);

-- Movimientos iniciales (usuario_id = NULL porque el admin aún no existe)
INSERT IGNORE INTO `movimientos`
    (`producto_id`, `tipo`, `cantidad`, `stock_anterior`, `stock_nuevo`,
     `usuario_id`, `proveedor_id`, `observacion`)
VALUES
    (1, 'entrada', 18, 0, 18, NULL, 1, 'Carga inicial de inventario'),
    (2, 'entrada',  3, 0,  3, NULL, 1, 'Carga inicial — pedido parcial'),
    (3, 'entrada', 22, 0, 22, NULL, 2, 'Carga inicial'),
    (4, 'entrada',  5, 0,  5, NULL, 3, 'Carga inicial'),
    (5, 'entrada', 60, 0, 60, NULL, 1, 'Compra silicona x60'),
    (6, 'entrada',  4, 0,  4, NULL, 2, 'Carga inicial — herramienta');

-- Roles
INSERT IGNORE INTO `roles` (`nombre`, `descripcion`) VALUES
    ('admin',    'Acceso total al sistema; gestiona catálogos, movimientos y auditoría.'),
    ('operador', 'Registra movimientos y consulta inventario; sin gestión de catálogos.');

-- Permisos
INSERT IGNORE INTO `permisos` (`codigo`, `nombre`, `modulo`) VALUES
    ('producto.ver',        'Ver productos',          'producto'),
    ('producto.crear',      'Crear productos',        'producto'),
    ('producto.editar',     'Editar productos',       'producto'),
    ('producto.eliminar',   'Archivar productos',     'producto'),
    ('producto.ajustar',    'Ajustar stock',          'producto'),
    ('categoria.ver',       'Ver categorías',         'categoria'),
    ('categoria.crear',     'Crear categorías',       'categoria'),
    ('categoria.editar',    'Editar categorías',      'categoria'),
    ('categoria.eliminar',  'Archivar categorías',    'categoria'),
    ('proveedor.ver',       'Ver proveedores',        'proveedor'),
    ('proveedor.crear',     'Crear proveedores',      'proveedor'),
    ('proveedor.editar',    'Editar proveedores',     'proveedor'),
    ('proveedor.eliminar',  'Archivar proveedores',   'proveedor'),
    ('movimiento.ver',      'Ver movimientos',        'movimiento'),
    ('movimiento.entrada',  'Registrar entradas',     'movimiento'),
    ('movimiento.salida',   'Registrar salidas',      'movimiento'),
    ('reporte.ver',         'Ver reportes',           'reporte'),
    ('reporte.ventas',      'Reporte ventas',         'reporte'),
    ('reporte.consolidado_proveedor', 'Reporte consolidado de proveedores', 'reporte'),
    ('auditoria.ver',       'Ver bitácora',           'auditoria'),
    ('rol.ver',             'Ver roles y permisos',   'rol'),
    ('pedido.ver',          'Ver pedidos',            'pedido'),
    ('pedido.crear',        'Crear pedidos',          'pedido'),
    ('pedido.editar',       'Editar pedidos',         'pedido'),
    ('pedido.estado',       'Cambiar estado pedido',  'pedido');

-- Asignar todos los permisos al rol admin
INSERT IGNORE INTO `roles_permisos` (`rol_id`, `permiso_id`)
SELECT r.id, p.id FROM `roles` r CROSS JOIN `permisos` p
WHERE r.nombre = 'admin';

-- Asignar permisos limitados al rol operador
INSERT IGNORE INTO `roles_permisos` (`rol_id`, `permiso_id`)
SELECT r.id, p.id FROM `roles` r JOIN `permisos` p
  ON p.codigo IN (
    'producto.ver', 'categoria.ver', 'proveedor.ver',
    'movimiento.ver', 'movimiento.entrada', 'movimiento.salida',
    'reporte.ver', 'reporte.ventas', 'reporte.consolidado_proveedor',
    'pedido.ver'
  )
WHERE r.nombre = 'operador';

-- Geografía: países
INSERT IGNORE INTO `paises` (`nombre`, `codigo`) VALUES
    ('Colombia', 'COL'),
    ('Perú',     'PER'),
    ('México',   'MEX');

-- Departamentos
INSERT IGNORE INTO `departamentos` (`pais_id`, `nombre`)
SELECT id, 'Cundinamarca'    FROM paises WHERE nombre = 'Colombia';
INSERT IGNORE INTO `departamentos` (`pais_id`, `nombre`)
SELECT id, 'Antioquia'       FROM paises WHERE nombre = 'Colombia';
INSERT IGNORE INTO `departamentos` (`pais_id`, `nombre`)
SELECT id, 'Valle del Cauca' FROM paises WHERE nombre = 'Colombia';
INSERT IGNORE INTO `departamentos` (`pais_id`, `nombre`)
SELECT id, 'Lima'            FROM paises WHERE nombre = 'Perú';
INSERT IGNORE INTO `departamentos` (`pais_id`, `nombre`)
SELECT id, 'Arequipa'        FROM paises WHERE nombre = 'Perú';
INSERT IGNORE INTO `departamentos` (`pais_id`, `nombre`)
SELECT id, 'Ciudad de México' FROM paises WHERE nombre = 'México';

-- Provincias
INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT d.id, 'Bogotá D.C.' FROM departamentos d JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Colombia' AND d.nombre = 'Cundinamarca';
INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT d.id, 'Valle de Aburrá' FROM departamentos d JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Colombia' AND d.nombre = 'Antioquia';
INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT d.id, 'Sur del Valle' FROM departamentos d JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Colombia' AND d.nombre = 'Valle del Cauca';
INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT d.id, 'Lima' FROM departamentos d JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Perú' AND d.nombre = 'Lima';
INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT d.id, 'Arequipa' FROM departamentos d JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Perú' AND d.nombre = 'Arequipa';
INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT d.id, 'CDMX' FROM departamentos d JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'México' AND d.nombre = 'Ciudad de México';

-- Distritos
INSERT IGNORE INTO `distritos` (`provincia_id`, `nombre`)
SELECT pr.id, 'Bogotá'    FROM provincias pr WHERE pr.nombre = 'Bogotá D.C.';
INSERT IGNORE INTO `distritos` (`provincia_id`, `nombre`)
SELECT pr.id, 'Medellín'  FROM provincias pr WHERE pr.nombre = 'Valle de Aburrá';
INSERT IGNORE INTO `distritos` (`provincia_id`, `nombre`)
SELECT pr.id, 'Cali'      FROM provincias pr WHERE pr.nombre = 'Sur del Valle';
INSERT IGNORE INTO `distritos` (`provincia_id`, `nombre`)
SELECT pr.id, 'Miraflores' FROM provincias pr WHERE pr.nombre = 'Lima';
INSERT IGNORE INTO `distritos` (`provincia_id`, `nombre`)
SELECT pr.id, 'San Isidro' FROM provincias pr WHERE pr.nombre = 'Lima';
INSERT IGNORE INTO `distritos` (`provincia_id`, `nombre`)
SELECT pr.id, 'Cercado de Arequipa' FROM provincias pr WHERE pr.nombre = 'Arequipa';
INSERT IGNORE INTO `distritos` (`provincia_id`, `nombre`)
SELECT pr.id, 'Cuauhtémoc' FROM provincias pr WHERE pr.nombre = 'CDMX';

-- Ciudades
INSERT IGNORE INTO `ciudades` (`distrito_id`, `nombre`)
SELECT d.id, 'Bogotá'           FROM distritos d WHERE d.nombre = 'Bogotá';
INSERT IGNORE INTO `ciudades` (`distrito_id`, `nombre`)
SELECT d.id, 'Medellín'         FROM distritos d WHERE d.nombre = 'Medellín';
INSERT IGNORE INTO `ciudades` (`distrito_id`, `nombre`)
SELECT d.id, 'Cali'             FROM distritos d WHERE d.nombre = 'Cali';
INSERT IGNORE INTO `ciudades` (`distrito_id`, `nombre`)
SELECT d.id, 'Miraflores'       FROM distritos d WHERE d.nombre = 'Miraflores';
INSERT IGNORE INTO `ciudades` (`distrito_id`, `nombre`)
SELECT d.id, 'San Isidro'       FROM distritos d WHERE d.nombre = 'San Isidro';
INSERT IGNORE INTO `ciudades` (`distrito_id`, `nombre`)
SELECT d.id, 'Arequipa'         FROM distritos d WHERE d.nombre = 'Cercado de Arequipa';
INSERT IGNORE INTO `ciudades` (`distrito_id`, `nombre`)
SELECT d.id, 'Ciudad de México' FROM distritos d WHERE d.nombre = 'Cuauhtémoc';
