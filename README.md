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
- **Sincronización en vivo (WebSocket)** — cuando un usuario registra una venta, todos los navegadores conectados ven el descuento de stock al instante sin recargar la página
- **Gestión de proveedores** con ubicación geográfica en cascada (país → departamento → provincia → distrito → ciudad)
- **Roles de acceso**: `admin` (acceso total) y `operador` (registro de movimientos)

---

## Stack tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8 — MVC sin framework |
| Base de datos | MySQL 8 / InnoDB / utf8mb4 |
| Servidor web | Apache + mod_rewrite (Laragon / XAMPP / WAMP) |
| Tiempo real | Daemon PHP con **Ratchet** (WebSocket) + **ReactPHP** (event loop) |
| Dependencias del daemon | Composer (sólo para el módulo realtime; el resto de la app no lo usa) |
| Frontend | HTML5, CSS3 propio, JavaScript vanilla + WebSocket API |
| Sesiones | PHP nativas (`$_SESSION`) |
| Seguridad | PDO con prepared statements, bcrypt para contraseñas |

---

## Requisitos

- PHP 8.1 o superior (CLI + módulo Apache)
- MySQL 8.0 o superior
- Apache con `mod_rewrite` y `mod_headers` activos
- Extensiones PHP: `pdo_mysql`, `fileinfo`, `zip`, `sockets`
- **Composer** — sólo para instalar las dependencias del daemon WebSocket (`cboden/ratchet`, `react/http`). El resto de la app sigue funcionando sin Composer.

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

### 6. Instalar las dependencias del daemon WebSocket

Desde la carpeta `vidrios-inventory/`:

```bash
composer install
```

Esto genera la carpeta `vendor/` con Ratchet y ReactPHP. **No hace falta** para el flujo de catálogo/ventas básico: si decides desactivar el tiempo real, pon `REALTIME_ENABLED` en `false` dentro de [`config/realtime.php`](vidrios-inventory/config/realtime.php) y los `publish()` se vuelven no-op.

### 7. Arrancar el daemon WebSocket

En una **consola dedicada** (déjala abierta mientras el sistema esté en uso):

```bash
php bin/ws-server.php
```

Verás:

```
[Vitralia WS] WebSocket escuchando en 0.0.0.0:8080 · push interno en 127.0.0.1:8081 · PID=12345
```

