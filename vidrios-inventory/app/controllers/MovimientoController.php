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
        $tipo   = trim((string) ($_GET['tipo']   ?? ''));
        $motivo = trim((string) ($_GET['motivo'] ?? ''));
        $desde  = trim((string) ($_GET['desde']  ?? ''));
        $hasta  = trim((string) ($_GET['hasta']  ?? ''));

        $total     = $this->movimientos->contarHistorial(
            $productoId, $tipo ?: null, $desde ?: null, $hasta ?: null, $motivo ?: null
        );
        $paginator = new Paginator($total, 10);

        $extras = array_filter([
            'producto' => $productoId,
            'tipo'     => $tipo,
            'motivo'   => $motivo,
            'desde'    => $desde,
            'hasta'    => $hasta,
        ], static fn($v) => $v !== null && $v !== '');

        $this->render('movimientos/index', [
            'movimientos' => $this->movimientos->historial(
                $productoId, $tipo ?: null, $desde ?: null, $hasta ?: null,
                $paginator->perPage, $paginator->offset, $motivo ?: null
            ),
            'productos'   => $this->productos->findAll('nombre ASC'),
            'filtro'      => compact('productoId', 'tipo', 'motivo', 'desde', 'hasta'),
            'paginator'   => $paginator,
            'extrasUrl'   => $extras,
            'titulo'      => 'Historial de movimientos',
        ]);
    }

    public function exportar(): void
    {
        $this->requireAuth();
        $productoId = isset($_GET['producto']) && $_GET['producto'] !== '' ? (int) $_GET['producto'] : null;
        $tipo   = trim((string) ($_GET['tipo']   ?? ''));
        $motivo = trim((string) ($_GET['motivo'] ?? ''));
        $desde  = trim((string) ($_GET['desde']  ?? ''));
        $hasta  = trim((string) ($_GET['hasta']  ?? ''));
        $filas = array_map(fn($m) => [
            $m['created_at'],
            $m['producto_codigo'],
            $m['producto_nombre'],
            $m['tipo'],
            $m['motivo'] ?? '',
            (int) $m['cantidad'],
            (int) $m['stock_anterior'],
            (int) $m['stock_nuevo'],
            $m['usuario_nombre']   ?? '',
            $m['proveedor_nombre'] ?? '',
            $m['cliente']          ?? '',
            $m['total']            ?? '',
            $m['fecha_entrega']    ?? '',
            $m['observacion']      ?? '',
        ], $this->movimientos->historial(
            $productoId, $tipo ?: null, $desde ?: null, $hasta ?: null, null, 0, $motivo ?: null
        ));
        Exporter::csv('movimientos', [
            'Fecha', 'Código producto', 'Producto', 'Tipo', 'Motivo', 'Cantidad',
            'Stock anterior', 'Stock nuevo', 'Usuario', 'Proveedor',
            'Cliente', 'Total', 'Fecha entrega', 'Observación',
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

    public function registrarVenta(): void     { $this->procesarSalida(Movimiento::MOTIVO_VENTA); }
    public function registrarAccidente(): void { $this->procesarSalida(Movimiento::MOTIVO_ACCIDENTE); }
    public function registrarMerma(): void     { $this->procesarSalida(Movimiento::MOTIVO_MERMA); }

    /**
     * Encargos ahora viven en su propio módulo (con items múltiples).
     * Redirigimos por compatibilidad con bookmarks o links viejos.
     */
    public function registrarEncargo(): void
    {
        $this->requireAuth();
        $this->redirect('/encargo/crear');
    }

    /**
     * Handler común para los 4 tipos de salida. Cada motivo expone campos distintos
     * en la vista; aquí extraemos sólo los relevantes y validamos.
     */
    private function procesarSalida(string $motivo): void
    {
        $this->requireAuth();

        $errores = [];
        $form = [
            'producto_id'   => 0,
            'cantidad'      => 1,
            'cliente'       => '',
            'total'         => '',
            'fecha_entrega' => '',
            'evidencia'     => '',
            'observacion'   => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form = [
                'producto_id'   => (int) ($_POST['producto_id'] ?? 0),
                'cantidad'      => (int) ($_POST['cantidad']    ?? 0),
                'cliente'       => trim((string) ($_POST['cliente']      ?? '')),
                'total'         => trim((string) ($_POST['total']        ?? '')),
                'fecha_entrega' => trim((string) ($_POST['fecha_entrega'] ?? '')),
                'evidencia'     => trim((string) ($_POST['evidencia']    ?? '')),
                'observacion'   => trim((string) ($_POST['observacion']  ?? '')),
            ];
            $errores = $this->validarSalida($motivo, $form);

            if ($errores === []) {
                try {
                    $this->movimientos->registrar(
                        productoId: $form['producto_id'],
                        tipo: Movimiento::TIPO_SALIDA,
                        cantidad: $form['cantidad'],
                        usuarioId: (int) $_SESSION['usuario']['id'],
                        observacion: $form['observacion'] !== '' ? $form['observacion'] : null,
                        proveedorId: null,
                        motivo: $motivo,
                        cliente: $form['cliente'] !== '' ? $form['cliente'] : null,
                        total: $form['total'] !== '' ? (float) $form['total'] : null,
                        fechaEntrega: $form['fecha_entrega'] !== '' ? $form['fecha_entrega'] : null,
                        evidencia: $form['evidencia'] !== '' ? $form['evidencia'] : null,
                    );
                    $this->audit('salida', 'movimiento', (string) $form['producto_id'],
                        "Salida ({$motivo}) de {$form['cantidad']} unidades.");
                    $this->setFlash('success', $this->mensajeExito($motivo, $form['cantidad']));
                    $this->redirect('/movimiento/historial?motivo=' . $motivo);
                } catch (Throwable $e) {
                    $errores['general'] = $e->getMessage();
                }
            }
        }

        $this->render('movimientos/' . $motivo, [
            'motivo'     => $motivo,
            'titulo'     => $this->tituloSalida($motivo),
            'productos'  => $this->productos->findAll('nombre ASC'),
            'form'       => $form,
            'errores'    => $errores,
        ]);
    }

    /** @return array<string, string> */
    private function validarSalida(string $motivo, array $f): array
    {
        $err = [];
        if ((int) $f['producto_id'] <= 0) {
            $err['producto_id'] = 'Selecciona un producto.';
        }
        if ((int) $f['cantidad'] <= 0) {
            $err['cantidad'] = 'La cantidad debe ser mayor a cero.';
        }
        // Validaciones específicas por motivo
        if ($motivo === Movimiento::MOTIVO_ENCARGO) {
            if ($f['cliente'] === '') {
                $err['cliente'] = 'El nombre del cliente es obligatorio para encargos.';
            }
            if ($f['fecha_entrega'] === '') {
                $err['fecha_entrega'] = 'La fecha de entrega es obligatoria.';
            }
        }
        if ($motivo === Movimiento::MOTIVO_VENTA) {
            if ($f['total'] !== '' && (!is_numeric($f['total']) || (float) $f['total'] < 0)) {
                $err['total'] = 'El total debe ser un número no negativo.';
            }
        }
        if (in_array($motivo, [Movimiento::MOTIVO_ACCIDENTE, Movimiento::MOTIVO_MERMA], true)) {
            if ($f['evidencia'] === '') {
                $err['evidencia'] = 'Describe brevemente qué ocurrió (evidencia).';
            }
        }
        return $err;
    }

    private function tituloSalida(string $motivo): string
    {
        return match ($motivo) {
            Movimiento::MOTIVO_VENTA     => 'Registrar venta',
            Movimiento::MOTIVO_ENCARGO   => 'Registrar encargo',
            Movimiento::MOTIVO_ACCIDENTE => 'Registrar accidente',
            Movimiento::MOTIVO_MERMA     => 'Registrar merma',
            default                       => 'Registrar salida',
        };
    }

    private function mensajeExito(string $motivo, int $cantidad): string
    {
        $verbo = match ($motivo) {
            Movimiento::MOTIVO_VENTA     => 'Venta registrada',
            Movimiento::MOTIVO_ENCARGO   => 'Encargo registrado',
            Movimiento::MOTIVO_ACCIDENTE => 'Accidente registrado',
            Movimiento::MOTIVO_MERMA     => 'Merma registrada',
            default                       => 'Salida registrada',
        };
        return "{$verbo} (-{$cantidad} de stock).";
    }
}
