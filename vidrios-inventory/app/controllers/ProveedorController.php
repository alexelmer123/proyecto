<?php
declare(strict_types=1);

final class ProveedorController extends Controller
{
    private Proveedor $proveedores;

    public function __construct()
    {
        $this->proveedores = new Proveedor();
    }

    public function index(): void
    {
        $this->requireAuth();
        $this->render('proveedores/index', [
            'proveedores' => $this->proveedores->listadoConUbicacion(),
            'titulo'      => 'Proveedores',
        ]);
    }

    public function crear(): void
    {
        $this->requireAuth();
        $errores = [];
        $form = $this->emptyForm();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form = $this->extraer($_POST);
            $errores = $this->validar($form);
            if ($errores === []) {
                $form['estado']     = 1;
                $form['created_at'] = date('Y-m-d H:i:s');
                $newId = $this->proveedores->create($form);
                $this->audit('crear', 'proveedor', (string) $newId, "Proveedor «{$form['nombre']}» creado.");
                $this->setFlash('success', 'Proveedor creado.');
                $this->redirect('/proveedor/index');
            }
        }

        $this->render('proveedores/crear', [
            'form'    => $form,
            'errores' => $errores,
            'paises'  => (new Pais())->activos(),
            'titulo'  => 'Nuevo proveedor',
        ]);
    }

    public function editar(string $id = '0'): void
    {
        $this->requireAuth();
        $id = (int) $id;
        $proveedor = $this->proveedores->findById($id);
        if ($proveedor === null) {
            $this->setFlash('error', 'Proveedor no encontrado.');
            $this->redirect('/proveedor/index');
        }

        $errores = [];
        $form = $proveedor;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form = $this->extraer($_POST);
            $errores = $this->validar($form);
            if ($errores === []) {
                $this->proveedores->update($id, $form);
                $this->audit('editar', 'proveedor', (string) $id, "Proveedor «{$form['nombre']}» editado.");
                $this->setFlash('success', 'Proveedor actualizado.');
                $this->redirect('/proveedor/index');
            }
        }

        // Pre-cargar selects ya filtrados según los IDs guardados
        $paises         = (new Pais())->activos();
        $departamentos  = !empty($form['pais_id'])         ? (new Departamento())->porPais((int) $form['pais_id'])               : [];
        $provincias     = !empty($form['departamento_id']) ? (new Provincia())->porDepartamento((int) $form['departamento_id']) : [];
        $distritos      = !empty($form['provincia_id'])    ? (new Distrito())->porProvincia((int) $form['provincia_id'])         : [];
        $ciudades       = !empty($form['distrito_id'])     ? (new Ciudad())->porDistrito((int) $form['distrito_id'])             : [];

        $this->render('proveedores/editar', [
            'form'           => $form,
            'errores'        => $errores,
            'paises'         => $paises,
            'departamentos'  => $departamentos,
            'provincias'     => $provincias,
            'distritos'      => $distritos,
            'ciudades'       => $ciudades,
            'titulo'         => 'Editar proveedor',
        ]);
    }

    public function eliminar(string $id = '0'): void
    {
        $this->requireAuth();
        $id = (int) $id;
        if ($this->proveedores->findById($id) !== null) {
            $this->proveedores->update($id, ['estado' => 0]);
            $this->audit('eliminar', 'proveedor', (string) $id, 'Proveedor desactivado.');
            $this->setFlash('success', 'Proveedor desactivado.');
        }
        $this->redirect('/proveedor/index');
    }

    public function exportar(): void
    {
        $this->requireAuth();
        $filas = array_map(fn($p) => [
            $p['nombre'],
            $p['contacto']   ?? '',
            $p['telefono']   ?? '',
            $p['email']      ?? '',
            $p['ciudad_nombre'] ?? '',
            $p['pais_nombre']   ?? '',
            $p['descripcion_productos'] ?? '',
            (int) $p['estado'] === 1 ? 'Activo' : 'Archivado',
        ], $this->proveedores->listadoConUbicacion());
        Exporter::csv('proveedores', [
            'Nombre', 'Contacto', 'Teléfono', 'Email',
            'Ciudad', 'País', 'Productos que provee', 'Estado',
        ], $filas);
    }

    private function emptyForm(): array
    {
        return [
            'nombre' => '', 'contacto' => '', 'telefono' => '', 'email' => '', 'direccion' => '',
            'descripcion_productos' => '',
            'pais_id' => null, 'departamento_id' => null, 'provincia_id' => null,
            'distrito_id' => null, 'ciudad_id' => null,
        ];
    }

    private function extraer(array $src): array
    {
        $idOrNull = static fn(string $k): ?int =>
            isset($src[$k]) && $src[$k] !== '' ? (int) $src[$k] : null;
        return [
            'nombre'                => trim((string) ($src['nombre']    ?? '')),
            'contacto'              => trim((string) ($src['contacto']  ?? '')),
            'telefono'              => trim((string) ($src['telefono']  ?? '')),
            'email'                 => trim((string) ($src['email']     ?? '')),
            'direccion'             => trim((string) ($src['direccion'] ?? '')),
            'descripcion_productos' => trim((string) ($src['descripcion_productos'] ?? '')),
            'pais_id'               => $idOrNull('pais_id'),
            'departamento_id'       => $idOrNull('departamento_id'),
            'provincia_id'          => $idOrNull('provincia_id'),
            'distrito_id'           => $idOrNull('distrito_id'),
            'ciudad_id'             => $idOrNull('ciudad_id'),
        ];
    }

    private function validar(array $f): array
    {
        $err = [];
        if ($f['nombre'] === '') {
            $err['nombre'] = 'El nombre es obligatorio.';
        }
        if ($f['email'] !== '' && !filter_var($f['email'], FILTER_VALIDATE_EMAIL)) {
            $err['email'] = 'Correo no válido.';
        }
        return $err;
    }
}
