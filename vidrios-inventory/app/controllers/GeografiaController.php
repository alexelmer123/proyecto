<?php
declare(strict_types=1);

/**
 * Endpoints JSON para selectores en cascada.
 * Rutas:
 *   /geografia/departamentos/{paisId}
 *   /geografia/provincias/{depId}
 *   /geografia/distritos/{provId}
 *   /geografia/ciudades/{distId}
 */
final class GeografiaController extends Controller
{
    public function departamentos(string $paisId = '0'): void
    {
        $this->requireAuth();
        $this->json((new Departamento())->porPais((int) $paisId));
    }

    public function provincias(string $depId = '0'): void
    {
        $this->requireAuth();
        $this->json((new Provincia())->porDepartamento((int) $depId));
    }

    public function distritos(string $provId = '0'): void
    {
        $this->requireAuth();
        $this->json((new Distrito())->porProvincia((int) $provId));
    }

    public function ciudades(string $distId = '0'): void
    {
        $this->requireAuth();
        $this->json((new Ciudad())->porDistrito((int) $distId));
    }

    private function json(array $data): void
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => true, 'data' => $data]);
    }
}
