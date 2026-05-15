<?php
declare(strict_types=1);

final class UsuarioController extends Controller
{
    private Usuario $usuarios;
    private Rol $roles;
    private Permiso $permisos;

    public function __construct()
    {
        $this->usuarios = new Usuario();
        $this->roles    = new Rol();
        $this->permisos = new Permiso();
    }

    public function index(): void
    {
        $this->requireAdmin();
        $this->render('usuarios/index', [
            'titulo'   => 'Usuarios del sistema',
            'usuarios' => $this->usuarios->listadoConRol(),
        ]);
    }

    public function crear(): void
    {
        $this->requireAdmin();
        $errores = [];
        $form    = $this->defaultForm();
        $permisosExtra = [];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form    = $this->extraerForm($_POST);
            $permisosExtra = array_map('intval', (array) ($_POST['permisos_extra'] ?? []));
            $errores = $this->validar($form, esEdicion: false);

            if ($errores === []) {
                try {
                    $rol = $this->roles->findById((int) $form['rol_id']);
                    $datos = [
                        'nombre'     => $form['nombre'],
                        'email'      => $form['email'],
                        'password'   => password_hash($form['password'], PASSWORD_BCRYPT),
                        'rol'        => $rol !== null ? (string) $rol['nombre'] : 'operador',
                        'rol_id'     => (int) $form['rol_id'],
                        'activo'     => 1,
                        'created_at' => date('Y-m-d H:i:s'),
                    ];
                    $uid = $this->usuarios->crearConAsignaciones($datos, $permisosExtra);
                    $this->audit('crear', 'usuario', (string) $uid, "Usuario «{$form['email']}» creado.");
                    $this->setFlash('success', "Usuario «{$form['nombre']}» creado.");
                    $this->redirect('/usuario/index');
                } catch (Throwable $e) {
                    $errores['general'] = 'No se pudo guardar: ' . $e->getMessage();
                }
            }
        }

