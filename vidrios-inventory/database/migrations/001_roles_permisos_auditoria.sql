-- ============================================================================
-- 001 · roles, permisos, roles_permisos, auditoria
-- Idempotente: usa CREATE TABLE IF NOT EXISTS e INSERT IGNORE.
-- ============================================================================

USE `vidrios_inventory`;
SET NAMES utf8mb4;

-- ----------------------------------------------------------------------------
-- roles
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `nombre`      VARCHAR(50)  NOT NULL UNIQUE,
    `descripcion` VARCHAR(255) NULL,
    `activo`      TINYINT(1)   NOT NULL DEFAULT 1,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- permisos
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permisos` (
    `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `codigo`      VARCHAR(80)  NOT NULL UNIQUE,
    `nombre`      VARCHAR(120) NOT NULL,
    `descripcion` VARCHAR(255) NULL,
    `modulo`      VARCHAR(50)  NOT NULL,
    `created_at`  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_modulo` (`modulo`)
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- roles_permisos (M:N)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `roles_permisos` (
    `rol_id`     INT UNSIGNED NOT NULL,
    `permiso_id` INT UNSIGNED NOT NULL,
    PRIMARY KEY (`rol_id`, `permiso_id`),
    CONSTRAINT `fk_rp_rol`     FOREIGN KEY (`rol_id`)     REFERENCES `roles`(`id`)    ON DELETE CASCADE,
    CONSTRAINT `fk_rp_permiso` FOREIGN KEY (`permiso_id`) REFERENCES `permisos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- auditoria
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `auditoria` (
    `id`             BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `usuario_id`     INT UNSIGNED NULL,
    `usuario_email`  VARCHAR(160) NULL,
    `accion`         VARCHAR(50)  NOT NULL,
    `entidad`        VARCHAR(50)  NOT NULL,
    `entidad_id`     VARCHAR(50)  NULL,
    `descripcion`    VARCHAR(500) NULL,
    `ip`             VARCHAR(45)  NULL,
    `user_agent`     VARCHAR(255) NULL,
    `created_at`     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_aud_usuario` (`usuario_id`),
    INDEX `idx_aud_entidad` (`entidad`, `entidad_id`),
    INDEX `idx_aud_fecha`   (`created_at`),
    CONSTRAINT `fk_aud_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ----------------------------------------------------------------------------
-- Seed roles
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO `roles` (`nombre`, `descripcion`) VALUES
  ('admin',    'Acceso total al sistema; gestiona catálogos, movimientos y auditoría.'),
  ('operador', 'Registra movimientos y consulta inventario; sin gestión de catálogos.');

-- ----------------------------------------------------------------------------
-- Seed permisos (CRUD por módulo + acciones de movimientos y reportes)
-- ----------------------------------------------------------------------------
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

  ('auditoria.ver',       'Ver bitácora de auditoría', 'auditoria'),

  ('rol.ver',             'Ver roles y permisos',   'rol');

-- ----------------------------------------------------------------------------
-- Asignación de permisos a roles
-- admin:    todos
-- operador: solo lectura + registro de movimientos + reportes
-- ----------------------------------------------------------------------------
INSERT IGNORE INTO `roles_permisos` (`rol_id`, `permiso_id`)
SELECT r.id, p.id FROM `roles` r CROSS JOIN `permisos` p
WHERE r.nombre = 'admin';

INSERT IGNORE INTO `roles_permisos` (`rol_id`, `permiso_id`)
SELECT r.id, p.id FROM `roles` r JOIN `permisos` p ON p.codigo IN (
    'producto.ver', 'categoria.ver', 'proveedor.ver',
    'movimiento.ver', 'movimiento.entrada', 'movimiento.salida',
    'reporte.ver'
)
WHERE r.nombre = 'operador';
