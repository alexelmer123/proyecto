<?php
declare(strict_types=1);

final class AuditoriaController extends Controller
{
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
