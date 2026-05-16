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
            number_format((float) $m['cantidad'],       2, '.', ''),
            number_format((float) $m['stock_anterior'], 2, '.', ''),
            number_format((float) $m['stock_nuevo'],    2, '.', ''),
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
            $cantidad    = is_numeric($_POST['cantidad'] ?? null) ? (float) $_POST['cantidad'] : 0.0;
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
                $cantTxt = $this->formatCantidad($cantidad);
                $this->audit('entrada', 'movimiento', (string) $productoId, "Entrada de {$cantTxt} unidades.");
                $this->setFlash('success', "Entrada registrada (+{$cantTxt}).");
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
            'mermas'        => [],
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form = [
                'producto_id'   => (int) ($_POST['producto_id'] ?? 0),
                'cantidad'      => is_numeric($_POST['cantidad'] ?? null) ? (float) $_POST['cantidad'] : 0.0,
                'cliente'       => trim((string) ($_POST['cliente']      ?? '')),
                'total'         => trim((string) ($_POST['total']        ?? '')),
                'fecha_entrega' => trim((string) ($_POST['fecha_entrega'] ?? '')),
                'evidencia'     => trim((string) ($_POST['evidencia']    ?? '')),
                'observacion'   => trim((string) ($_POST['observacion']  ?? '')),
                'mermas'        => $this->normalizarMermasPost($_POST['mermas'] ?? []),
            ];
            $errores = $this->validarSalida($motivo, $form);

            if ($errores === []) {
                $db = Database::getInstance();
                try {
                    $db->beginTransaction();

                    // Movimiento principal de salida.
                    $movId = $this->movimientos->registrar(
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

                    // Mermas/accidentes: cada fila se registra como salida adicional
                    // ligada al producto. Los retazos se persisten en la tabla
                    // `retazos` (no descuentan stock).
                    $mermasRegistradas = 0.0;
                    $retazosCount      = 0;
                    $retazoModel = new Retazo();
                    foreach ($form['mermas'] as $m) {
                        if ($m['motivo'] === Movimiento::MOTIVO_RETAZO) {
                            $retazoModel->registrar(
                                productoId: $form['producto_id'],
                                cantidad: (float) $m['cantidad'],
                                medidas: [], // sin medidas dimensionales desde salida (V1)
                                origen: Retazo::ORIGEN_SALIDA,
                                origenId: $movId,
                                observacion: $m['observacion'] !== '' ? $m['observacion'] : null,
                                usuarioId: (int) $_SESSION['usuario']['id'],
                            );
                            $retazosCount++;
                            continue;
                        }
                        $this->movimientos->registrar(
                            productoId: $form['producto_id'],
                            tipo: Movimiento::TIPO_SALIDA,
                            cantidad: (float) $m['cantidad'],
                            usuarioId: (int) $_SESSION['usuario']['id'],
                            observacion: $m['observacion'] !== '' ? $m['observacion'] : null,
                            proveedorId: null,
                            motivo: $m['motivo'],
                        );
                        $mermasRegistradas += (float) $m['cantidad'];
                    }

                    $db->commit();

                    $cantTxt  = $this->formatCantidad($form['cantidad']);
                    $mermaTxt = $this->formatCantidad($mermasRegistradas);
                    $detalleAudit = "Salida ({$motivo}) de {$cantTxt}"
                        . ($mermasRegistradas > 0 ? " + {$mermaTxt} en mermas." : '.')
                        . ($retazosCount > 0 ? " {$retazosCount} retazo(s) guardados." : '');
                    $this->audit('salida', 'movimiento', (string) $form['producto_id'], $detalleAudit);
                    $this->setFlash('success', $this->mensajeExito($motivo, $form['cantidad'], $mermasRegistradas, $retazosCount));
                    $this->redirect('/movimiento/historial?motivo=' . $motivo);
                } catch (Throwable $e) {
                    if ($db->inTransaction()) {
                        $db->rollBack();
                    }
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
        if ((float) $f['cantidad'] <= 0) {
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

    /**
     * Filtra y normaliza el arreglo `mermas[]` posteado. Descarta filas vacías
     * (cantidad 0 o vacía) y deja únicamente cantidades positivas con motivo
     * válido. Los motivos aceptados son: merma, accidente y retazo. Los retazos
     * NO descuentan stock (se procesan aparte en procesarSalida).
     *
     * @param  mixed $raw
     * @return array<int, array{cantidad:float, motivo:string, observacion:string}>
     */
    private function normalizarMermasPost(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }
        $motivosValidos = [
            Movimiento::MOTIVO_MERMA,
            Movimiento::MOTIVO_ACCIDENTE,
            Movimiento::MOTIVO_RETAZO,
        ];
        $out = [];
        foreach ($raw as $fila) {
            if (!is_array($fila)) continue;
            $cant = is_numeric($fila['cantidad'] ?? null) ? (float) $fila['cantidad'] : 0.0;
            if ($cant <= 0) continue;
            $motivo = (string) ($fila['motivo'] ?? Movimiento::MOTIVO_MERMA);
            if (!in_array($motivo, $motivosValidos, true)) {
                $motivo = Movimiento::MOTIVO_MERMA;
            }
            $out[] = [
                'cantidad'    => $cant,
                'motivo'      => $motivo,
                'observacion' => trim((string) ($fila['observacion'] ?? '')),
            ];
        }
        return $out;
    }

    /** Formatea cantidades: 18 → "18"; 1.5 → "1.50". */
    private function formatCantidad(float $n): string
    {
        return fmod($n, 1.0) === 0.0
            ? (string) (int) $n
            : number_format($n, 2, '.', '');
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

    private function mensajeExito(string $motivo, float $cantidad, float $mermas = 0.0, int $retazos = 0): string
    {
        $verbo = match ($motivo) {
            Movimiento::MOTIVO_VENTA     => 'Venta registrada',
            Movimiento::MOTIVO_ENCARGO   => 'Encargo registrado',
            Movimiento::MOTIVO_ACCIDENTE => 'Accidente registrado',
            Movimiento::MOTIVO_MERMA     => 'Merma registrada',
            default                       => 'Salida registrada',
        };
        $msg = "{$verbo} (-{$this->formatCantidad($cantidad)} de stock)";
        if ($mermas > 0) {
            $msg .= ' + ' . $this->formatCantidad($mermas) . ' en mermas';
        }
        if ($retazos > 0) {
            $msg .= " · {$retazos} retazo" . ($retazos > 1 ? 's' : '') . ' anotado' . ($retazos > 1 ? 's' : '');
        }
        return $msg . '.';
    }
}
