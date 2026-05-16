<?php
declare(strict_types=1);

final class EncargoController extends Controller
{
    private Encargo $encargos;
    private Producto $productos;

    public function __construct()
    {
        $this->encargos  = new Encargo();
        $this->productos = new Producto();
    }

    public function index(): void
    {
        $this->requireAuth();

        $estado = trim((string) ($_GET['estado'] ?? ''));
        $total     = $this->encargos->contar($estado ?: null);
        $paginator = new Paginator($total, 10);

        $extras = $estado !== '' ? ['estado' => $estado] : [];

        // Para mostrar los items en la tabla agrupada, traemos cada encargo con sus items
        $listado = $this->encargos->listadoConResumen($estado ?: null, $paginator->perPage, $paginator->offset);
        foreach ($listado as &$e) {
            $e['items'] = $this->encargos->itemsDe((int) $e['id']);
        }
        unset($e);

        $this->render('encargos/index', [
            'titulo'    => 'Encargos',
            'encargos'  => $listado,
            'estado'    => $estado,
            'paginator' => $paginator,
            'extrasUrl' => $extras,
        ]);
    }

    public function crear(): void
    {
        $this->requireAuth();
        $errores = [];
        $form = $this->defaultForm();
        $items = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form = [
                'cliente'       => trim((string) ($_POST['cliente']       ?? '')),
                'telefono'      => trim((string) ($_POST['telefono']      ?? '')),
                'lugar_entrega' => trim((string) ($_POST['lugar_entrega'] ?? '')),
                'fecha_entrega' => trim((string) ($_POST['fecha_entrega'] ?? '')),
                'detalles'      => trim((string) ($_POST['detalles']      ?? '')),
            ];
            $items = $this->extraerItems($_POST);
            $errores = $this->validar($form, $items);

            if ($errores === []) {
                try {
                    $id = $this->encargos->crearConItems(
                        $form,
                        $items,
                        (int) $_SESSION['usuario']['id']
                    );
                    $this->audit('crear', 'encargo', (string) $id,
                        "Encargo para «{$form['cliente']}» con " . count($items) . ' producto(s).');
                    $this->setFlash('success', 'Encargo registrado y stock descontado.');
                    $this->redirect('/encargo/detalle/' . $id);
                } catch (Throwable $e) {
                    $errores['general'] = $e->getMessage();
                }
            }
        }

