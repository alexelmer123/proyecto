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
| Retazos | `/retazo` | Sobrantes aprovechables (no descuentan stock) |

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

## Flujo técnico de una petición

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
