<?php
declare(strict_types=1);

final class CategoriaController extends Controller
{
    private Categoria $categorias;

    public function __construct()
    {
        $this->categorias = new Categoria();
    }

    public function index(): void
    {
        $this->requireAuth();
        $this->render('categorias/index', [
            'categorias' => $this->categorias->conConteoDeProductos(),
            'titulo'     => 'Categorías',
        ]);
    }

    public function exportar(): void
    {
        $this->requireAuth();
        $filas = array_map(fn($c) => [
            (int) $c['id'],
            $c['nombre'],
            $c['descripcion'] ?? '',
            (int) ($c['total_productos'] ?? 0),
            (int) $c['estado'] === 1 ? 'Activa' : 'Inactiva',
        ], $this->categorias->conConteoDeProductos());
        Exporter::csv('categorias', [
            'ID', 'Nombre', 'Descripción', 'Total productos', 'Estado',
        ], $filas);
    }

    public function crear(): void
    {
        $this->requireAuth();
        $errores = [];
        $form = ['nombre' => '', 'descripcion' => ''];

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form['nombre']      = trim((string) ($_POST['nombre'] ?? ''));
            $form['descripcion'] = trim((string) ($_POST['descripcion'] ?? ''));

            if ($form['nombre'] === '') {
                $errores['nombre'] = 'El nombre es obligatorio.';
            } elseif ($this->categorias->existsBy('nombre', $form['nombre'])) {
                $errores['nombre'] = 'Ya existe una categoría con ese nombre.';
            }

            if ($errores === []) {
                $newId = $this->categorias->create([
                    'nombre'      => $form['nombre'],
                    'descripcion' => $form['descripcion'],
                    'estado'      => 1,
                    'created_at'  => date('Y-m-d H:i:s'),
                ]);
                $this->audit('crear', 'categoria', (string) $newId, "Categoría «{$form['nombre']}» creada.");
                $this->setFlash('success', 'Categoría creada.');
                $this->redirect('/categoria/index');
            }
        }

        $this->render('categorias/crear', [
            'form'    => $form,
            'errores' => $errores,
            'titulo'  => 'Nueva categoría',
        ]);
    }

    public function editar(string $id = '0'): void
    {
        $this->requireAuth();
        $id = (int) $id;
        $categoria = $this->categorias->findById($id);
        if ($categoria === null) {
            $this->setFlash('error', 'Categoría no encontrada.');
            $this->redirect('/categoria/index');
        }

        $errores = [];
        $form = $categoria;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $form['nombre']      = trim((string) ($_POST['nombre'] ?? ''));
            $form['descripcion'] = trim((string) ($_POST['descripcion'] ?? ''));

            if ($form['nombre'] === '') {
                $errores['nombre'] = 'El nombre es obligatorio.';
            } elseif ($this->categorias->existsBy('nombre', $form['nombre'], $id)) {
                $errores['nombre'] = 'Ya existe otra categoría con ese nombre.';
            }

            if ($errores === []) {
                $this->categorias->update($id, [
                    'nombre'      => $form['nombre'],
                    'descripcion' => $form['descripcion'],
                ]);
                $this->audit('editar', 'categoria', (string) $id, "Categoría «{$form['nombre']}» editada.");
                $this->setFlash('success', 'Categoría actualizada.');
                $this->redirect('/categoria/index');
            }
        }

        $this->render('categorias/editar', [
            'form'    => $form,
            'errores' => $errores,
            'titulo'  => 'Editar categoría',
        ]);
    }

    public function eliminar(string $id = '0'): void
    {
        $this->requireAuth();
        $id = (int) $id;
        if ($this->categorias->findById($id) !== null) {
            $this->categorias->update($id, ['estado' => 0]);
            $this->audit('eliminar', 'categoria', (string) $id, 'Categoría desactivada.');
            $this->setFlash('success', 'Categoría desactivada.');
        }
        $this->redirect('/categoria/index');
    }
}