        $this->render('usuarios/crear', [
            'titulo'           => 'Nuevo usuario',
            'form'             => $form,
            'errores'          => $errores,
            'roles'            => $this->roles->activos(),
            'todosLosPermisos' => $this->permisos->listadoAgrupado(),
            'permisosDelRol'   => $form['rol_id'] ? $this->roles->idsPermisoDeRolId((int) $form['rol_id']) : [],
            'permisosExtra'    => $permisosExtra,
            'esEdicion'        => false,
        ]);
    }

    public function editar(string $id = '0'): void
    {
        $this->requireAdmin();
        $id      = (int) $id;
        $usuario = $this->usuarios->findByIdConRol($id);
        if ($usuario === null) {
            $this->setFlash('error', 'Usuario no encontrado.');
            $this->redirect('/usuario/index');
        }

        $errores = [];
        $form = [
            'nombre'   => (string) $usuario['nombre'],
            'email'    => (string) $usuario['email'],
            'rol_id'   => (int) ($usuario['rol_id'] ?? 0),
            'password' => '',
            'activo'   => (int) $usuario['activo'],
        ];
        $permisosExtra = $this->usuarios->idsPermisoExtra($id);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form    = $this->extraerForm($_POST);
            $form['activo'] = isset($_POST['activo']) ? 1 : 0;
            $permisosExtra = array_map('intval', (array) ($_POST['permisos_extra'] ?? []));
            $errores = $this->validar($form, esEdicion: true, excluirId: $id);

            if ($errores === []) {
                try {
                    $rol = $this->roles->findById((int) $form['rol_id']);
                    $datos = [
                        'nombre' => $form['nombre'],
                        'email'  => $form['email'],
                        'rol'    => $rol !== null ? (string) $rol['nombre'] : 'operador',
                        'rol_id' => (int) $form['rol_id'],
                        'activo' => $form['activo'],
                    ];
                    if ($form['password'] !== '') {
                        $datos['password'] = password_hash($form['password'], PASSWORD_BCRYPT);
                    }
                    $this->usuarios->actualizarConAsignaciones($id, $datos, $permisosExtra);
                    $this->audit('editar', 'usuario', (string) $id, "Usuario «{$form['email']}» editado.");
                    $this->setFlash('success', 'Usuario actualizado.');
                    $this->redirect('/usuario/index');
                } catch (Throwable $e) {
                    $errores['general'] = 'No se pudo guardar: ' . $e->getMessage();
                }
            }
        }

        $this->render('usuarios/editar', [
            'titulo'           => 'Editar usuario',
            'usuarioId'        => $id,
            'form'             => $form,
            'errores'          => $errores,
            'roles'            => $this->roles->activos(),
            'todosLosPermisos' => $this->permisos->listadoAgrupado(),
            'permisosDelRol'   => $form['rol_id'] ? $this->roles->idsPermisoDeRolId((int) $form['rol_id']) : [],
            'permisosExtra'    => $permisosExtra,
            'esEdicion'        => true,
        ]);
    }

    public function eliminar(string $id = '0'): void
    {
        $this->requireAdmin();
        $id = (int) $id;
        $usuario = $this->usuarios->findById($id);
        if ($usuario === null) {
            $this->setFlash('error', 'Usuario no encontrado.');
            $this->redirect('/usuario/index');
        }
        if ($id === $this->userId()) {
            $this->setFlash('error', 'No puedes archivar tu propia cuenta.');
            $this->redirect('/usuario/index');
        }
        $this->usuarios->softDelete($id);
        $this->audit('eliminar', 'usuario', (string) $id, "Usuario «{$usuario['email']}» archivado.");
        $this->setFlash('success', 'Usuario archivado.');
        $this->redirect('/usuario/index');
    }

    /** Endpoint JSON: lista de ids de permiso para un rol dado. Usado por el JS del form. */
    public function permisosDeRol(string $rolId = '0'): void
    {
        $this->requireAdmin();
        header('Content-Type: application/json; charset=utf-8');
        $rolId = (int) $rolId;
        if ($rolId <= 0) {
            echo json_encode(['ok' => true, 'permisos' => []]);
            return;
        }
        echo json_encode([
            'ok'       => true,
            'permisos' => $this->roles->idsPermisoDeRolId($rolId),
        ]);
    }

    /** @return array<string, mixed> */
    private function defaultForm(): array
    {
        return [
            'nombre' => '', 'email' => '', 'password' => '',
            'rol_id' => 0, 'activo' => 1,
        ];
    }

    /** @return array<string, mixed> */
    private function extraerForm(array $src): array
    {
        return [
            'nombre'   => trim((string) ($src['nombre']   ?? '')),
            'email'    => trim((string) ($src['email']    ?? '')),
            'password' => (string) ($src['password'] ?? ''),
            'rol_id'   => (int) ($src['rol_id'] ?? 0),
            'activo'   => 1,
        ];
    }

    /** @return array<string, string> */
    private function validar(array $f, bool $esEdicion, ?int $excluirId = null): array
    {
        $err = [];
        if ($f['nombre'] === '') {
            $err['nombre'] = 'El nombre es obligatorio.';
        }
        if ($f['email'] === '' || !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
            $err['email'] = 'Email inválido.';
        } elseif ($this->usuarios->existsByEmail($f['email'], $excluirId)) {
            $err['email'] = 'Ya existe otro usuario con ese email.';
        }
        if (!$esEdicion) {
            if (strlen($f['password']) < 6) {
                $err['password'] = 'La contraseña debe tener al menos 6 caracteres.';
            }
        } elseif ($f['password'] !== '' && strlen($f['password']) < 6) {
            $err['password'] = 'La nueva contraseña debe tener al menos 6 caracteres.';
        }
        if ((int) $f['rol_id'] <= 0 || $this->roles->findById((int) $f['rol_id']) === null) {
            $err['rol_id'] = 'Selecciona un rol válido.';
        }
        return $err;
    }
}
