<?php
declare(strict_types=1);

final class RetazoController extends Controller
{
    private Retazo $retazos;
    private Producto $productos;

    public function __construct()
    {
        $this->retazos   = new Retazo();
        $this->productos = new Producto();
    }

    public function index(): void
    {
        $this->requireAuth();

        $productoId = isset($_GET['producto']) && $_GET['producto'] !== '' ? (int) $_GET['producto'] : null;
        $origen     = trim((string) ($_GET['origen'] ?? ''));
        $estado     = trim((string) ($_GET['estado'] ?? ''));   // disponible|aprovechado|''
        $desde      = trim((string) ($_GET['desde']  ?? ''));
        $hasta      = trim((string) ($_GET['hasta']  ?? ''));

        $total     = $this->retazos->contar($productoId, $origen ?: null, $estado ?: null, $desde ?: null, $hasta ?: null);
        $paginator = new Paginator($total, 15);

        $extras = array_filter([
            'producto' => $productoId,
            'origen'   => $origen,
            'estado'   => $estado,
            'desde'    => $desde,
            'hasta'    => $hasta,
        ], static fn($v) => $v !== null && $v !== '');

        $this->render('retazos/index', [
            'titulo'      => 'Retazos disponibles',
            'retazos'     => $this->retazos->listar(
                $productoId, $origen ?: null, $estado ?: null,
                $desde ?: null, $hasta ?: null,
                $paginator->perPage, $paginator->offset
            ),
            'productos'   => $this->productos->findAll('nombre ASC'),
            'filtro'      => compact('productoId', 'origen', 'estado', 'desde', 'hasta'),
            'paginator'   => $paginator,
            'extrasUrl'   => $extras,
        ]);
    }

    /** Marca un retazo como aprovechado (o lo regresa a disponible). */
    public function aprovechar(string $id = '0'): void
    {
        $this->requireAuth();
        $id = (int) $id;
        $r  = $this->retazos->findById($id);
        if ($r === null) {
            $this->setFlash('error', 'Retazo no encontrado.');
            $this->redirect('/retazo/index');
        }
        $nuevo = ((int) $r['aprovechado']) === 1 ? false : true;
        $this->retazos->marcarAprovechado($id, $nuevo);
        $this->audit('aprovechar', 'retazo', (string) $id,
            $nuevo ? 'Retazo marcado como aprovechado.' : 'Retazo regresado a disponible.');
        $this->setFlash('success', $nuevo
            ? 'Retazo marcado como aprovechado.'
            : 'Retazo disponible de nuevo.');
        $this->redirect('/retazo/index');
    }

    public function eliminar(string $id = '0'): void
    {
        $this->requireAdmin();
        $id = (int) $id;
        if ($this->retazos->findById($id) === null) {
            $this->setFlash('error', 'Retazo no encontrado.');
            $this->redirect('/retazo/index');
        }
        $this->retazos->delete($id);
        $this->audit('eliminar', 'retazo', (string) $id, 'Retazo eliminado.');
        $this->setFlash('success', 'Retazo eliminado.');
        $this->redirect('/retazo/index');
    }
}
