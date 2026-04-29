<?php
declare(strict_types=1);

final class ProductoController extends Controller
{
    private Producto $productos;
    private Categoria $categorias;
    private Proveedor $proveedores;

    public function __construct()
    {
        $this->productos   = new Producto();
        $this->categorias  = new Categoria();
        $this->proveedores = new Proveedor();
    }

    public function index(): void
    {
        $this->requireAuth();

        $q   = trim((string) ($_GET['q']   ?? ''));
        $cat = isset($_GET['categoria']) && $_GET['categoria'] !== '' ? (int) $_GET['categoria'] : null;

        $total     = $this->productos->contarBusqueda($q, $cat);
        $paginator = new Paginator($total, 10);

        $extras = [];
        if ($q !== '')        $extras['q']         = $q;
        if ($cat !== null)    $extras['categoria'] = $cat;

        $this->render('productos/index', [
            'productos'   => $this->productos->buscar($q, $cat, $paginator->perPage, $paginator->offset),
            'categorias'  => $this->categorias->activas(),
            'q'           => $q,
            'categoriaId' => $cat,
            'paginator'   => $paginator,
            'extrasUrl'   => $extras,
            'titulo'      => 'Catálogo de productos',
        ]);
    }

    public function crear(): void
    {
        $this->requireAuth();

        $errores = [];
        $form = $this->defaultProductoForm();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form = $this->extraerForm($_POST);
            $errores = $this->validar($form);

            if (empty($form['codigo'])) {
                $form['codigo'] = $this->productos->generarCodigoUnico();
            } elseif ($this->productos->existsBy('codigo', $form['codigo'])) {
                $errores['codigo'] = 'Ya existe un producto con ese código.';
            }

            $imagen = $this->procesarImagen('imagen');
            if (is_array($imagen) && isset($imagen['error'])) {
                $errores['imagen'] = $imagen['error'];
            }

            if ($errores === []) {
                $form['imagen'] = is_string($imagen) ? $imagen : null;
                $form['estado'] = 1;
                $form['created_at'] = date('Y-m-d H:i:s');
                $newId = $this->productos->create($form);
                $this->audit('crear', 'producto', (string) $newId, "Producto «{$form['nombre']}» creado.");
                $this->setFlash('success', "Producto «{$form['nombre']}» creado correctamente.");
                $this->redirect('/producto/index');
            }
        }