        $this->render('encargos/crear', [
            'titulo'    => 'Nuevo encargo',
            'form'      => $form,
            'items'     => $items,
            'errores'   => $errores,
            'productos' => $this->productos->buscar(''),
        ]);
    }

    public function editar(string $id = '0'): void
    {
        $this->requireAuth();
        $id = (int) $id;
        $encargo = $this->encargos->findById($id);
        if ($encargo === null) {
            $this->setFlash('error', 'Encargo no encontrado.');
            $this->redirect('/encargo/index');
        }
        if ($encargo['estado'] !== Encargo::ESTADO_PENDIENTE) {
            $this->setFlash('error', 'Sólo se puede editar un encargo pendiente.');
            $this->redirect('/encargo/detalle/' . $id);
        }

        $errores = [];
        $form = [
            'cliente'       => (string) $encargo['cliente'],
            'telefono'      => (string) ($encargo['telefono'] ?? ''),
            'lugar_entrega' => (string) ($encargo['lugar_entrega'] ?? ''),
            'fecha_entrega' => (string) ($encargo['fecha_entrega'] ?? ''),
            'detalles'      => (string) ($encargo['detalles'] ?? ''),
        ];
        $items = $this->itemsExistentesParaForm($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form = [
                'cliente'       => trim((string) ($_POST['cliente']       ?? '')),
                'telefono'      => trim((string) ($_POST['telefono']      ?? '')),
                'lugar_entrega' => trim((string) ($_POST['lugar_entrega'] ?? '')),
                'fecha_entrega' => trim((string) ($_POST['fecha_entrega'] ?? '')),
                'detalles'      => trim((string) ($_POST['detalles']      ?? '')),
            ];
            $items = $this->extraerItems($_POST);
            // Para edición: stock disponible considera los items actuales
            // que se "devolverán" al guardar.
            $errores = $this->validar($form, $items, $id);

            if ($errores === []) {
                try {
                    $this->encargos->actualizarConItems(
                        $id, $form, $items,
                        (int) $_SESSION['usuario']['id']
                    );
                    $this->audit('editar', 'encargo', (string) $id,
                        "Encargo «{$encargo['codigo']}» editado.");
                    $this->setFlash('success', 'Encargo actualizado.');
                    $this->redirect('/encargo/detalle/' . $id);
                } catch (Throwable $e) {
                    $errores['general'] = $e->getMessage();
                }
            }
        }

        $this->render('encargos/editar', [
            'titulo'    => "Editar encargo {$encargo['codigo']}",
            'encargo'   => $encargo,
            'form'      => $form,
            'items'     => $items,
            'errores'   => $errores,
            'productos' => $this->productos->buscar(''),
        ]);
    }

    /**
     * Convierte los items guardados (DB) al formato esperado por el form.
     * @return array<int, array<string, mixed>>
     */
    private function itemsExistentesParaForm(int $encargoId): array
    {
        $out = [];
        foreach ($this->encargos->itemsDe($encargoId) as $it) {
            $out[] = [
                'producto_id'     => (int) $it['producto_id'],
                'cantidad'        => (int) $it['cantidad'],
                'precio_unitario' => $it['precio_unitario'] ?? '',
            ];
        }
        return $out;
    }

    public function detalle(string $id = '0'): void
    {
        $this->requireAuth();
        $id      = (int) $id;
        $encargo = $this->encargos->findEnriquecido($id);
        if ($encargo === null) {
            $this->setFlash('error', 'Encargo no encontrado.');
            $this->redirect('/encargo/index');
        }
        $this->render('encargos/detalle', [
            'titulo'  => "Encargo {$encargo['codigo']}",
            'encargo' => $encargo,
            'items'   => $this->encargos->itemsDe($id),
        ]);
    }

    public function entregar(string $id = '0'): void
    {
        $this->requireAuth();
        $id = (int) $id;
        $encargo = $this->encargos->findEnriquecido($id);
        if ($encargo === null) {
            $this->setFlash('error', 'Encargo no encontrado.');
            $this->redirect('/encargo/index');
        }
        if ($encargo['estado'] !== Encargo::ESTADO_PENDIENTE) {
            $this->setFlash('error', 'Sólo se puede entregar un encargo pendiente.');
            $this->redirect('/encargo/detalle/' . $id);
        }

        $items   = $this->encargos->itemsDe($id);
        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $this->encargos->entregar(
                    $id,
                    (int) $_SESSION['usuario']['id'],
                    $this->normalizarMermasEntrega($_POST['mermas'] ?? [])
                );
                $this->audit('entregar', 'encargo', (string) $id, 'Encargo marcado como entregado.');
                $this->setFlash('success', 'Encargo marcado como entregado.');
                if ($this->isAjax()) {
                    http_response_code(200);
                    echo 'ok';
                    return;
                }
                $this->redirect('/encargo/detalle/' . $id);
            } catch (Throwable $e) {
                $errores['general'] = $e->getMessage();
            }
        }

        $viewData = [
            'encargo'  => $encargo,
            'items'    => $items,
            'errores'  => $errores,
            'unidades' => ProductoController::UNIDADES,
        ];

        if ($this->isAjax()) {
            $this->render('encargos/entregar', $viewData, withLayout: false);
            return;
        }
        $viewData['titulo'] = "Entregar encargo {$encargo['codigo']}";
        $this->render('encargos/entregar', $viewData);
    }

    /**
     * Filtra las mermas posteadas al entregar. Acepta merma, accidente y
     * retazo. Las medidas dimensionales (ancho/alto/longitud según unidad)
     * vienen en el sub-array `medidas` y se consolidan a texto en el modelo.
     *
     * @param  mixed $raw
     * @return array<int, array{producto_id:int, cantidad:float, motivo:string, medidas:array<string,float>}>
     */
    private function normalizarMermasEntrega(mixed $raw): array
    {
        if (!is_array($raw)) return [];
        $motivos = [Movimiento::MOTIVO_MERMA, Movimiento::MOTIVO_ACCIDENTE, Movimiento::MOTIVO_RETAZO];
        $out = [];
        foreach ($raw as $row) {
            if (!is_array($row)) continue;
            $pid  = (int) ($row['producto_id'] ?? 0);
            $cant = is_numeric($row['cantidad'] ?? null) ? (float) $row['cantidad'] : 0.0;
            if ($pid <= 0 || $cant <= 0) continue;
            $motivo = (string) ($row['motivo'] ?? Movimiento::MOTIVO_MERMA);
            if (!in_array($motivo, $motivos, true)) {
                $motivo = Movimiento::MOTIVO_MERMA;
            }
            $medidasRaw = is_array($row['medidas'] ?? null) ? $row['medidas'] : [];
            $medidas = [];
            foreach ($medidasRaw as $k => $v) {
                if (is_numeric($v) && (float) $v > 0) {
                    $medidas[(string) $k] = (float) $v;
                }
            }
            $out[] = [
                'producto_id' => $pid,
                'cantidad'    => $cant,
                'motivo'      => $motivo,
                'medidas'     => $medidas,
            ];
        }
        return $out;
    }

    public function cancelar(string $id = '0'): void
    {
        $this->requireAuth();
        $id = (int) $id;
        $motivo = trim((string) ($_POST['motivo'] ?? $_GET['motivo'] ?? ''));
        try {
            $this->encargos->cancelar($id, (int) $_SESSION['usuario']['id'], $motivo);
            $this->audit('cancelar', 'encargo', (string) $id, 'Encargo cancelado; stock restituido.');
            $this->setFlash('success', 'Encargo cancelado y stock devuelto.');
        } catch (Throwable $e) {
            $this->setFlash('error', $e->getMessage());
        }
        $this->redirect('/encargo/detalle/' . $id);
    }

    /** @return array<string, mixed> */
    private function defaultForm(): array
    {
        return [
            'cliente'       => '',
            'telefono'      => '',
            'lugar_entrega' => '',
            'fecha_entrega' => '',
            'detalles'      => '',
        ];
    }

    /**
     * Parsea `items[N][producto_id|cantidad|precio_unitario]` desde POST.
     * @return array<int, array<string, mixed>>
     */
    private function extraerItems(array $src): array
    {
        $raw = $src['items'] ?? [];
        if (!is_array($raw)) return [];
        $out = [];
        foreach ($raw as $row) {
            $pid = (int) ($row['producto_id'] ?? 0);
            $qty = (int) ($row['cantidad']    ?? 0);
            if ($pid <= 0 || $qty <= 0) {
                continue; // descartamos filas vacías o inválidas silenciosamente
            }
            $out[] = [
                'producto_id'     => $pid,
                'cantidad'        => $qty,
                'precio_unitario' => isset($row['precio_unitario']) && $row['precio_unitario'] !== ''
                    ? (float) $row['precio_unitario'] : null,
            ];
        }
        return $out;
    }

    /**
     * @param array<int, array<string, mixed>> $items
     * @param int|null $editandoEncargoId si está editando, sumamos al stock
     *                                    disponible las cantidades de los items
     *                                    actuales del encargo (que serán
     *                                    "devueltos" al guardar).
     * @return array<string, string>
     */
    private function validar(array $f, array $items, ?int $editandoEncargoId = null): array
    {
        $err = [];
        if ($f['cliente'] === '') {
            $err['cliente'] = 'El nombre del cliente es obligatorio.';
        }
        if ($f['fecha_entrega'] === '') {
            $err['fecha_entrega'] = 'La fecha de entrega es obligatoria.';
        }
        if ($items === []) {
            $err['items'] = 'Añade al menos un producto al encargo.';
            return $err;
        }

        // Si estamos editando, sumamos al stock las cantidades existentes que
        // se reintegrarán antes del nuevo descuento.
        $stockExtra = [];
        if ($editandoEncargoId !== null) {
            foreach ($this->encargos->itemsDe($editandoEncargoId) as $it) {
                $pid = (int) $it['producto_id'];
                $stockExtra[$pid] = ($stockExtra[$pid] ?? 0) + (int) $it['cantidad'];
            }
        }

        // Acumulamos cantidades por producto en el form (puede haber filas
        // duplicadas del mismo producto).
        $solicitado = [];
        foreach ($items as $it) {
            $pid = (int) $it['producto_id'];
            $solicitado[$pid] = ($solicitado[$pid] ?? 0) + (int) $it['cantidad'];
        }

        foreach ($solicitado as $pid => $qty) {
            $prod = $this->productos->findById($pid);
            if ($prod === null) {
                $err['items'] = "Hay un producto inválido en el encargo.";
                break;
            }
            $disponible = (int) $prod['stock_actual'] + ($stockExtra[$pid] ?? 0);
            if ($disponible < $qty) {
                $err['items'] = "Stock insuficiente para «{$prod['nombre']}»: "
                              . "disponible {$disponible}, solicitado {$qty}.";
                break;
            }
        }
        return $err;
    }
}