Detalles, troubleshooting y servicio en producción → sección [Sincronización en tiempo real (WebSocket)](#sincronización-en-tiempo-real-websocket) más abajo.

### 8. Acceder al sistema

```
http://localhost/<ruta>/vidrios-inventory/public
```

Si todo está OK, en la barra superior aparecerá un indicador verde **● live** mostrando que el navegador está conectado al daemon WS.

---

## Estructura del proyecto

```
vidrios-inventory/
├── app/
│   ├── controllers/     # Un controlador por módulo
│   ├── models/          # Un modelo por entidad
│   ├── realtime/        # Hub WebSocket (Vitralia\Realtime — namespaced, autoload PSR-4)
│   └── views/           # Vistas PHP puras organizadas por módulo
│       └── layouts/     # header, sidebar y footer compartidos
├── bin/
│   └── ws-server.php    # Daemon WebSocket (PHP CLI) — arranca Ratchet + bridge HTTP
├── config/
│   ├── config.php       # Constantes globales (BASE_URL, DEBUG, etc.)
│   ├── database.php     # Credenciales de conexión
│   └── realtime.php     # Puertos, URL pública del WS y secreto del bridge
├── core/
│   ├── Controller.php   # Clase base abstracta para controladores
│   ├── Database.php     # Singleton PDO
│   ├── Exporter.php     # Generador de archivos CSV
│   ├── Model.php        # Clase base para modelos
│   ├── Paginator.php    # Paginación reutilizable
│   ├── Realtime.php     # Cliente PHP que publica eventos al daemon WS
│   └── Router.php       # Enrutador URL → Controlador@acción
├── database/
│   ├── schema.sql       # Schema completo con datos semilla
│   ├── install.php      # Script para crear el usuario admin
│   └── migrations/      # Scripts adicionales de base de datos
├── public/
│   ├── index.php        # Front-controller (punto de entrada único)
│   ├── css/             # custom.css + realtime.css
│   ├── js/              # app.js + realtime.js (cliente WebSocket del navegador)
│   └── img/
├── composer.json        # Sólo para el daemon: Ratchet + ReactPHP
└── vendor/              # Generado por `composer install`
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
| Retazos | `/retazo` | Sobrantes aprovechables (no descuentan stock) |

### Qué hace cada módulo (en detalle)

A continuación se describen los módulos en orden de uso típico — desde lo que cargas primero hasta los reportes que consumes al final.

#### Dashboard — `/dashboard`
La pantalla de inicio cuando entras al sistema. Muestra **métricas en vivo**: total de productos activos, stock crítico, ventas del mes y movimientos recientes. Incluye dos gráficos: barras de flujo mensual (entradas vs salidas) y un donut con la distribución por categoría. Es de **solo lectura**; sirve para "ver de un vistazo" cómo va el taller.

#### Productos (Catálogo) — `/producto`
El corazón del inventario. Cada **producto** tiene código único, nombre, categoría, proveedor, **unidad de medida** (m², lámina, unidad, tubo o metro lineal), dimensiones que varían según la unidad, precios de compra y venta, stock actual y stock mínimo. Acciones disponibles: crear, editar, archivar, ver detalle, ajustar stock manualmente y exportar a CSV. Las imágenes de los productos se guardan en `public/img/productos/`.

#### Categorías — `/categoria`
Clasificación de productos en grupos lógicos (Vidrio, Consumibles, Herrajes, Perfiles, Tornillos…). Cada producto pertenece a **una** categoría. Permite activar/desactivar categorías sin perder los productos que las usan.

#### Proveedores — `/proveedor`
Directorio de a quién compras. Cada proveedor tiene nombre, contacto, teléfono, email y **ubicación geográfica en cascada**: País → Departamento → Provincia → Distrito → Ciudad. Esa cascada se llena dinámicamente con AJAX al elegir el nivel superior.

#### Retazos — `/retazo`
**Sobrantes aprovechables** generados al vender o entregar encargos (sólo aplica a unidades como m², lámina o metro lineal). Cada retazo guarda: producto, cantidad, medidas en cm (ancho/alto/longitud según la unidad), origen (salida o encargo), id del origen, observación y estado (disponible / aprovechado). **No descuentan stock**; sirven para que el operario sepa qué sobras tiene antes de cortar una pieza nueva.

#### Movimientos — `/movimiento`
La **bitácora de stock**. Cada cambio del inventario es un movimiento de tipo `entrada`, `salida` o `ajuste`. Las salidas además tienen un `motivo`: `venta`, `encargo`, `accidente` o `merma`. Cada movimiento guarda stock anterior, stock nuevo, cantidad, usuario, proveedor (si aplica) y observaciones. Todas las salidas pasan por `Movimiento::registrar()` que abre una transacción con `SELECT ... FOR UPDATE` para evitar carreras.

#### Encargos — `/encargo`
Reservas de productos para entrega futura. Al crearlo se **descuenta el stock inmediatamente** (queda apartado para el cliente). El encargo tiene 3 estados: `pendiente`, `entregado` o `cancelado`. Al marcar como entregado se abre un modal donde por cada producto puedes anotar mermas, accidentes o retazos generados en el corte/entrega. Al cancelar se devuelve el stock automáticamente.

#### Pedidos — `/pedido`
Órdenes de compra a proveedores. Cada pedido tiene número, proveedor, fecha de pedido, fecha de entrega prevista, total y estado (`pendiente`, `pagado` o `deuda`). Permite seguir cuánto se le ha pagado a cada proveedor y cuánto queda pendiente.

#### Reportes — `/reporte`
5 reportes con filtros y exportación a CSV:
- **Stock** (`/reporte/stock`) — listado completo con stock actual, mínimo, faltante. Marca críticos.
- **Mermas y accidentes** (`/reporte/mermas`) — movimientos con motivo merma o accidente, agrupados por producto o detalle cronológico.
- **Ventas del día** (`/reporte/ventas`) — ventas por período (día/semana/mes).
- **Consolidado de proveedores** (`/reporte/consolidadoProveedores`) — agrupa el inventario por proveedor con valorización a precio de compra.

#### Auditoría — `/auditoria`
Bitácora de acciones del sistema: quién hizo qué, sobre qué entidad y cuándo. Cada vez que un controlador llama a `Controller::audit()` se inserta una fila en la tabla `auditoria` con usuario, acción (crear/editar/eliminar/etc.), entidad afectada, id, descripción, IP y user-agent. **No bloquea el flujo de negocio** — si falla el log, el guardado normal continúa.

#### Usuarios — `/usuario`
Gestión de cuentas con email, nombre, rol (`admin` u `operador`) y permisos individuales adicionales. Las contraseñas se guardan con `password_hash()` (bcrypt). Admin puede crear, editar, activar/desactivar usuarios.

#### Roles — `/rol`
Visualización del modelo RBAC (Role-Based Access Control). Cada rol tiene un conjunto de permisos asignados (`producto.crear`, `movimiento.salida`, etc.). Los permisos se gestionan vía la tabla `roles_permisos` y opcionalmente vía `usuarios_permisos` para extras por usuario. **Nota**: hoy el control de acceso del request todavía mira `$_SESSION['usuario']['rol']` directamente con strings (`'admin'` vs `'operador'`); las tablas RBAC están conectadas a los modelos pero aún no son la fuente única de verdad.

---

## Arquitectura — el patrón MVC

Este proyecto usa **MVC manual** (sin framework) porque la lógica de negocio cabe entera y queda más fácil de mantener sin abstracciones extra. A continuación, qué es MVC en palabras simples, cómo está organizado aquí y qué pasa exactamente cuando un usuario crea un producto.

### Qué significan M, V y C

MVC separa el código en **tres responsabilidades** que no se mezclan:

| Pieza | Qué hace | Qué NO hace | Dónde vive |
|---|---|---|---|
| **Modelo (M)** | Conoce la base de datos. Hace consultas, valida invariantes del negocio, ejecuta transacciones. | NO genera HTML. NO sabe de URLs ni de sesiones. | `app/models/` |
| **Vista (V)** | Genera HTML para mostrar al usuario. Recibe datos ya listos del controlador y los formatea. | NO consulta BD. NO modifica datos. | `app/views/` |
| **Controlador (C)** | Es el "policía de tráfico". Recibe la petición, llama al modelo, decide qué vista mostrar y con qué datos. | NO escribe SQL directamente. NO contiene HTML grande. | `app/controllers/` |

La regla simple: el navegador habla con el controlador, el controlador habla con el modelo, y la vista solo lee datos para pintar.

### Cómo se conectan en este proyecto

```
[Navegador] HTTP request
     │
     ▼
[Apache + .htaccess]
   reescribe la URL → /public/index.php?url=<ruta>
     │
     ▼
[public/index.php] (front-controller)
   - inicia sesión
   - carga config/, core/ y registra el autoloader
     │
     ▼
[core/Router.php]
   parsea la URL → decide qué Controlador@acción ejecutar
     │
     ▼
[app/controllers/XxxController.php]
   - valida la petición
   - llama al modelo
     │
     ▼
[app/models/Xxx.php]
   - ejecuta SQL (PDO con prepared statements)
   - devuelve datos al controlador
     │
     ▼
[app/views/.../*.php]
   - recibe los datos
   - genera HTML
     │
     ▼
[Navegador] respuesta HTML
```

Las piezas en `core/` son la **infraestructura compartida**: `Database` (singleton PDO), `Router`, `Controller` base, `Model` base, `Paginator`, `Exporter`. Todo lo de negocio vive en `app/`.

### Ejemplo paso a paso — crear un producto

Imaginemos que el administrador entra al catálogo, presiona "Nuevo producto", rellena el formulario y presiona "Crear producto". Esto es **exactamente** lo que pasa entre bambalinas.

#### 1. El navegador envía el POST

El form del modal hace:
```
POST /vidrios-inventory/producto/crear
Content-Type: multipart/form-data
codigo=&nombre=Plancha+4mm&categoria_id=1&unidad=lámina&ancho=1830&alto=2440&grosor=4&precio_compra=180000&precio_venta=320000&stock_actual=10&stock_minimo=5
```

#### 2. Apache reescribe la URL

[`public/.htaccess`](vidrios-inventory/.htaccess) detecta que `/producto/crear` no es un archivo físico y aplica `RewriteRule`:
```
/producto/crear  →  /public/index.php?url=producto/crear
```
También bloquea acceso directo a `app/`, `core/`, `config/` y `database/`.

#### 3. `public/index.php` arranca el sistema

[`public/index.php`](vidrios-inventory/public/index.php) hace 4 cosas en orden:
1. `session_start()` — restaura la sesión del usuario.
2. Define la constante `ROOT` y carga `config/config.php` (constantes globales) y `config/database.php` (credenciales BD).
3. **Carga manualmente** los 5 archivos de `core/`: `Database`, `Model`, `Controller`, `Router`, `Paginator`, `Exporter`, más los helpers `Icons.php` y `Format.php`.
4. Registra un `spl_autoload_register` que resuelve clases de `app/controllers/` y `app/models/` bajo demanda.

#### 4. El Router decide qué se ejecuta

[`core/Router.php`](vidrios-inventory/core/Router.php) parsea la URL `producto/crear`:
- Primer segmento → `Producto` + sufijo `Controller` → busca la clase `ProductoController`.
- Segundo segmento → `crear` (en camelCase) → método `crear()` del controlador.
- Segmentos restantes (ninguno aquí) → argumentos posicionales.

El autoloader carga [`app/controllers/ProductoController.php`](vidrios-inventory/app/controllers/ProductoController.php) automáticamente.

#### 5. El Controlador valida y llama al Modelo

`ProductoController::crear()` (`ProductoController.php:42`) hace en orden:

1. **Requiere autenticación** con `$this->requireAuth()`. Si no hay sesión válida, redirige a `/auth/login`.
2. **Detecta que es POST** y extrae el formulario:
   ```php
   $form = $this->extraerForm($_POST);
   ```
   Este helper privado limpia y castea cada campo (`trim()`, `(int)`, `(float)`, valida que la unidad esté en la lista permitida, etc.).
3. **Valida** con `$errores = $this->validar($form)` — devuelve un arreglo con errores por campo. Si está vacío, todo pasó.
4. Si el código vino vacío, **autogenera uno** llamando a `$this->productos->generarCodigoUnico()` que pertenece al modelo.
5. **Procesa la imagen** con `procesarImagen()` (valida MIME, tamaño, mueve el archivo a `public/img/productos/`).
6. Si no hay errores, **llama al modelo para guardar**:
   ```php
   $newId = $this->productos->create($form);
   ```
7. **Registra en la auditoría**:
   ```php
   $this->audit('crear', 'producto', (string) $newId, "Producto «{$form['nombre']}» creado.");
   ```
8. **Pone un mensaje flash** en sesión y **redirige** al listado:
   ```php
   $this->setFlash('success', "Producto «{$form['nombre']}» creado correctamente.");
   $this->redirect('/producto/index');
   ```

#### 6. El Modelo escribe en la base de datos

[`app/models/Producto.php`](vidrios-inventory/app/models/Producto.php) extiende `BaseModel`, que a su vez extiende `core/Model.php`. El método `create($datos)` heredado:
1. Construye un `INSERT INTO productos (...) VALUES (...)` con **placeholders** para cada columna.
2. Llama a `PDO::prepare()` y luego a `execute()` con los valores.
3. Devuelve el `lastInsertId()`.

Como toda inserción usa **prepared statements**, no hay forma de inyectar SQL desde el formulario.

La conexión PDO viene del singleton en [`core/Database.php`](vidrios-inventory/core/Database.php) — todos los modelos comparten la misma conexión por petición.

#### 7. La redirección dispara una nueva petición GET

El navegador recibe un `HTTP 302` con `Location: /vidrios-inventory/producto/index`. Hace un GET a esa URL y todo el ciclo se repite:
- Apache rewrite → `index.php?url=producto/index`
- Router → `ProductoController::index()`
- Controller llama a `Producto::buscar(...)` y `Producto::contarBusqueda(...)` (para paginación)
- Controller llama a `$this->render('productos/index', [...])` con los productos y filtros

#### 8. La Vista genera el HTML

`$this->render(...)` (en `core/Controller.php:10`):
1. Carga las variables globales del layout (`$usuario`, `$stockBajoCount`, `$flash`).
2. Hace `extract($data, EXTR_SKIP)` para que las claves del array se vuelvan variables PHP en la vista.
3. Incluye `app/views/layouts/header.php`, luego `layouts/sidebar.php`, luego la vista pedida [`app/views/productos/index.php`](vidrios-inventory/app/views/productos/index.php), y finalmente `layouts/footer.php`.
4. La vista usa los datos para pintar HTML con tarjetas. En la cabecera aparece el mensaje verde "Producto «Plancha 4mm» creado correctamente" leído desde `$flash`.

#### 9. El usuario ve el resultado

El navegador recibe el HTML del catálogo actualizado con la nueva tarjeta visible y el flash de éxito. Todo el ciclo tomó típicamente **menos de 50 ms** en localhost.

### Resumen visual del flujo del ejemplo

```
[Navegador] POST /producto/crear (con datos del form)
     │
     ▼
[.htaccess] rewrite → /public/index.php?url=producto/crear
     │
     ▼
[index.php] session_start, carga core/, registra autoloader
     │
     ▼
[Router] producto + crear → ProductoController::crear()
     │
     ▼
[ProductoController]
   ├─ requireAuth()                       ← seguridad
   ├─ extraerForm($_POST)                 ← limpieza/cast
   ├─ validar($form)                      ← reglas de negocio
   ├─ procesarImagen('imagen')            ← upload
   ├─ $productos->create($form)           ← llamada al Modelo
   └─ setFlash + redirect('/producto/index')
     │
     ▼
[Producto Modelo]
   └─ INSERT INTO productos VALUES (...)  ← PDO prepared
     │
     ▼
[Auditoria Modelo]
   └─ INSERT INTO auditoria (...)         ← traza
     │
     ▼
[HTTP 302] → nueva petición GET /producto/index
     │
     ▼
[ProductoController::index()]
   ├─ Producto::buscar(...)               ← consulta listado
   ├─ Producto::contarBusqueda(...)       ← total para paginación
   └─ render('productos/index', [...])
     │
     ▼
[Vista productos/index.php]
   └─ HTML con las tarjetas y flash de éxito
     │
     ▼
[Navegador] muestra el catálogo actualizado
```

### Por qué este diseño funciona bien aquí

- **Sin dependencias**: no hay Composer ni `vendor/`. Para desplegar basta con copiar la carpeta.
- **Cambios localizados**: si cambia una regla de stock, modificas un modelo. Si cambia un texto, modificas una vista. Si cambia una ruta, modificas un controlador.
- **Trazabilidad**: el `audit()` registra cada operación; la sesión registra el usuario; las transacciones agrupan operaciones críticas.
- **Seguridad por capas**: `.htaccess` bloquea acceso directo a internals; los controladores filtran con `requireAuth/requireAdmin`; los modelos usan prepared statements; las contraseñas pasan por bcrypt.

---

## Sincronización en tiempo real (WebSocket)

Hasta aquí MVC describe **una sola petición** de un solo usuario. ¿Qué pasa cuando dos personas están conectadas al sistema y una de ellas cambia el stock?

> **El caso de uso real**: en el taller hay un vendedor en mostrador y un encargado de inventario en el almacén. El vendedor cobra una **plancha de vidrio templado 6 mm** y descuenta 1 lámina del stock. **El encargado de inventario tiene que enterarse al instante** para que no entregue esa misma lámina a otra persona ni la cuente como disponible.
>
> Sin tiempo real, el inventario sólo se entera cuando recarga manualmente la pantalla — abre ventana de 5–30 segundos donde dos cabezas creen tener el mismo stock disponible. **Con tiempo real, esa ventana desaparece**: en cuanto el vendedor confirma la venta, al encargado de inventario le aparece un toast en su pantalla con "Venta · Plancha vidrio templado 6mm · −1 lámina · stock 17 · por Alex" y la fila del producto en su catálogo parpadea en ámbar señalando que acaba de cambiar.

Esta sección explica cómo está hecho.

### Tres procesos cooperando

Antes del realtime existían **dos** procesos: tu navegador y Apache+PHP. Ahora hay **tres**:

```
┌────────────────────┐                          ┌────────────────────┐
│  Navegador VENDED. │                          │  Navegador INVENT. │
└─────────┬──────────┘                          └─────────▲──────────┘
          │ 1. POST /movimiento/registrarVenta            │ 4. push WS (broadcast)
          ▼                                               │
┌────────────────────┐                          ┌────────┴───────────┐
│   Apache + PHP     │   2. INSERT + UPDATE     │  Daemon Ratchet    │
│   (request del     │  ─────────────────────►  │  (proceso PHP CLI  │
│    vendedor)       │     MySQL                │  largo, siempre    │
│                    │                          │  vivo)             │
│                    │   3. POST /push          │                    │
│                    │  ─────────────────────►  │  WS público :8080  │
│                    │   localhost:8081         │  Push HTTP :8081   │
└────────────────────┘                          └────────────────────┘
```

- (1) El vendedor hace el POST de venta. Apache lo procesa como siempre.
- (2) El controlador descuenta stock en MySQL dentro de la transacción habitual.
- (3) **Después del commit**, el controlador hace un `POST /push` a `127.0.0.1:8081` (el bridge HTTP interno del daemon) con el JSON `{event: "stock_changed", data: {producto_id, stock_nuevo, …}}`. Si el daemon está caído el POST falla con timeout corto y el request del usuario continúa normal (degradación elegante).
- (4) El daemon **difunde ese JSON por WebSocket** a todos los navegadores conectados (incluido el del encargado de inventario). El JavaScript del cliente recibe el mensaje, muestra el toast y marca la fila como obsoleta.

### Por qué WebSocket y no AJAX polling

El polling (cada cliente hace `fetch` cada N segundos) funciona pero:
- Multiplica las consultas: 10 usuarios × cada 5 s = 120 consultas/minuto sólo para "ver si algo cambió".
- Tiene **delay** estructural igual al intervalo de poll: si pones 5 s, el peor caso es 5 s de retraso.
- Cuesta **siempre lo mismo** aunque no haya cambios.

WebSocket es lo opuesto:
- Una sola conexión TCP por cliente, que se mantiene abierta.
- **Cero tráfico** cuando no hay cambios.
- **Latencia ≈ red local** (típicamente <50 ms) entre el commit del vendedor y el toast del inventario.

### El daemon — qué es y por qué necesitamos un proceso aparte

Apache+PHP no puede servir WebSockets de forma natural porque su modelo es **un proceso por request**: cuando termina la respuesta, el proceso muere y no puede recordar a quién tenía conectado. WebSocket exige un proceso **largo** que mantenga abiertas las conexiones de todos los usuarios.

[`bin/ws-server.php`](vidrios-inventory/bin/ws-server.php) es ese proceso. Es un script PHP CLI que arranca un único **event loop** de ReactPHP y monta encima **dos sockets**:

| Socket | Puerto | Quién habla aquí | Para qué |
|---|---|---|---|
| **WebSocket público** | `0.0.0.0:8080` | Los navegadores de los usuarios | Reciben los eventos de cambio de stock en vivo |
| **HTTP interno** | `127.0.0.1:8081` | Los procesos PHP de Apache | Cada controlador empuja sus eventos por aquí después de hacer commit |

Ambos sockets viven en el **mismo event loop**, así que es un único proceso PHP corriendo. No hay base de datos en el daemon; sólo retransmite mensajes — lo más simple y rápido posible.

### Los archivos involucrados

| Archivo | Rol |
|---|---|
| [`composer.json`](vidrios-inventory/composer.json) | Declara las dependencias `cboden/ratchet` y `react/http` y mapea el namespace `Vitralia\Realtime\` a `app/realtime/`. |
| [`config/realtime.php`](vidrios-inventory/config/realtime.php) | Define los puertos, la URL WS pública, el secreto compartido y el switch `REALTIME_ENABLED`. |
| [`bin/ws-server.php`](vidrios-inventory/bin/ws-server.php) | Entry point del daemon: levanta los dos sockets y arranca el loop. |
| [`app/realtime/InventoryHub.php`](vidrios-inventory/app/realtime/InventoryHub.php) | Hub Ratchet (`MessageComponentInterface`). Mantiene la lista de clientes conectados y hace `broadcast()` cuando llega un push. Ignora mensajes entrantes de los clientes (son pasivos por diseño). |
| [`core/Realtime.php`](vidrios-inventory/core/Realtime.php) | Cliente PHP que los controladores usan para publicar eventos. `Realtime::publishStockChange($productoId, [...])` consulta el stock actual y empuja el JSON al bridge HTTP interno con timeout corto. |
| [`public/js/realtime.js`](vidrios-inventory/public/js/realtime.js) | Cliente del navegador: lee `<meta name="realtime-ws">`, conecta al WS, muestra toasts, marca filas obsoletas, reconecta cada 3 s si pierde la conexión. |
| [`public/css/realtime.css`](vidrios-inventory/public/css/realtime.css) | Estilos para los toasts y para el indicador verde **● live** en el topbar. |

### Dónde dispara `publish()` cada controlador

| Controlador | Acción | Evento | Cuándo |
|---|---|---|---|
| `MovimientoController::registrarEntrada()` | Entrada manual de stock | `stock_changed` (`tipo: entrada`) | Tras la llamada exitosa a `Movimiento::registrar()` |
| `MovimientoController::procesarSalida()` | Venta, accidente, merma | `stock_changed` (`tipo: salida`, `motivo: venta|accidente|merma`) | Tras `$db->commit()` |
| `ProductoController::ajustarStock()` | Ajuste manual | `stock_changed` (`tipo: ajuste`) | Tras `Movimiento::registrar()` |
| `EncargoController::crear()` | Reserva con descuento | un `stock_changed` por producto del encargo | Tras `$encargos->crearConItems()` |
| `EncargoController::editar()` | Edición de encargo | un evento por producto afectado | Tras `actualizarConItems()` |
| `EncargoController::cancelar()` | Devolver stock reservado | un evento por producto restituido (`tipo: entrada`) | Tras `cancelar()` |
| `EncargoController::entregar()` | Mermas en entrega | un evento por producto con mermas | Tras `entregar()` |

**Regla clave**: el `publish()` siempre va **después del `commit()`**. Si lo pusiéramos dentro de la transacción, un rollback dejaría a los demás usuarios viendo un cambio que en realidad nunca ocurrió.

### El payload que viaja por la red

Lo que el cliente JS recibe se ve así:

```json
{
  "event": "stock_changed",
  "data": {
    "producto_id": 42,
    "producto_codigo": "VID-00042",
    "producto_nombre": "Plancha vidrio templado 6mm",
    "stock_nuevo": 17,
    "stock_minimo": 5,
    "unidad": "lámina",
    "stock_bajo_count": 3,
    "tipo": "salida",
    "motivo": "venta",
    "cantidad": 1,
    "delta": -1,
    "cliente": "Distribuidora Andina",
    "observacion": null,
    "usuario": "Alex E.",
    "usuario_id": 7,
    "usuario_rol": "operador"
  },
  "ts": "2026-05-16T15:42:33-05:00"
}
```

Es **información pública del inventario**: ningún dato sensible (no van contraseñas, ni hashes, ni emails de clientes). Por eso aceptamos conexiones WS de cualquier navegador con sesión iniciada sin un protocolo de auth complejo.

### Qué hace el JavaScript del cliente

[`public/js/realtime.js`](vidrios-inventory/public/js/realtime.js) hace cinco cosas, todas con vanilla JS:

1. **Conecta** al WS leyendo la URL desde `<meta name="realtime-ws">` (sólo emitido cuando hay sesión activa).
2. **Indicador en el topbar**: pinta un pill **● live** verde (conectado) o gris (reconectando).
3. **Toasts**: por cada `stock_changed` muestra una tarjeta en la esquina inferior derecha con tipo, motivo, delta (en rojo si baja, verde si sube), nombre del producto, stock nuevo y usuario que lo provocó.
4. **Resaltado de filas obsoletas**: busca elementos con `data-producto-id="<id>"` en la página actual (las tarjetas del catálogo `/producto` y las filas del historial `/movimiento`) y les añade una clase `.rt-stale` que parpadea en ámbar 2.8 s. Así el operario sabe sin ambigüedad qué fila ya no refleja el dato real.
5. **Actualiza el badge "Stock crítico"** del topbar: el payload incluye `stock_bajo_count` recién calculado, así que el contador no necesita esperar a la siguiente recarga.

Si el daemon se cae el cliente reintenta cada 3 s sin spamear errores en consola.

### Diagrama del ciclo completo — "vendedor cobra, inventario lo ve"

```
T=0     Vendedor confirma venta en /movimiento/registrarVenta
         │
         ▼
T+10ms   MovimientoController::procesarSalida() ejecuta:
           BEGIN
             SELECT ... FOR UPDATE   ← bloquea la fila del producto
             UPDATE productos SET stock_actual = 17
             INSERT INTO movimientos (...)
           COMMIT
         │
         ▼
T+15ms   Realtime::publishStockChange(42, ['tipo'=>'salida', ...])
           ↓
           SELECT codigo, nombre, stock_actual FROM productos WHERE id=42
           SELECT COUNT(*) FROM productos WHERE stock_actual <= stock_minimo
           ↓
           stream_socket_client → 127.0.0.1:8081 (POST /push, 250ms timeout)
         │
         ▼
T+18ms   Daemon Ratchet recibe el POST, valida X-Realtime-Secret,
         llama a InventoryHub::broadcast($payload)
         │
         ▼
T+20ms   Loop sobre SplObjectStorage de conexiones,
         envía el frame WS a cada cliente conectado
         │
         ▼
T+25ms   Navegador del encargado de inventario recibe el frame.
         realtime.js parsea el JSON, dispara handleStockChange():
           - Toast: "Venta · Plancha vidrio templado 6mm · −1 lámina · stock 17 · por Alex"
           - Marca la fila/tarjeta con data-producto-id="42" como .rt-stale (ámbar)
           - Actualiza el badge "Stock crítico" del topbar
         │
         ▼
T+35ms   Vendedor recibe redirect HTTP 302 → /movimiento/historial?motivo=venta
         (su request termina como antes)
```

**Latencia total observada** (LAN local): ~25–50 ms entre commit y toast en pantalla.

### Modos de despliegue del daemon

| Modo | Cómo se ejecuta | Cuándo usarlo |
|---|---|---|
| **Manual (dev)** | `php bin/ws-server.php` en una consola | Mientras desarrollas: ves los logs en vivo, puedes pararlo con Ctrl+C |
| **Servicio Windows** | Envolverlo con **NSSM** apuntando al binario PHP + script | Producción sobre Windows Server / Laragon en uso continuo |
| **Servicio Linux** | Unit systemd `[Service] ExecStart=/usr/bin/php /var/www/.../bin/ws-server.php` con `Restart=always` | Producción Linux |
| **Sin daemon** | Pon `REALTIME_ENABLED=false` en `config/realtime.php` | Cuando no quieres tiempo real; la app sigue funcionando con sus recargas manuales |

### Seguridad

- El bridge HTTP interno escucha **sólo en 127.0.0.1** — desde fuera de la máquina es inalcanzable.
- Aún así requiere la cabecera `X-Realtime-Secret`; cambia el valor `REALTIME_PUSH_SECRET` en `config/realtime.php` antes de producción.
- El WS público no expone datos sensibles: el payload tiene código, nombre, stock, motivo y nombre del usuario. Nada de eso pasa por encima de lo que un usuario logueado ya ve en su pantalla.
- Los clientes WS son **pasivos**: cualquier mensaje que un navegador envíe por el socket se descarta. Así un atacante con sesión no puede inyectar eventos falsos.
- En producción detrás de HTTPS, sirve el WS también vía TLS (`wss://`) — típicamente con un reverse proxy (nginx/Apache) reescribiendo `/ws` → `127.0.0.1:8080`.

### Cómo probarlo localmente en 30 segundos

1. Arranca el daemon en una consola: `php vidrios-inventory/bin/ws-server.php`
2. Abre el sistema en **dos ventanas distintas** del navegador (o navegador + incógnito) con dos usuarios distintos. Verifica que en ambas barras aparezca el pill verde **● live**.
3. En la ventana A, registra una venta de cualquier producto.
4. En la ventana B, **sin tocar nada**, observa el toast aparecer abajo a la derecha y la tarjeta del producto parpadeando en ámbar — **el número del stock cambia en vivo, sin recargar**.

### Eventos genéricos para todos los módulos — `entity_changed`

Además de `stock_changed` (específico de movimientos), cualquier acción que llame a `$this->audit($accion, $entidad, $id, $descripcion)` dispara automáticamente un evento `entity_changed`. Como **todos los CRUD del sistema ya llaman `audit()`** (es el patrón estándar del proyecto), la cobertura es total sin tocar cada controlador.

| Controlador (acción) | Entidad | Acción auditada | Lo que ve el resto de usuarios |
|---|---|---|---|
| `ProductoController::crear/editar/eliminar` | `producto` | `crear`, `editar`, `archivar` | Toast + **la tarjeta se actualiza en vivo** (refresh in-place) |
| `CategoriaController::*` | `categoria` | `crear`, `editar`, `eliminar` | Toast + la card parpadea en ámbar |
| `ProveedorController::*` | `proveedor` | `crear`, `editar`, `eliminar` | Toast + la fila parpadea en ámbar |
| `UsuarioController::*` | `usuario` | `crear`, `editar`, `desactivar` | Toast + la fila parpadea |
| `RolController::*` | `rol` | `editar` (permisos) | Toast + la card parpadea |
| `RetazoController::*` | `retazo` | `crear`, `aprovechar`, `eliminar` | Toast + la fila parpadea |
| `PedidoController::*` | `pedido` | `crear`, `pagar`, `editar` | Toast + la fila parpadea |
| `EncargoController::*` | `encargo` | `crear`, `editar`, `entregar`, `cancelar` | Toast + la tarjeta parpadea (además del `stock_changed` por cada producto afectado) |

### Cómo extender el refresh in-place a otro módulo (ejemplo en 3 pasos)

Hoy sólo **producto** se refresca in-place (su tarjeta se reescribe sola con datos nuevos). Para los demás módulos sólo parpadea la fila — el usuario tiene que recargar para ver el dato actualizado. Si quieres añadir refresh in-place a otro módulo, son tres pasos:

**Paso 1** — Extrae la fila/tarjeta a un partial reutilizable. Ejemplo para proveedores:

Crea `app/views/proveedores/_row.php` con sólo el `<tr>` (sin el `<table>` envolvente) y pásalo el dato como `$p`. Después, en `proveedores/index.php`, reemplaza el cuerpo del `foreach` por:

```php
<?php foreach ($proveedores as $p): require __DIR__ . '/_row.php'; endforeach; ?>
```

**Paso 2** — Añade una acción `fila($id)` al controlador que devuelva sólo ese partial:

```php
public function fila(string $id = '0'): void
{
    $this->requireAuth();
    $p = $this->proveedores->findById((int) $id);
    if ($p === null) { http_response_code(404); return; }
    header('Content-Type: text/html; charset=utf-8');
    require ROOT . '/app/views/proveedores/_row.php';
}
```

**Paso 3** — Registra la URL en el mapa `realtimeRefreshUrl()` de `core/Controller.php`:

```php
$map = [
    'producto'  => '/producto/tarjeta/',
    'proveedor' => '/proveedor/fila/',   // ← nueva línea
];
```

Con esos tres cambios, cuando alguien edita un proveedor, el resto de usuarios ve la fila actualizándose en vivo en `/proveedor/index` sin recargar. El partial debe llevar `data-entity-id="proveedor:<id>"` para que el JS lo localice.

### Atributos HTML que el cliente realtime busca

El JS de [`public/js/realtime.js`](vidrios-inventory/public/js/realtime.js) localiza nodos por dos convenciones:

| Atributo | Para qué |
|---|---|
| `data-entity-id="<entidad>:<id>"` | Forma genérica: identifica un nodo como "vista de esta entidad con este id". Usada por todos los eventos `entity_changed`. |
| `data-<entidad>-id="<id>"` | Forma específica retrocompatible. `data-producto-id="42"` sigue funcionando para `stock_changed`. |
| `data-realtime-refresh-url="<url>"` | Si está presente en un nodo, el JS al recibir un evento que lo afecta hace fetch de esa URL y reemplaza el `outerHTML` con la respuesta. Sin este atributo, sólo parpadea ámbar. |
| `data-stock-display` | El JS sobreescribe el `textContent` con el nuevo stock al recibir `stock_changed`. |
| `data-stock-alert` | El JS togglea la clase `catalog-card__stock--alert` según el stock vs `data-stock-minimo`. |
| `data-stock-minimo="<num>"` | Umbral para reevaluar el estado crítico del producto en el cliente. |

---

## Flujos de uso

Esta sección explica, paso a paso y con palabras simples, las **cuatro acciones más comunes** del sistema. Las imágenes están en `docs/img/`; reemplázalas con tus capturas reales — los nombres ya están definidos para que solo tengas que sacar el screenshot y guardarlo con ese nombre.

### Convenciones para las imágenes

- Carpeta: [`docs/img/`](docs/img/)
- Formato sugerido: PNG (mejor compresión para UI con texto)
- Nombre: `<numero>-<modulo>-<paso>.png` (ej. `01-producto-catalogo.png`)
- Tamaño: ancho máx. 1400 px (más grande no aporta y pesa innecesario)

---

### Flujo 1 — Ingresar un producto nuevo

Esto es lo primero que harás antes de poder vender o registrar nada: dar de alta una pieza en el catálogo.

#### Paso 1. Entrar al catálogo

Desde el menú lateral izquierdo, abre **Inventario → Catálogo**. Verás todas las piezas activas en forma de tarjetas, con su código, nombre, precio y stock actual. Si el stock está por debajo del mínimo, la tarjeta se marca en rojo.

![Catálogo de productos](docs/img/01-producto-catalogo.png)
> **Imagen 01:** Pantalla `/producto/index` con las tarjetas del catálogo, el filtro de búsqueda y el botón "Nuevo producto" arriba a la derecha.

#### Paso 2. Abrir el formulario

Presiona el botón **Nuevo producto** (arriba a la derecha). Aparece un modal con el formulario dividido en bloques: Identidad, Clasificación, Unidad y dimensiones, Precios y stock, e Imagen.

![Modal Nuevo producto abierto](docs/img/02-producto-modal-vacio.png)
> **Imagen 02:** Modal recién abierto, todos los campos vacíos. Foco automático en el primer input.

#### Paso 3. Identidad

- **Código** (opcional). Si lo dejas vacío, el sistema te genera uno automático estilo `VID-00042`.
- **Nombre** (obligatorio). Texto libre, como "Plancha de vidrio templado 6 mm".
- **Descripción** (opcional). Para detalles adicionales.

#### Paso 4. Clasificación

- **Categoría** (obligatorio). Lista desplegable con las categorías existentes (Vidrio, Consumibles, Herrajes, Perfiles, Tornillos…).
- **Proveedor** (opcional). De dónde se compra.

#### Paso 5. Unidad de medida y dimensiones (¡dinámico!)

Aquí está la magia: al elegir la **Unidad** se muestran solo los campos dimensionales que tienen sentido para ese tipo de producto.

| Si eliges… | Verás estos campos |
|---|---|
| `m² (metros cuadrados)` | Ancho · Alto · Grosor (mm) |
| `Lámina` | Ancho · Alto · Grosor (mm) |
| `Unidad` | (sin dimensiones) |
| `Tubo` | Longitud · Diámetro (mm) |
| `Metro lineal` | Longitud (mm) |

![Form con unidad m² seleccionada](docs/img/03-producto-unidad-m2.png)
> **Imagen 03:** Form con unidad m² seleccionada — aparecen los 3 inputs Ancho, Alto y Grosor.

![Form con unidad tubo seleccionada](docs/img/04-producto-unidad-tubo.png)
> **Imagen 04:** Form con unidad "Tubo" seleccionada — desaparecen Ancho/Alto/Grosor y aparecen Longitud y Diámetro.

![Form con unidad unidad seleccionada](docs/img/05-producto-unidad-pieza.png)
> **Imagen 05:** Form con unidad "Unidad" seleccionada — no hay campos dimensionales, sólo el aviso "Esta unidad no requiere campos de dimensiones".

#### Paso 6. Precios y stock

- **Precio compra** y **Precio venta** en S/. El de venta no puede ser menor al de compra.
- **Stock inicial** (solo al crear). Cuántas unidades hay disponibles ahora mismo.
- **Stock mínimo**. Si el stock baja a este número o menos, la tarjeta se marca como crítica y aparece en alertas.

![Bloque precios y stock](docs/img/06-producto-precios.png)
> **Imagen 06:** Sección "Precios y stock" rellenada — precios y umbrales.

#### Paso 7. Imagen (opcional)

Arrastra una foto al cuadro de upload o haz click para seleccionarla. Acepta JPG, PNG o WebP hasta 4 MB. Si subes una y luego cambias de opinión, hay una × para quitarla.

#### Paso 8. Guardar

Click en **Crear producto**. El modal se cierra, aparece un mensaje verde "Producto creado correctamente" y la nueva tarjeta aparece en el catálogo.

![Catálogo con el producto recién creado](docs/img/07-producto-creado.png)
> **Imagen 07:** Catálogo mostrando la tarjeta del producto recién creado, con el flash de éxito visible arriba.

---

### Flujo 2 — Registrar una venta

Una venta es una salida de stock motivada por un cliente que compra. El sistema descuenta del stock automáticamente y registra el movimiento.

#### Paso 1. Entrar al formulario

Menú lateral → **Salidas → Ventas**. Llegas a `/movimiento/registrarVenta`.

![Form de venta vacío](docs/img/08-venta-form-vacio.png)
> **Imagen 08:** Formulario de venta recién cargado. El recuadro de stock dice "Selecciona un producto para ver el stock actual".

#### Paso 2. Elegir el producto

Selecciona el producto del desplegable. Al elegirlo:
- El recuadro de stock se llena: muestra código, nombre, **stock disponible**, unidad y stock mínimo. Si está crítico, se pinta en rojo.
- El input "Cantidad a retirar" cambia de comportamiento según la unidad:
  - `m²` o `metro lineal` → acepta decimales (`step="0.01"`, p. ej. `1.50`).
  - `unidad`, `tubo`, `lámina` → solo enteros (`step="1"`).
- El label del input cambia para mostrar la unidad entre paréntesis: "Cantidad a retirar * (m²)".

![Venta con producto seleccionado, paso 0.01](docs/img/09-venta-producto-m2.png)
> **Imagen 09:** Producto seleccionado (Plancha vidrio templado). Aparece "Stock disponible: 18 lámina (mín. 5)" y el input de cantidad lista para decimales.

#### Paso 3. Ingresar cantidad

Escribe cuánto vendes. Si vendes 1.50 m², escribe `1.50`. El sistema valida que tengas stock suficiente.

#### Paso 4. Datos del cliente (opcional para venta)

- **Cliente**: nombre o razón social.
- **Total de la venta** en S/.: lo que pagó el cliente.

#### Paso 5. Mermas, accidentes y retazos (solo aplica a ciertas unidades)

Si el producto seleccionado usa `m²`, `lámina` o `metro lineal`, aparece una sección extra **"Mermas y retazos generados"**. Aquí anotas todo lo que se rompió, se perdió o quedó como sobrante útil durante la operación.

Cada fila tiene:
- **Cantidad**: cuánto se afectó (en la misma unidad que el producto).
- **Motivo**:
  - `Merma` → material perdido. **Descuenta stock extra**.
  - `Accidente / rotura` → idem, pero etiquetado como accidente. **Descuenta stock extra**.
  - `Retazo aprovechable` → sobrante que puedes reutilizar. **NO descuenta stock**, solo se anota.
- **Detalle**: texto libre, ej. "esquina rota" o "sobrante irregular".

Puedes agregar varias filas con **+ Agregar fila**: típicamente una para la merma del corte y otra para el retazo aprovechable.

![Sección mermas con filas](docs/img/10-venta-mermas-retazos.png)
> **Imagen 10:** Sección "Mermas y retazos generados" desplegada, con dos filas: una merma de 0.10 m² ("rotura esquina") y un retazo aprovechable de 1 ("50×30").

#### Paso 6. Confirmar

Click en **Registrar venta**. El sistema, dentro de una transacción atómica:
1. Crea un movimiento de salida con tipo `salida` y motivo `venta` (descuenta la cantidad principal).
2. Por cada fila de merma/accidente, crea un movimiento adicional (descuenta stock extra).
3. Por cada fila de retazo, inserta una fila en la tabla `retazos` (no toca el stock).

Si algo falla, hace rollback completo. Te redirige al historial filtrado por ventas.

![Historial con la venta nueva](docs/img/11-venta-historial.png)
> **Imagen 11:** `/movimiento/historial?motivo=venta` mostrando arriba la venta recién registrada (timestamp, código, cantidad y observación).

---

### Flujo 3 — Crear un encargo

Un encargo es una **reserva por adelantado**: el cliente pide algo para entregar en una fecha futura. Al crear el encargo el stock ya se descuenta (queda apartado). Al entregar marcas el encargo y, si hubo mermas o retazos durante el corte/entrega, las anotas.

#### Paso 1. Listado de encargos

Menú lateral → **Salidas → Encargos**. Verás todos los encargos agrupados con su estado: `pendiente`, `entregado` o `cancelado`.

![Listado de encargos](docs/img/12-encargo-listado.png)
> **Imagen 12:** Pantalla `/encargo/index` con tarjetas de encargos y el filtro por estado.

#### Paso 2. Nuevo encargo

Botón **Nuevo encargo**. Te lleva a `/encargo/crear`.

#### Paso 3. Datos del cliente

- **Cliente** *: nombre del comprador.
- **Teléfono**: para contacto.
- **Lugar de entrega**: dirección o referencia.
- **Fecha de entrega** *: cuándo se entrega (input tipo date).
- **Detalles**: notas libres.

![Top del form de encargo](docs/img/13-encargo-form-datos.png)
> **Imagen 13:** Cabecera del formulario de encargo con los datos del cliente.

#### Paso 4. Productos del encargo

Click **Añadir producto**. Aparece una fila con:
- **Producto** (select): al elegirlo se autocompleta el precio si está definido.
- **Cantidad**: cuántas unidades reservas.
- **Precio unitario**: editable si quieres pactar uno distinto.
- **Subtotal**: calculado en vivo.

Puedes añadir tantas filas como productos lleve el encargo. Abajo aparece el **Total** que se recalcula al cambiar cualquier cantidad o precio.

![Items del encargo](docs/img/14-encargo-items.png)
> **Imagen 14:** Tabla de items del encargo con dos productos añadidos y el total visible al pie.

#### Paso 5. Guardar el encargo

Click **Crear encargo**. El sistema, dentro de una transacción:
1. Inserta el encargo con estado `pendiente`.
2. Por cada producto, inserta una fila en `encargo_items`.
3. Por cada producto, crea un movimiento de salida con motivo `encargo` (descuenta el stock).

Si un producto no tiene stock suficiente, se hace rollback y muestra el error.

#### Paso 6. Detalle del encargo

Llegas a `/encargo/detalle/<id>` con un resumen del encargo, los items y los totales. Mientras esté `pendiente` puedes Editar, Cancelar o Marcar como entregado.

![Detalle del encargo pendiente](docs/img/15-encargo-detalle.png)
> **Imagen 15:** Pantalla de detalle del encargo recién creado, con sus 3 acciones (Editar / Cancelar / Marcar entregado).

#### Paso 7. Marcar como entregado

Click **Marcar entregado**. Se abre un modal grande con un fieldset por cada producto del encargo. Aquí anotas, **al momento de entregar**, todo lo que no fue limpio:

Por cada producto cuya unidad lo permite (m², lámina, metro lineal):
- Una fila inicial con: cantidad, motivo (Merma / Accidente / Retazo), y los inputs de medidas según la unidad (Ancho/Alto en cm para m²/lámina, Longitud en cm para metro lineal).
- Botón **+ Agregar otra fila para este producto** para anotar varias afectaciones del mismo producto (ej. una merma + un retazo en la misma plancha).

Para productos cuya unidad NO admite mermas (Unidad, Tubo) sólo aparece un texto informativo.

![Modal de entrega abierto](docs/img/16-encargo-entregar-modal.png)
> **Imagen 16:** Modal "Entregar encargo" con un producto en m² y otro en unidad. El primero tiene los 5 campos (cantidad, motivo, ancho, alto, ×); el segundo solo dice "La unidad unidad no admite mermas ni retazos".

#### Paso 8. Confirmar entrega

Click **Confirmar entrega**. El sistema:
1. Por cada fila con motivo `merma` o `accidente` → crea un movimiento de salida adicional (descuenta stock extra).
2. Por cada fila con motivo `retazo` → inserta un registro en la tabla `retazos` (con sus medidas en cm). NO descuenta stock.
3. Cambia el estado del encargo a `entregado`.

![Detalle del encargo entregado](docs/img/17-encargo-entregado.png)
> **Imagen 17:** Detalle del encargo ya marcado como entregado, con el link a "Inventario · Retazos" en la zona inferior.

---

### Flujo 4 — Generar y administrar un retazo

Los retazos **no se crean por su cuenta** — se generan como subproducto de una venta o de la entrega de un encargo, cuando seleccionas el motivo "Retazo aprovechable" en alguna de esas dos pantallas.

#### Forma A: durante una venta

Sigue el [Flujo 2 — Registrar una venta](#flujo-2--registrar-una-venta) hasta el **Paso 5**. En vez de elegir motivo "Merma", elige **"Retazo aprovechable (no descuenta stock)"** y pon en "Detalle" las dimensiones tipo `50×30 cm`.

![Fila de retazo en venta](docs/img/18-retazo-desde-venta.png)
> **Imagen 18:** Fila con motivo "Retazo aprovechable" en el form de venta, con su detalle textual.

#### Forma B: durante la entrega de un encargo

Sigue el [Flujo 3 — Crear un encargo](#flujo-3--crear-un-encargo) hasta el **Paso 7**. En el modal de entrega, por cada producto que admite mermas, elige motivo **"Retazo aprovechable"** y rellena los inputs dimensionales (Ancho y Alto en cm, o Longitud).

![Fila de retazo en entrega de encargo](docs/img/19-retazo-desde-encargo.png)
> **Imagen 19:** Fila con motivo "Retazo aprovechable" en el modal de entregar encargo, con los inputs separados de Ancho y Alto en cm.

#### Consultar los retazos disponibles

Menú lateral → **Inventario → Retazos**. Llegas a `/retazo/index`, listado completo con filtros: producto, origen (`salida` o `encargo`), estado (`disponible` o `aprovechado`) y rango de fechas.

Cada fila muestra: fecha, producto, categoría, cantidad + unidad, medidas en cm, origen + id de la operación, estado y acciones.

![Listado de retazos](docs/img/20-retazo-listado.png)
> **Imagen 20:** Pantalla `/retazo/index` con varios retazos listados, filtros arriba y acciones (✓ aprovechar · 🗑️ eliminar) por fila.

#### Marcar un retazo como aprovechado

Cuando reutilizas un retazo en un corte futuro, click en el icono **✓** de su fila. El estado pasa de "Disponible" a "Aprovechado" y la fila se atenúa visualmente (para no confundirla con un retazo disponible). Si te equivocaste, el mismo botón lo vuelve a disponible.

![Retazo marcado como aprovechado](docs/img/21-retazo-aprovechado.png)
> **Imagen 21:** Tabla con un retazo en estado "Aprovechado" (badge gris, fila atenuada) y otros en "Disponible".

#### Eliminar un retazo

Si registraste un retazo por error, click en el icono **🗑️** de su fila. Pide confirmación. Solo el rol **admin** puede borrar.

---

## Resumen del comportamiento por unidad

Esta tabla es la "letra chica" que rige toda la lógica anterior:

| Unidad | Cantidad acepta decimales | Muestra sección mermas/retazos | Campos dimensionales del retazo |
|---|---|---|---|
| `m²` | ✅ Sí (0.01) | ✅ Sí | Ancho · Alto (cm) |
| `Lámina` | ❌ No (entero) | ✅ Sí | Ancho · Alto (cm) |
| `Unidad` | ❌ No | ❌ No | — |
| `Tubo` | ❌ No | ❌ No | — |
| `Metro lineal` | ✅ Sí (0.01) | ✅ Sí | Longitud (cm) |

---

## Exportación a CSV

Cada módulo incluye un botón **↓ Exportar CSV** que descarga los datos visibles en pantalla respetando los filtros activos. Los archivos incluyen BOM UTF-8 para compatibilidad con Microsoft Excel.

---

## Capturas de pantalla

> *(Agrega capturas de pantalla aquí para darle más presencia al proyecto)*

---

## Licencia

Este proyecto es de uso interno para **Vidrios Centro Puno E.I.R.L.**
