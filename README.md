# Vitralia — Sistema de Inventario de Vidrios

> Sistema web de gestión de inventario para **Vidrios Centro Puno E.I.R.L.**  
> Control de stock, movimientos, pedidos a proveedores y reportes en tiempo real.

---

## Características principales

- **Catálogo de productos** con imágenes, dimensiones y precios de compra/venta
- **Kardex de movimientos** — entradas, salidas y ajustes de stock con trazabilidad completa
- **Pedidos a proveedores** con estados (pendiente / pagado / deuda)
- **Reportes exportables a CSV**: stock crítico, valor de inventario, ventas por período y consolidado de proveedores
- **Auditoría de acciones** — registro de quién hizo qué y cuándo
- **Dashboard** con métricas en tiempo real y alertas de stock bajo
- **Gestión de proveedores** con ubicación geográfica en cascada (país → departamento → provincia → distrito → ciudad)
- **Roles de acceso**: `admin` (acceso total) y `operador` (registro de movimientos)

---

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8 — MVC sin framework, sin Composer |
| Base de datos | MySQL 8 / InnoDB / utf8mb4 |
| Servidor | Apache + mod_rewrite (Laragon / XAMPP / WAMP) |
| Frontend | HTML5, CSS3 propio, JavaScript vanilla |
| Sesiones | PHP nativas (`$_SESSION`) |
| Seguridad | PDO con prepared statements, bcrypt para contraseñas |

---

## Requisitos

- PHP 8.0 o superior
- MySQL 8.0 o superior
- Apache con `mod_rewrite` y `mod_headers` activos
- Extensiones PHP: `pdo_mysql`, `fileinfo`, `zip`

---

## Instalación

### 1. Clonar el repositorio

```bash
git clone https://github.com/<tu-usuario>/<tu-repo>.git
cd <tu-repo>
```

### 2. Configurar la base de datos

Edita las credenciales en `vidrios-inventory/config/database.php`:

```php
'host'     => '127.0.0.1',
'dbname'   => 'vidrios_inventory',
'user'     => 'root',
'password' => '',
```

### 3. Cargar el schema

Desde phpMyAdmin o consola:

```bash
mysql -u root < vidrios-inventory/database/schema.sql
```

### 4. Crear el usuario administrador

```bash
php vidrios-inventory/database/install.php
```

Credenciales de acceso demo:

```
Email:      admin@vitralia.co
Contraseña: vidrio123
```

### 5. Configurar la URL base

Edita `vidrios-inventory/config/config.php` y `vidrios-inventory/.htaccess` según la ruta donde sirvas el proyecto:

```php
// config.php
define('BASE_URL', '/vidrios-inventory');
```

```apache
# .htaccess
RewriteBase /vidrios-inventory/
```

### 6. Acceder al sistema

```
http://localhost/<ruta>/vidrios-inventory/public
```

---

## Estructura del proyecto

```
vidrios-inventory/
├── app/
│   ├── controllers/     # Un controlador por módulo
│   ├── models/          # Un modelo por entidad
│   └── views/           # Vistas PHP puras organizadas por módulo
│       └── layouts/     # header, sidebar y footer compartidos
├── config/
│   ├── config.php       # Constantes globales (BASE_URL, DEBUG, etc.)
│   └── database.php     # Credenciales de conexión
├── core/
│   ├── Controller.php   # Clase base abstracta para controladores
│   ├── Database.php     # Singleton PDO
│   ├── Exporter.php     # Generador de archivos CSV
│   ├── Model.php        # Clase base para modelos
│   ├── Paginator.php    # Paginación reutilizable
│   └── Router.php       # Enrutador URL → Controlador@acción
├── database/
│   ├── schema.sql       # Schema completo con datos semilla
│   ├── install.php      # Script para crear el usuario admin
│   └── migrations/      # Scripts adicionales de base de datos
└── public/
    ├── index.php        # Front-controller (punto de entrada único)
    ├── css/
    ├── js/
    └── img/
```

---

## Módulos disponibles

| Módulo | Ruta | Descripción |
|---|---|---|
| Dashboard | `/dashboard` | Resumen y métricas generales |
| Productos | `/producto` | CRUD + stock + imágenes |
| Categorías | `/categoria` | Clasificación de productos |
| Proveedores | `/proveedor` | Directorio con ubicación geográfica |
| Movimientos | `/movimiento` | Entradas, salidas y ajustes de stock |
| Pedidos | `/pedido` | Órdenes de compra a proveedores |
| Reportes | `/reporte` | 5 reportes con exportación a CSV |
| Auditoría | `/auditoria` | Bitácora de acciones del sistema |
| Roles | `/rol` | Visualización de roles y permisos |

---

## Flujo de una petición

```
Navegador → Apache (.htaccess) → public/index.php → Router
  → Controller → Model → Base de datos
  → Controller → View → Respuesta HTML
```

---

## Exportación a CSV

Cada módulo incluye un botón **↓ Exportar CSV** que descarga los datos visibles en pantalla respetando los filtros activos. Los archivos incluyen BOM UTF-8 para compatibilidad con Microsoft Excel.

---

## Capturas de pantalla

> *(Agrega capturas de pantalla aquí para darle más presencia al proyecto)*

---

## Licencia

Este proyecto es de uso interno para **Vidrios Centro Puno E.I.R.L.**
