<?php
declare(strict_types=1);

final class MovimientoController extends Controller
{
    private Movimiento $movimientos;
    private Producto $productos;
    private Proveedor $proveedores;

    public function __construct()
    {
        $this->movimientos = new Movimiento();
        $this->productos   = new Producto();
        $this->proveedores = new Proveedor();
    }

    public function index(): void
    {
        $this->historial();
    }

    public function historial(): void
    {
        $this->requireAuth();

        $productoId = isset($_GET['producto']) && $_GET['producto'] !== '' ? (int) $_GET['producto'] : null;
        $tipo  = trim((string) ($_GET['tipo']  ?? ''));
        $desde = trim((string) ($_GET['desde'] ?? ''));
        $hasta = trim((string) ($_GET['hasta'] ?? ''));

        $total     = $this->movimientos->contarHistorial($productoId, $tipo ?: null, $desde ?: null, $hasta ?: null);
        $paginator = new Paginator($total, 10);

        $extras = array_filter([
            'producto' => $productoId,
            'tipo'     => $tipo,
            'desde'    => $desde,
            'hasta'    => $hasta,
        ], static fn($v) => $v !== null && $v !== '');

        $this->render('movimientos/index', [
            'movimientos' => $this->movimientos->historial(
                $productoId, $tipo ?: null, $desde ?: null, $hasta ?: null,
                $paginator->perPage, $paginator->offset
            ),
            'productos'   => $this->productos->findAll('nombre ASC'),
            'filtro'      => compact('productoId', 'tipo', 'desde', 'hasta'),
            'paginator'   => $paginator,
            'extrasUrl'   => $extras,
            'titulo'      => 'Historial de movimientos',
        ]);
    }

    public function exportar(): void
    {
        $this->requireAuth();
        $productoId = isset($_GET['producto']) && $_GET['producto'] !== '' ? (int) $_GET['producto'] : null;
        $tipo  = trim((string) ($_GET['tipo']  ?? ''));
        $desde = trim((string) ($_GET['desde'] ?? ''));
        $hasta = trim((string) ($_GET['hasta'] ?? ''));
        $filas = array_map(fn($m) => [
            $m['created_at'],
            $m['producto_codigo'],
            $m['producto_nombre'],
            $m['tipo'],
            (int) $m['cantidad'],
            (int) $m['stock_anterior'],
            (int) $m['stock_nuevo'],
            $m['usuario_nombre']   ?? '',
            $m['proveedor_nombre'] ?? '',
            $m['observacion']      ?? '',
        ], $this->movimientos->historial(
            $productoId, $tipo ?: null, $desde ?: null, $hasta ?: null
        ));
        Exporter::csv('movimientos', [
            'Fecha', 'Código producto', 'Producto', 'Tipo', 'Cantidad',
            'Stock anterior', 'Stock nuevo', 'Usuario', 'Proveedor', 'Observación',
        ], $filas);
    }

    public function registrarEntrada(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productoId  = (int) ($_POST['producto_id'] ?? 0);
            $cantidad    = (int) ($_POST['cantidad']    ?? 0);
            $proveedorId = isset($_POST['proveedor_id']) && $_POST['proveedor_id'] !== ''
                ? (int) $_POST['proveedor_id'] : null;
            $obs = trim((string) ($_POST['observacion'] ?? ''));

            try {
                $this->movimientos->registrar(
                    productoId: $productoId,
                    tipo: Movimiento::TIPO_ENTRADA,
                    cantidad: $cantidad,
                    usuarioId: (int) $_SESSION['usuario']['id'],
                    observacion: $obs !== '' ? $obs : null,
                    proveedorId: $proveedorId
                );
                $this->audit('entrada', 'movimiento', (string) $productoId, "Entrada de {$cantidad} unidades.");
                $this->setFlash('success', "Entrada registrada (+{$cantidad}).");
                $this->redirect('/movimiento/historial');
            } catch (Throwable $e) {
                $this->setFlash('error', $e->getMessage());
            }
        }

        $this->render('movimientos/entrada', [
            'productos'   => $this->productos->findAll('nombre ASC'),
            'proveedores' => $this->proveedores->activos(),
            'titulo'      => 'Registrar entrada',
        ]);
    }

    public function registrarSalida(): void
    {
        $this->requireAuth();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $productoId = (int) ($_POST['producto_id'] ?? 0);
            $cantidad   = (int) ($_POST['cantidad']    ?? 0);
            $obs        = trim((string) ($_POST['observacion'] ?? ''));

            $producto = $this->productos->findById($productoId);
            if ($producto === null) {
                $this->setFlash('error', 'Producto no encontrado.');
            } elseif ($cantidad <= 0) {
                $this->setFlash('error', 'La cantidad debe ser mayor a cero.');
            } elseif ($cantidad > (int) $producto['stock_actual']) {
                $this->setFlash(
                    'error',
                    "Stock insuficiente. Disponible: {$producto['stock_actual']}, solicitado: {$cantidad}."
                );
            } else {
                try {
                    $this->movimientos->registrar(
                        productoId: $productoId,
                        tipo: Movimiento::TIPO_SALIDA,
                        cantidad: $cantidad,
                        usuarioId: (int) $_SESSION['usuario']['id'],
                        observacion: $obs !== '' ? $obs : null,
                    );
                    $this->audit('salida', 'movimiento', (string) $productoId, "Salida de {$cantidad} unidades.");
                    $this->setFlash('success', "Salida registrada (-{$cantidad}).");
                    $this->redirect('/movimiento/historial');
                } catch (Throwable $e) {
                    $this->setFlash('error', $e->getMessage());
                }
            }
        }

        $this->render('movimientos/salida', [
            'productos' => $this->productos->findAll('nombre ASC'),
            'titulo'    => 'Registrar salida',
        ]);
    }
}
