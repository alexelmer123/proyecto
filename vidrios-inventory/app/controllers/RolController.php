<?php
declare(strict_types=1);

final class RolController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $roles = (new Rol())->listadoConPermisos();

        // Para cada rol, transformar el GROUP_CONCAT en un array para la vista.
        foreach ($roles as &$r) {
            $r['permisos_lista'] = $r['codigos']
                ? array_map('trim', explode(',', (string) $r['codigos']))
                : [];
            // Agrupar por módulo (prefijo antes del punto)
            $grupos = [];
            foreach ($r['permisos_lista'] as $cod) {
                $modulo = strpos($cod, '.') !== false
                    ? substr($cod, 0, strpos($cod, '.'))
                    : 'general';
                $grupos[$modulo][] = $cod;
            }
            ksort($grupos);
            $r['permisos_grupos'] = $grupos;
        }
        unset($r);

        $todosLosPermisos = (new Permiso())->listadoAgrupado();

        $this->render('roles/index', [
            'titulo'           => 'Roles y permisos',
            'roles'            => $roles,
            'todosLosPermisos' => $todosLosPermisos,
        ]);
    }
}