        $this->render('productos/crear', [
            'form'         => $form,
            'errores'      => $errores,
            'categorias'   => $this->categorias->activas(),
            'proveedores'  => $this->proveedores->activos(),
            'titulo'       => 'Nuevo producto',
        ]);
    }

    public function editar(string $id = '0'): void
    {
        $this->requireAuth();
        $id = (int) $id;
        $producto = $this->productos->findById($id);
        if ($producto === null) {
            $this->setFlash('error', 'Producto no encontrado.');
            $this->redirect('/producto/index');
        }

        $errores = [];
        $form = $producto;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form = $this->extraerForm($_POST);
            $form['id'] = $id;
            $errores = $this->validar($form);

            if (!empty($form['codigo']) && $this->productos->existsBy('codigo', $form['codigo'], $id)) {
                $errores['codigo'] = 'Ya existe otro producto con ese código.';
            }

            $imagen = $this->procesarImagen('imagen');
            if (is_array($imagen) && isset($imagen['error'])) {
                $errores['imagen'] = $imagen['error'];
            }

            if ($errores === []) {
                $datos = $form;
                unset($datos['id'], $datos['stock_actual']); // stock no se modifica desde aquí
                if (is_string($imagen)) {
                    $datos['imagen'] = $imagen;
                } else {
                    unset($datos['imagen']);
                }
                $this->productos->update($id, $datos);
                $this->audit('editar', 'producto', (string) $id, "Producto «{$form['nombre']}» editado.");
                $this->setFlash('success', 'Producto actualizado.');
                $this->redirect('/producto/index');
            }
        }

        $this->render('productos/editar', [
            'form'         => $form,
            'errores'      => $errores,
            'categorias'   => $this->categorias->activas(),
            'proveedores'  => $this->proveedores->activos(),
            'titulo'       => 'Editar producto',
        ]);
    }

    public function eliminar(string $id = '0'): void
    {
        $this->requireAuth();
        $id = (int) $id;
        if ($id <= 0 || $this->productos->findById($id) === null) {
            $this->setFlash('error', 'Producto no encontrado.');
            $this->redirect('/producto/index');
        }
        $this->productos->softDelete($id);
        $this->audit('eliminar', 'producto', (string) $id, 'Producto archivado.');
        $this->setFlash('success', 'Producto archivado (soft delete).');
        $this->redirect('/producto/index');
    }

    public function ajustarStock(string $id = '0'): void
    {
        $this->requireAuth();
        $id = (int) $id;
        $producto = $this->productos->findById($id);
        if ($producto === null) {
            $this->setFlash('error', 'Producto no encontrado.');
            $this->redirect('/producto/index');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nuevo = (int) ($_POST['stock_nuevo'] ?? -1);
            $obs   = trim((string) ($_POST['observacion'] ?? ''));
            if ($nuevo < 0) {
                $this->setFlash('error', 'El nuevo stock debe ser cero o positivo.');
            } else {
                try {
                    $movs = new Movimiento();
                    $movs->registrar(
                        productoId: $id,
                        tipo: Movimiento::TIPO_AJUSTE,
                        cantidad: $nuevo,
                        usuarioId: (int) $_SESSION['usuario']['id'],
                        observacion: $obs !== '' ? $obs : 'Ajuste manual'
                    );
                    $this->audit('ajustar', 'producto', (string) $id, "Stock ajustado a {$nuevo}.");
                    $this->setFlash('success', 'Stock ajustado y movimiento registrado.');
                    $this->redirect('/producto/index');
                } catch (Throwable $e) {
                    $this->setFlash('error', $e->getMessage());
                }
            }
        }

        $this->render('productos/ajustar_stock', [
            'producto' => $producto,
            'titulo'   => 'Ajustar stock',
        ]);
    }

    public function exportar(): void
    {
        $this->requireAuth();
        $q   = trim((string) ($_GET['q']        ?? ''));
        $cat = isset($_GET['categoria']) && $_GET['categoria'] !== '' ? (int) $_GET['categoria'] : null;
        $filas = array_map(fn($p) => [
            $p['codigo'],
            $p['nombre'],
            $p['descripcion'] ?? '',
            $p['categoria_nombre']  ?? '',
            $p['proveedor_nombre']  ?? '',
            $p['unidad'],
            $p['ancho']  ?? '',
            $p['alto']   ?? '',
            $p['grosor'] ?? '',
            number_format((float) $p['precio_compra'], 2, '.', ''),
            number_format((float) $p['precio_venta'],  2, '.', ''),
            (int) $p['stock_actual'],
            (int) $p['stock_minimo'],
            (int) $p['estado'] === 1 ? 'Activo' : 'Inactivo',
        ], $this->productos->buscar($q, $cat));
        Exporter::csv('productos', [
            'Código', 'Nombre', 'Descripción', 'Categoría', 'Proveedor',
            'Unidad', 'Ancho (mm)', 'Alto (mm)', 'Grosor (mm)',
            'Precio compra', 'Precio venta', 'Stock actual', 'Stock mínimo', 'Estado',
        ], $filas);
    }

    /** Endpoint JSON usado por app.js al cambiar el selector de producto. */
    public function stockActual(string $id = '0'): void
    {
        $this->requireAuth();
        header('Content-Type: application/json; charset=utf-8');
        $producto = $this->productos->findById((int) $id);
        if ($producto === null) {
            http_response_code(404);
            echo json_encode(['ok' => false, 'msg' => 'No encontrado']);
            return;
        }
        echo json_encode([
            'ok'           => true,
            'id'           => (int) $producto['id'],
            'codigo'       => $producto['codigo'],
            'nombre'       => $producto['nombre'],
            'stock_actual' => (int) $producto['stock_actual'],
            'stock_minimo' => (int) $producto['stock_minimo'],
            'unidad'       => $producto['unidad'] ?? 'u',
        ]);
    }

    // ---------- Helpers privados ----------

    private function defaultProductoForm(): array
    {
        return [
            'codigo'        => '',
            'nombre'        => '',
            'descripcion'   => '',
            'categoria_id'  => null,
            'proveedor_id'  => null,
            'unidad'        => 'm²',
            'ancho'         => '',
            'alto'          => '',
            'grosor'        => '',
            'precio_compra' => '',
            'precio_venta'  => '',
            'stock_actual'  => 0,
            'stock_minimo'  => 1,
            'imagen'        => null,
        ];
    }

    private function extraerForm(array $src): array
    {
        return [
            'codigo'        => trim((string) ($src['codigo']        ?? '')),
            'nombre'        => trim((string) ($src['nombre']        ?? '')),
            'descripcion'   => trim((string) ($src['descripcion']   ?? '')),
            'categoria_id'  => isset($src['categoria_id']) && $src['categoria_id'] !== ''
                                  ? (int) $src['categoria_id'] : null,
            'proveedor_id'  => isset($src['proveedor_id']) && $src['proveedor_id'] !== ''
                                  ? (int) $src['proveedor_id'] : null,
            'unidad'        => trim((string) ($src['unidad']        ?? 'm²')),
            'ancho'         => isset($src['ancho'])  && $src['ancho']  !== '' ? (float) $src['ancho']  : null,
            'alto'          => isset($src['alto'])   && $src['alto']   !== '' ? (float) $src['alto']   : null,
            'grosor'        => isset($src['grosor']) && $src['grosor'] !== '' ? (float) $src['grosor'] : null,
            'precio_compra' => (float) ($src['precio_compra'] ?? 0),
            'precio_venta'  => (float) ($src['precio_venta']  ?? 0),
            'stock_actual'  => (int)   ($src['stock_actual']  ?? 0),
            'stock_minimo'  => (int)   ($src['stock_minimo']  ?? 1),
        ];
    }

    /** Devuelve los errores indexados por campo. */
    private function validar(array $f): array
    {
        $err = [];
        if ($f['nombre'] === '') {
            $err['nombre'] = 'El nombre es obligatorio.';
        }
        if (empty($f['categoria_id'])) {
            $err['categoria_id'] = 'Selecciona una categoría.';
        }
        if (!is_numeric($f['precio_compra']) || $f['precio_compra'] < 0) {
            $err['precio_compra'] = 'El precio de compra debe ser numérico y no negativo.';
        }
        if (!is_numeric($f['precio_venta']) || $f['precio_venta'] < 0) {
            $err['precio_venta'] = 'El precio de venta debe ser numérico y no negativo.';
        }
        if (is_numeric($f['precio_venta']) && is_numeric($f['precio_compra'])
            && (float) $f['precio_venta'] < (float) $f['precio_compra']) {
            $err['precio_venta'] = 'El precio de venta no puede ser menor al de compra.';
        }
        if ((int) $f['stock_minimo'] <= 0) {
            $err['stock_minimo'] = 'El stock mínimo debe ser mayor a cero.';
        }
        foreach (['ancho', 'alto', 'grosor'] as $dim) {
            if ($f[$dim] !== null && (!is_numeric($f[$dim]) || (float) $f[$dim] <= 0)) {
                $err[$dim] = ucfirst($dim) . ' debe ser un número positivo.';
            }
        }
        return $err;
    }

    /**
     * Devuelve la ruta relativa de la imagen subida, o un array con error,
     * o null si no se envió ninguna imagen.
     */
    private function procesarImagen(string $field): string|array|null
    {
        if (!isset($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        if ($_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
            return ['error' => 'Error al subir la imagen.'];
        }
        $tipos = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime  = $finfo->file($_FILES[$field]['tmp_name']);
        if (!isset($tipos[$mime])) {
            return ['error' => 'Formato no permitido (usa JPG, PNG o WebP).'];
        }
        if ($_FILES[$field]['size'] > 4 * 1024 * 1024) {
            return ['error' => 'La imagen supera los 4 MB.'];
        }
        if (!is_dir(UPLOAD_DIR) && !mkdir(UPLOAD_DIR, 0775, true) && !is_dir(UPLOAD_DIR)) {
            return ['error' => 'No fue posible crear el directorio de imágenes.'];
        }
        $nombre = bin2hex(random_bytes(8)) . '.' . $tipos[$mime];
        $destino = UPLOAD_DIR . '/' . $nombre;
        if (!move_uploaded_file($_FILES[$field]['tmp_name'], $destino)) {
            return ['error' => 'No se pudo guardar la imagen.'];
        }
        return UPLOAD_URL . '/' . $nombre;
    }
}
