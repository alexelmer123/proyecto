-- ============================================================================
-- Vitralia · vidrios_inventory
-- MySQL 8 — InnoDB · utf8mb4
-- ----------------------------------------------------------------------------
-- Schema "tablas sueltas": NO se declaran FOREIGN KEYs.
-- Las columnas *_id siguen existiendo y los índices se conservan para que los
-- INNER/LEFT JOIN de los modelos sigan siendo rápidos, pero la integridad
-- relacional se mantiene desde el código (no desde el motor).
-- ----------------------------------------------------------------------------
-- Pensado para instalación nueva. Ejecutar:
--   DROP DATABASE IF EXISTS vidrios_inventory;   -- si quieres empezar de cero
--   mysql -u root < vidrios-inventory/database/schema.sql
--   php vidrios-inventory/database/install.php   -- crea el admin demo
-- ============================================================================

CREATE DATABASE IF NOT EXISTS `vidrios_inventory`
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE `vidrios_inventory`;
SET NAMES utf8mb4;

-- ============================================================================
-- USUARIOS / SEGURIDAD (RBAC)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `usuarios` (
    `id`            INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    `nombre`        VARCHAR(120)  NOT NULL,
    `email`         VARCHAR(160)  NOT NULL UNIQUE,
    `password`      VARCHAR(255)  NOT NULL,
    `rol`           VARCHAR(50)   NOT NULL DEFAULT 'operador',
    `rol_id`        INT UNSIGNED  NULL,
    `activo`        TINYINT(1)    NOT NULL DEFAULT 1,
    `ultimo_acceso` DATETIME      NULL,
    `created_at`    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_usuarios_rol_id` (`rol_id`)
) ENGINE=InnoDB;

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
    INDEX `idx_rp_permiso` (`permiso_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `usuarios_permisos` (
    `usuario_id` INT UNSIGNED NOT NULL,
    `permiso_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`usuario_id`, `permiso_id`),
    INDEX `idx_up_permiso` (`permiso_id`)
) ENGINE=InnoDB;

-- ============================================================================
-- GEOGRAFIA JERARQUICA (tablas sueltas — sin FKs)
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
    UNIQUE KEY `uniq_dep` (`pais_id`, `nombre`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `provincias` (
    `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `departamento_id` INT UNSIGNED NOT NULL,
    `nombre`          VARCHAR(120) NOT NULL,
    INDEX `idx_prov_dep` (`departamento_id`),
    UNIQUE KEY `uniq_prov` (`departamento_id`, `nombre`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `distritos` (
    `id`           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `provincia_id` INT UNSIGNED NOT NULL,
    `nombre`       VARCHAR(120) NOT NULL,
    INDEX `idx_dist_prov` (`provincia_id`),
    UNIQUE KEY `uniq_dist` (`provincia_id`, `nombre`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `ciudades` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `distrito_id` INT UNSIGNED NOT NULL,
    `nombre`      VARCHAR(120) NOT NULL,
    INDEX `idx_ciu_dist` (`distrito_id`),
    UNIQUE KEY `uniq_ciu` (`distrito_id`, `nombre`)
) ENGINE=InnoDB;

-- ============================================================================
-- CATALOGOS
-- ============================================================================

CREATE TABLE IF NOT EXISTS `categorias` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre`      VARCHAR(120) NOT NULL UNIQUE,
    `descripcion` VARCHAR(255) NULL,
    `estado`      TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `proveedores` (
    `id`                    INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre`                VARCHAR(160) NOT NULL,
    `contacto`              VARCHAR(120) NULL,
    `telefono`              VARCHAR(40)  NULL,
    `email`                 VARCHAR(160) NULL,
    `direccion`             VARCHAR(255) NULL,
    `descripcion_productos` TEXT         NULL,
    `pais_id`               INT UNSIGNED NULL,
    `departamento_id`       INT UNSIGNED NULL,
    `provincia_id`          INT UNSIGNED NULL,
    `distrito_id`           INT UNSIGNED NULL,
    `ciudad_id`             INT UNSIGNED NULL,
    `estado`                TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_prov_pais`         (`pais_id`),
    INDEX `idx_prov_departamento` (`departamento_id`),
    INDEX `idx_prov_provincia`    (`provincia_id`),
    INDEX `idx_prov_distrito`     (`distrito_id`),
    INDEX `idx_prov_ciudad`       (`ciudad_id`)
) ENGINE=InnoDB;

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
    `longitud`      DECIMAL(10,2)  NULL,
    `diametro`      DECIMAL(10,2)  NULL,
    `precio_compra` DECIMAL(12,2)  NOT NULL DEFAULT 0,
    `precio_venta`  DECIMAL(12,2)  NOT NULL DEFAULT 0,
    `stock_actual`  DECIMAL(12,2)  NOT NULL DEFAULT 0,
    `stock_minimo`  DECIMAL(12,2)  NOT NULL DEFAULT 1,
    `imagen`        VARCHAR(255)   NULL,
    `estado`        TINYINT(1)     NOT NULL DEFAULT 1,
    `created_at`    DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_productos_codigo`    (`codigo`),
    INDEX `idx_productos_categoria` (`categoria_id`),
    INDEX `idx_productos_proveedor` (`proveedor_id`),
    INDEX `idx_productos_estado`    (`estado`)
) ENGINE=InnoDB;

-- ============================================================================
-- MOVIMIENTOS DE STOCK
-- ============================================================================

CREATE TABLE IF NOT EXISTS `movimientos` (
    `id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `producto_id`    INT UNSIGNED    NOT NULL,
    `tipo`           ENUM('entrada','salida','ajuste') NOT NULL,
    `motivo`         VARCHAR(20)     NULL,   -- venta|encargo|accidente|merma (sólo para salidas)
    `cantidad`       DECIMAL(12,2)   NOT NULL,
    `stock_anterior` DECIMAL(12,2)   NOT NULL,
    `stock_nuevo`    DECIMAL(12,2)   NOT NULL,
    `usuario_id`     INT UNSIGNED    NULL,
    `proveedor_id`   INT UNSIGNED    NULL,
    `encargo_id`     INT UNSIGNED    NULL,
    `cliente`        VARCHAR(160)    NULL,
    `total`          DECIMAL(12,2)   NULL,
    `fecha_entrega`  DATE            NULL,
    `evidencia`      TEXT            NULL,
    `observacion`    VARCHAR(500)    NULL,
    `created_at`     DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_movs_producto`  (`producto_id`),
    INDEX `idx_movs_tipo`      (`tipo`),
    INDEX `idx_movs_motivo`    (`motivo`),
    INDEX `idx_movs_usuario`   (`usuario_id`),
    INDEX `idx_movs_proveedor` (`proveedor_id`),
    INDEX `idx_movs_encargo`   (`encargo_id`),
    INDEX `idx_movs_created`   (`created_at`)
) ENGINE=InnoDB;

-- ============================================================================
-- ENCARGOS (clientes que reservan productos para entrega futura)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `encargos` (
    `id`             INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    `codigo`         VARCHAR(20)    NOT NULL UNIQUE,
    `cliente`        VARCHAR(160)   NOT NULL,
    `telefono`       VARCHAR(40)    NULL,
    `lugar_entrega`  VARCHAR(255)   NULL,
    `fecha_entrega`  DATE           NULL,
    `detalles`       TEXT           NULL,
    `notas_entrega`  TEXT           NULL,   -- consolidado de retazos al entregar
    `estado`         ENUM('pendiente','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
    `usuario_id`     INT UNSIGNED   NULL,
    `created_at`     DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_encargos_estado`  (`estado`),
    INDEX `idx_encargos_fecha`   (`fecha_entrega`),
    INDEX `idx_encargos_usuario` (`usuario_id`)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS `encargo_items` (
    `id`              INT UNSIGNED  AUTO_INCREMENT PRIMARY KEY,
    `encargo_id`      INT UNSIGNED  NOT NULL,
    `producto_id`     INT UNSIGNED  NOT NULL,
    `cantidad`        INT           NOT NULL,
    `precio_unitario` DECIMAL(12,2) NULL,
    INDEX `idx_ei_encargo`  (`encargo_id`),
    INDEX `idx_ei_producto` (`producto_id`)
) ENGINE=InnoDB;

-- ============================================================================
-- RETAZOS (sobrantes aprovechables que NO descuentan stock)
-- ============================================================================

CREATE TABLE IF NOT EXISTS `retazos` (
    `id`          INT UNSIGNED   AUTO_INCREMENT PRIMARY KEY,
    `producto_id` INT UNSIGNED   NOT NULL,
    `cantidad`    DECIMAL(12,2)  NOT NULL DEFAULT 1,
    `ancho`       DECIMAL(10,2)  NULL,
    `alto`        DECIMAL(10,2)  NULL,
    `longitud`    DECIMAL(10,2)  NULL,
    `origen`      ENUM('salida','encargo') NOT NULL,
    `origen_id`   INT UNSIGNED   NULL,   -- id del movimiento o encargo origen
    `observacion` VARCHAR(255)   NULL,
    `usuario_id`  INT UNSIGNED   NULL,
    `aprovechado` TINYINT(1)     NOT NULL DEFAULT 0,
    `created_at`  DATETIME       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_retazos_producto` (`producto_id`),
    INDEX `idx_retazos_origen`   (`origen`, `origen_id`),
    INDEX `idx_retazos_aprov`    (`aprovechado`),
    INDEX `idx_retazos_usuario`  (`usuario_id`),
    INDEX `idx_retazos_created`  (`created_at`)
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
    INDEX `idx_aud_fecha`   (`created_at`)
) ENGINE=InnoDB;

-- ============================================================================
-- PEDIDOS A PROVEEDOR
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
    INDEX `idx_ped_usuario`   (`usuario_id`),
    INDEX `idx_ped_fecha`     (`fecha_pedido`)
) ENGINE=InnoDB;

-- ============================================================================
-- DATOS SEMILLA
-- ============================================================================

-- El usuario admin se crea con database/install.php (hash bcrypt real).
-- No insertar aquí; ejecutar: php database/install.php

-- Categorías base
INSERT IGNORE INTO `categorias` (`nombre`, `descripcion`) VALUES
    ('Vidrio',      'Planchas de vidrio templado, laminado y espejos.'),
    ('Consumibles', 'Selladores, pegamentos y otros insumos de uso continuo.'),
    ('Herrajes',    'Bisagras, chapas, cerraduras y accesorios metálicos.'),
    ('Perfiles',    'Perfiles de aluminio y materiales por metro lineal.'),
    ('Tornillos',   'Tornillería, anclajes y elementos de fijación.');

-- Proveedores
INSERT IGNORE INTO `proveedores` (`nombre`, `contacto`, `telefono`, `email`, `direccion`) VALUES
    ('Vidrios Andinos S.A.',  'Carolina Méndez', '+57 601 555 0101', 'ventas@andinos.co',   'Bogotá, Colombia'),
    ('Cristalería del Sur',   'Iván Rojas',      '+57 322 712 0099', 'iv.rojas@cdsur.co',   'Cali, Colombia'),
    ('Templex Internacional', 'Mariana Vega',    '+57 604 444 8210', 'pedidos@templex.com', 'Medellín, Colombia');

-- Productos
-- Las columnas dimensionales se llenan según la unidad: lámina usa
-- ancho/alto/grosor; metro lineal usa longitud; unidad no usa ninguna.
INSERT IGNORE INTO `productos`
    (`codigo`, `nombre`, `descripcion`, `categoria_id`, `proveedor_id`,
     `unidad`, `ancho`, `alto`, `grosor`, `longitud`, `diametro`,
     `precio_compra`, `precio_venta`, `stock_actual`, `stock_minimo`)
VALUES
    ('VID-00001', 'Plancha vidrio templado 6mm', 'Lámina 1.83 × 2.44 m, templada incolora',
        1, 1, 'lámina',       1830, 2440,    6, NULL, NULL, 180000, 320000, 18, 5),
    ('VID-00002', 'Espejo plateado 4mm',         'Lámina 2.40 × 1.50 m, canto pulido',
        1, 2, 'lámina',       2400, 1500,    4, NULL, NULL, 130000, 230000, 22, 6),
    ('VID-00003', 'Silicona neutra transparente','Cartucho 300 ml, anti-hongos',
        2, 1, 'unidad',       NULL, NULL, NULL, NULL, NULL,  18000,  32000, 60, 12),
    ('VID-00004', 'Pegamento epóxico bicomponente','Kit 80 g resina + endurecedor',
        2, 3, 'unidad',       NULL, NULL, NULL, NULL, NULL,  22000,  38000, 35,  8),
    ('VID-00005', 'Bisagra hidráulica para puerta','Acero inoxidable, cierre suave',
        3, 2, 'unidad',       NULL, NULL, NULL, NULL, NULL,  45000,  78000, 24,  6),
    ('VID-00006', 'Chapa cerradura embutida',     'Cilindro doble llave, acabado cromo',
        3, 3, 'unidad',       NULL, NULL, NULL, NULL, NULL,  68000, 115000, 12,  4),
    ('VID-00007', 'Perfil aluminio plata 1"',     'Barra estándar 6 m, anodizado plata',
        4, 1, 'metro lineal', NULL, NULL, NULL, 6000, NULL,  12000,  21000, 90, 20),
    ('VID-00008', 'Tornillo autorroscante 1"',    'Cabeza plana, punta broca · paquete x100',
        5, 2, 'unidad',       NULL, NULL, NULL, NULL, NULL,   8500,  15000, 80, 15);

-- Movimientos iniciales (usuario_id = NULL porque el admin aún no existe)
INSERT IGNORE INTO `movimientos`
    (`producto_id`, `tipo`, `cantidad`, `stock_anterior`, `stock_nuevo`,
     `usuario_id`, `proveedor_id`, `observacion`)
VALUES
    (1, 'entrada', 18, 0, 18, NULL, 1, 'Carga inicial — planchas de vidrio'),
    (2, 'entrada', 22, 0, 22, NULL, 2, 'Carga inicial — espejos'),
    (3, 'entrada', 60, 0, 60, NULL, 1, 'Compra silicona x60'),
    (4, 'entrada', 35, 0, 35, NULL, 3, 'Carga inicial — pegamento'),
    (5, 'entrada', 24, 0, 24, NULL, 2, 'Carga inicial — bisagras'),
    (6, 'entrada', 12, 0, 12, NULL, 3, 'Carga inicial — chapas'),
    (7, 'entrada', 90, 0, 90, NULL, 1, 'Carga inicial — perfiles aluminio'),
    (8, 'entrada', 80, 0, 80, NULL, 2, 'Carga inicial — tornillos');

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
SELECT r.id, p.id FROM `roles` r INNER JOIN `permisos` p
  ON p.codigo IN (
    'producto.ver', 'categoria.ver', 'proveedor.ver',
    'movimiento.ver', 'movimiento.entrada', 'movimiento.salida',
    'reporte.ver', 'reporte.ventas', 'reporte.consolidado_proveedor',
    'pedido.ver'
  )
WHERE r.nombre = 'operador';

-- Backfill: para usuarios con rol_id NULL, copiar desde el nombre del rol.
UPDATE `usuarios` u
   INNER JOIN `roles` r ON r.nombre = u.rol
   SET u.rol_id = r.id
 WHERE u.rol_id IS NULL;

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
SELECT d.id, 'Bogotá D.C.' FROM departamentos d INNER JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Colombia' AND d.nombre = 'Cundinamarca';
INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT d.id, 'Valle de Aburrá' FROM departamentos d INNER JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Colombia' AND d.nombre = 'Antioquia';
INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT d.id, 'Sur del Valle' FROM departamentos d INNER JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Colombia' AND d.nombre = 'Valle del Cauca';
INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT d.id, 'Lima' FROM departamentos d INNER JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Perú' AND d.nombre = 'Lima';
INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT d.id, 'Arequipa' FROM departamentos d INNER JOIN paises p ON p.id = d.pais_id
WHERE p.nombre = 'Perú' AND d.nombre = 'Arequipa';
INSERT IGNORE INTO `provincias` (`departamento_id`, `nombre`)
SELECT d.id, 'CDMX' FROM departamentos d INNER JOIN paises p ON p.id = d.pais_id
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
