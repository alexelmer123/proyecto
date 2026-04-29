<?php
declare(strict_types=1);

final class PedidoController extends Controller
{
    private Pedido $pedidos;
    private Proveedor $proveedores;

    public function __construct()
    {
        $this->pedidos     = new Pedido();
        $this->proveedores = new Proveedor();
    }

    public function index(): void
    {
        $this->requireAuth();
        $estado = trim((string) ($_GET['estado'] ?? ''));
        $estado = in_array($estado, ['pendiente','pagado','deuda'], true) ? $estado : null;

        $total     = $this->pedidos->contar($estado);
        $paginator = new Paginator($total, 10);
        $registros = $this->pedidos->listar($estado, $paginator->perPage, $paginator->offset);
        $resumen   = $this->pedidos->resumen();

        $this->render('pedidos/index', [
            'titulo'    => 'Pedidos a proveedores',
            'pedidos'   => $registros,
            'paginator' => $paginator,
            'estado'    => $estado,
            'resumen'   => $resumen,
            'extrasUrl' => $estado ? ['estado' => $estado] : [],
        ]);
    }

    public function crear(): void
    {
        $this->requireAuth();
        $errores = [];
        $form = [
            'numero'        => $this->pedidos->generarNumero(),
            'proveedor_id'  => null,
            'fecha_pedido'  => date('Y-m-d'),
            'fecha_entrega' => '',
            'total'         => 0,
            'pagado'        => 0,
            'observacion'   => '',
        ];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form = $this->extraer($_POST, $form);
            $errores = $this->validar($form);
            if ($errores === []) {
                $estado = $this->pedidos->calcularEstado($form['total'], $form['pagado']);
                $newId = $this->pedidos->create([
                    'numero'        => $form['numero'],
                    'proveedor_id'  => $form['proveedor_id'],
                    'usuario_id'    => $this->userId(),
                    'fecha_pedido'  => $form['fecha_pedido'],
                    'fecha_entrega' => $form['fecha_entrega'] ?: null,
                    'total'         => $form['total'],
                    'pagado'        => $form['pagado'],
                    'estado'        => $estado,
                    'observacion'   => $form['observacion'],
                    'created_at'    => date('Y-m-d H:i:s'),
                ]);
                $this->audit('crear', 'pedido', (string) $newId, "Pedido {$form['numero']} creado (estado: {$estado}).");
                $this->setFlash('success', "Pedido {$form['numero']} registrado.");
                $this->redirect('/pedido/index');
            }
        }

        $this->render('pedidos/crear', [
            'titulo'      => 'Nuevo pedido',
            'form'        => $form,
            'errores'     => $errores,
            'proveedores' => $this->proveedores->activos(),
        ]);
    }

    public function editar(string $id = '0'): void
    {
        $this->requireAuth();
        $id = (int) $id;
        $pedido = $this->pedidos->findById($id);
        if ($pedido === null) {
            $this->setFlash('error', 'Pedido no encontrado.');
            $this->redirect('/pedido/index');
        }
        $form = $pedido;
        $errores = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form = $this->extraer($_POST, $pedido);
            $form['numero'] = $pedido['numero']; // no se cambia
            $errores = $this->validar($form);
            if ($errores === []) {
                $estado = $this->pedidos->calcularEstado($form['total'], $form['pagado']);
                $this->pedidos->update($id, [
                    'proveedor_id'  => $form['proveedor_id'],
                    'fecha_pedido'  => $form['fecha_pedido'],
                    'fecha_entrega' => $form['fecha_entrega'] ?: null,
                    'total'         => $form['total'],
                    'pagado'        => $form['pagado'],
                    'estado'        => $estado,
                    'observacion'   => $form['observacion'],
                ]);
                $this->audit('editar', 'pedido', (string) $id, "Pedido {$pedido['numero']} editado (estado: {$estado}).");
                $this->setFlash('success', 'Pedido actualizado.');
                $this->redirect('/pedido/index');
            }
        }

        $this->render('pedidos/editar', [
            'titulo'      => 'Editar pedido',
            'form'        => $form,
            'errores'     => $errores,
            'proveedores' => $this->proveedores->activos(),
        ]);
    }

    /** Acción rápida desde el listado para marcar como pagado. */
    public function pagar(string $id = '0'): void
    {
        $this->requireAuth();
        $id = (int) $id;
        $pedido = $this->pedidos->findById($id);
        if ($pedido === null) {
            $this->setFlash('error', 'Pedido no encontrado.');
        } else {
            $this->pedidos->update($id, [
                'pagado' => $pedido['total'],
                'estado' => Pedido::ESTADO_PAGADO,
            ]);
            $this->audit('estado', 'pedido', (string) $id, "Pedido {$pedido['numero']} marcado como pagado.");
            $this->setFlash('success', "Pedido {$pedido['numero']} marcado como pagado.");
        }
        $this->redirect('/pedido/index');
    }

    private function extraer(array $src, array $base): array
    {
        return [
            'numero'        => trim((string) ($src['numero']        ?? $base['numero'] ?? '')),
            'proveedor_id'  => isset($src['proveedor_id']) && $src['proveedor_id'] !== ''
                                  ? (int) $src['proveedor_id'] : null,
            'fecha_pedido'  => trim((string) ($src['fecha_pedido']  ?? $base['fecha_pedido']  ?? date('Y-m-d'))),
            'fecha_entrega' => trim((string) ($src['fecha_entrega'] ?? $base['fecha_entrega'] ?? '')),
            'total'         => (float) ($src['total']  ?? 0),
            'pagado'        => (float) ($src['pagado'] ?? 0),
            'observacion'   => trim((string) ($src['observacion']   ?? '')),
        ];
    }

    private function validar(array $f): array
    {
        $err = [];
        if ($f['numero'] === '')          $err['numero']        = 'El número es obligatorio.';
        if ($f['proveedor_id'] === null)  $err['proveedor_id']  = 'Selecciona un proveedor.';
        if ($f['fecha_pedido'] === '')    $err['fecha_pedido']  = 'La fecha del pedido es obligatoria.';
        if ($f['total'] < 0)              $err['total']         = 'El total no puede ser negativo.';
        if ($f['pagado'] < 0)             $err['pagado']        = 'El monto pagado no puede ser negativo.';
        if ($f['pagado'] > $f['total'])   $err['pagado']        = 'El pagado no puede superar al total.';
        return $err;
    }
}
