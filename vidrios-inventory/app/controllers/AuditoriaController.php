<?php
declare(strict_types=1);

final class AuditoriaController extends Controller
{
    public function exportar(): void
    {
        $this->requireAuth();
        $filtros = [];
        if (!empty($_GET['entidad'])) $filtros['entidad'] = (string) $_GET['entidad'];
        if (!empty($_GET['accion']))  $filtros['accion']  = (string) $_GET['accion'];
        $auditoria = new Auditoria();
        $filas = array_map(fn($r) => [
            $r['created_at'],
            $r['usuario_email'] ?? '',
            $r['usuario_nombre'] ?? '',
            $r['accion'],
            $r['entidad'],
            $r['entidad_id'] ?? '',
            $r['descripcion'] ?? '',
            $r['ip'] ?? '',
        ], $auditoria->listar($filtros, 5000, 0));
        Exporter::csv('auditoria', [
            'Fecha', 'Email usuario', 'Nombre usuario', 'Acción',
            'Entidad', 'ID entidad', 'Descripción', 'IP',
        ], $filas);
    }

    public function index(): void
    {
        $this->requireAuth();

        $auditoria = new Auditoria();

        $filtros = [];
        if (!empty($_GET['entidad'])) $filtros['entidad'] = (string) $_GET['entidad'];
        if (!empty($_GET['accion']))  $filtros['accion']  = (string) $_GET['accion'];

        $total     = $auditoria->contar($filtros);
        $paginator = new Paginator($total, 10);
        $registros = $auditoria->listar($filtros, $paginator->perPage, $paginator->offset);

        $entidades = ['producto', 'categoria', 'proveedor', 'movimiento', 'auth'];
        $acciones  = ['crear', 'editar', 'eliminar', 'ajustar', 'entrada', 'salida', 'login', 'logout', 'login_fallido'];

        $this->render('auditoria/index', [
            'titulo'     => 'Bitácora de auditoría',
            'registros'  => $registros,
            'paginator'  => $paginator,
            'filtros'    => $filtros,
            'entidades'  => $entidades,
            'acciones'   => $acciones,
        ]);
    }
}
