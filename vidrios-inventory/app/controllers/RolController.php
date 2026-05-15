<?php
declare(strict_types=1);

final class RolController extends Controller
{
    private Rol $roles;
    private Permiso $permisos;

    public function __construct()
    {
        $this->roles    = new Rol();
        $this->permisos = new Permiso();
    }

    public function index(): void
    {
        $this->requireAuth();
        $roles = $this->roles->listadoConPermisos();

        foreach ($roles as &$r) {
            $r['permisos_lista'] = $r['codigos']
                ? array_map('trim', explode(',', (string) $r['codigos']))
                : [];
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

        $this->render('roles/index', [
            'titulo' => 'Roles y permisos',
            'roles'  => $roles,
        ]);
    }

    public function crear(): void
    {
        $this->requireAdmin();
        $errores = [];
        $form    = ['nombre' => '', 'descripcion' => '', 'activo' => 1];
        $permisosSeleccionados = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form = [
                'nombre'      => trim((string) ($_POST['nombre'] ?? '')),
                'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
                'activo'      => isset($_POST['activo']) ? 1 : 0,
            ];
            $permisosSeleccionados = array_map('intval', (array) ($_POST['permisos'] ?? []));
            $errores = $this->validar($form);
            if ($errores === []) {
                try {
                    $datos = [
                        'nombre'      => $form['nombre'],
                        'descripcion' => $form['descripcion'] !== '' ? $form['descripcion'] : null,
                        'activo'      => $form['activo'],
                        'created_at'  => date('Y-m-d H:i:s'),
                    ];
                    $rolId = $this->roles->crearConPermisos($datos, $permisosSeleccionados);
                    $this->audit('crear', 'rol', (string) $rolId, "Rol «{$form['nombre']}» creado.");
                    $this->setFlash('success', "Rol «{$form['nombre']}» creado.");
                    $this->redirect('/rol/index');
                } catch (Throwable $e) {
                    $errores['general'] = 'No se pudo guardar: ' . $e->getMessage();
                }
            }
        }

        $this->render('roles/crear', [
            'titulo'                => 'Nuevo rol',
            'form'                  => $form,
            'errores'               => $errores,
            'todosLosPermisos'      => $this->permisos->listadoAgrupado(),
            'permisosSeleccionados' => $permisosSeleccionados,
            'esEdicion'             => false,
        ]);
    }

    public function editar(string $id = '0'): void
    {
        $this->requireAdmin();
        $id  = (int) $id;
        $rol = $this->roles->findById($id);
        if ($rol === null) {
            $this->setFlash('error', 'Rol no encontrado.');
            $this->redirect('/rol/index');
        }

        $errores = [];
        $form = [
            'nombre'      => (string) $rol['nombre'],
            'descripcion' => (string) ($rol['descripcion'] ?? ''),
            'activo'      => (int) $rol['activo'],
        ];
        $permisosSeleccionados = $this->roles->idsPermisoDeRolId($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form = [
                'nombre'      => trim((string) ($_POST['nombre'] ?? '')),
                'descripcion' => trim((string) ($_POST['descripcion'] ?? '')),
                'activo'      => isset($_POST['activo']) ? 1 : 0,
            ];
            $permisosSeleccionados = array_map('intval', (array) ($_POST['permisos'] ?? []));
            $errores = $this->validar($form, $id);
            if ($errores === []) {
                try {
                    $datos = [
                        'nombre'      => $form['nombre'],
                        'descripcion' => $form['descripcion'] !== '' ? $form['descripcion'] : null,
                        'activo'      => $form['activo'],
                    ];
                    $this->roles->actualizarConPermisos($id, $datos, $permisosSeleccionados);
                    $this->audit('editar', 'rol', (string) $id, "Rol «{$form['nombre']}» editado.");
                    $this->setFlash('success', 'Rol actualizado.');
                    $this->redirect('/rol/index');
                } catch (Throwable $e) {
                    $errores['general'] = 'No se pudo guardar: ' . $e->getMessage();
                }
            }
        }

        $this->render('roles/editar', [
            'titulo'                => 'Editar rol',
            'rolId'                 => $id,
            'form'                  => $form,
            'errores'               => $errores,
            'todosLosPermisos'      => $this->permisos->listadoAgrupado(),
            'permisosSeleccionados' => $permisosSeleccionados,
            'esEdicion'             => true,
        ]);
    }

    public function eliminar(string $id = '0'): void
    {
        $this->requireAdmin();
        $id  = (int) $id;
        $rol = $this->roles->findById($id);
        if ($rol === null) {
            $this->setFlash('error', 'Rol no encontrado.');
            $this->redirect('/rol/index');
        }
        if ($this->roles->contarUsuarios($id) > 0) {
            $this->setFlash('error', "No se puede archivar el rol «{$rol['nombre']}»: hay usuarios activos asignados.");
            $this->redirect('/rol/index');
        }
        $this->roles->softDelete($id);
        $this->audit('eliminar', 'rol', (string) $id, "Rol «{$rol['nombre']}» archivado.");
        $this->setFlash('success', 'Rol archivado.');
        $this->redirect('/rol/index');
    }

    /** @return array<string, string> */
    private function validar(array $f, ?int $excluirId = null): array
    {
        $err = [];
        if ($f['nombre'] === '') {
            $err['nombre'] = 'El nombre es obligatorio.';
        } elseif (mb_strlen($f['nombre']) > 50) {
            $err['nombre'] = 'El nombre no puede exceder 50 caracteres.';
        } elseif ($this->roles->findByNombre($f['nombre'], $excluirId) !== null) {
            $err['nombre'] = 'Ya existe un rol con ese nombre.';
        }
        return $err;
    }
}
