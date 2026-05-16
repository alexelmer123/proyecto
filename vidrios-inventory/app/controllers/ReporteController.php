<?php
declare(strict_types=1);

final class ReporteController extends Controller
{
    private Producto $productos;
    private Movimiento $movimientos;

    public function __construct()
    {
        $this->productos   = new Producto();
        $this->movimientos = new Movimiento();
    }

    public function index(): void
    {
        $this->stock();
    }

    /* ---------------------------------------------------------------------
     *  STOCK · unifica antiguos "Stock crítico" + "Valor del inventario".
     * ------------------------------------------------------------------- */

    public function stock(): void
    {
        $this->requireAuth();
        [$soloCriticos, $categoriaId, $proveedorId] = $this->filtrosStock();

        $items = $this->productos->stockReporte($soloCriticos, $categoriaId, $proveedorId);
        $totalValor    = array_sum(array_map(static fn(array $r): float => (float) $r['valor_total'], $items));
        $totalCriticos = array_sum(array_map(static fn(array $r): int => (int) $r['es_critico'], $items));

        $this->render('reportes/stock', [
            'productos'      => $items,
            'totalValor'     => $totalValor,
            'totalCriticos'  => $totalCriticos,
            'soloCriticos'   => $soloCriticos,
            'categoriaId'    => $categoriaId,
            'proveedorId'    => $proveedorId,
            'categorias'     => (new Categoria())->activas(),
            'proveedores'    => (new Proveedor())->activos(),
            'titulo'         => 'Reporte · Stock',
        ]);
    }

    public function exportarStock(): void
    {
        $this->requireAuth();
        [$soloCriticos, $categoriaId, $proveedorId] = $this->filtrosStock();
        $items = $this->productos->stockReporte($soloCriticos, $categoriaId, $proveedorId);

        $filas = array_map(static fn(array $p): array => [
            $p['codigo'],
            $p['nombre'],
            $p['categoria_nombre']  ?? '',
            $p['proveedor_nombre']  ?? '',
            fmt_cantidad($p['stock_actual']),
            fmt_cantidad($p['stock_minimo']),
            fmt_cantidad($p['faltante']),
            number_format((float) $p['precio_compra'], 2, '.', ''),
            number_format((float) $p['valor_total'],   2, '.', ''),
            ((int) $p['es_critico']) === 1 ? 'Sí' : 'No',
        ], $items);

        Exporter::csv('stock', [
            'Código', 'Producto', 'Categoría', 'Proveedor',
            'Stock actual', 'Stock mínimo', 'Faltante',
            'Precio compra', 'Valor total', 'Crítico',
        ], $filas);
    }

    private function filtrosStock(): array
    {
        $soloCriticos = !empty($_GET['criticos']);
        $categoriaId  = isset($_GET['categoria']) && $_GET['categoria'] !== '' ? (int) $_GET['categoria'] : null;
        $proveedorId  = isset($_GET['proveedor']) && $_GET['proveedor'] !== '' ? (int) $_GET['proveedor'] : null;
        return [$soloCriticos, $categoriaId, $proveedorId];
    }

    /* ---------------------------------------------------------------------
     *  MERMAS / RETAZOS · salidas con motivo merma | accidente.
     *  Dos vistas (agrupada por producto · detalle cronológico) con toggle.
     * ------------------------------------------------------------------- */

    public function mermas(): void
    {
        $this->requireAuth();
        [$vista, $desde, $hasta, $motivo] = $this->filtrosMermas();

        $agrupado = $this->movimientos->mermasPorProducto($desde ?: null, $hasta ?: null, $motivo ?: null);
        $detalle  = $vista === 'detalle'
            ? $this->movimientos->mermasDetalle($desde ?: null, $hasta ?: null, $motivo ?: null)
            : [];

        $totales = [
            'eventos'        => array_sum(array_column($agrupado, 'eventos')),
            'total_perdido'  => array_sum(array_column($agrupado, 'total_perdido')),
            'valor_perdido'  => array_sum(array_map(static fn(array $r): float => (float) $r['valor_perdido'], $agrupado)),
            'total_merma'    => array_sum(array_column($agrupado, 'total_merma')),
            'total_accidente'=> array_sum(array_column($agrupado, 'total_accidente')),
        ];

        $this->render('reportes/mermas', [
            'agrupado' => $agrupado,
            'detalle'  => $detalle,
            'totales'  => $totales,
            'vista'    => $vista,
            'desde'    => $desde,
            'hasta'    => $hasta,
            'motivo'   => $motivo,
            'titulo'   => 'Reporte · Mermas y retazos',
        ]);
    }

    public function exportarMermas(): void
    {
        $this->requireAuth();
        [$vista, $desde, $hasta, $motivo] = $this->filtrosMermas();

        if ($vista === 'detalle') {
            $filas = array_map(static fn(array $r): array => [
                $r['created_at'],
                $r['producto_codigo'],
                $r['producto_nombre'],
                $r['motivo'],
                fmt_cantidad($r['cantidad']),
                $r['usuario_nombre'] ?? '',
                $r['observacion'] ?? '',
            ], $this->movimientos->mermasDetalle($desde ?: null, $hasta ?: null, $motivo ?: null));
            Exporter::csv('mermas_detalle', [
                'Fecha', 'Código', 'Producto', 'Motivo',
                'Cantidad', 'Usuario', 'Observación',
            ], $filas);
            return;
        }

        $filas = array_map(static fn(array $r): array => [
            $r['codigo'],
            $r['nombre'],
            $r['categoria_nombre'] ?? '',
            fmt_cantidad($r['total_merma']),
            fmt_cantidad($r['total_accidente']),
            fmt_cantidad($r['total_perdido']),
            (int) $r['eventos'],
            $r['ultimo_evento'] ?? '',
        ], $this->movimientos->mermasPorProducto($desde ?: null, $hasta ?: null, $motivo ?: null));
        Exporter::csv('mermas_agrupado', [
            'Código', 'Producto', 'Categoría',
            'Merma', 'Accidente', 'Total perdido',
            'Eventos', 'Último evento',
        ], $filas);
    }

    private function filtrosMermas(): array
    {
        $vista  = ($_GET['vista'] ?? 'agrupado') === 'detalle' ? 'detalle' : 'agrupado';
        $desde  = trim((string) ($_GET['desde']  ?? ''));
        $hasta  = trim((string) ($_GET['hasta']  ?? ''));
        $motivo = trim((string) ($_GET['motivo'] ?? ''));
        if (!in_array($motivo, ['merma', 'accidente'], true)) {
            $motivo = '';
        }
        return [$vista, $desde, $hasta, $motivo];
    }

    /* ---------------------------------------------------------------------
     *  VENTAS · sin cambios respecto a la versión anterior.
     * ------------------------------------------------------------------- */

    public function ventas(): void
    {
        $this->requireAuth();
        $agrupacion = $_GET['agrupacion'] ?? 'dia';
        $agrupacion = in_array($agrupacion, ['dia', 'semana', 'mes'], true) ? $agrupacion : 'dia';
        $desde      = trim((string) ($_GET['desde'] ?? date('Y-m-d', strtotime('-29 days'))));
        $hasta      = trim((string) ($_GET['hasta'] ?? date('Y-m-d')));

        $expr = match ($agrupacion) {
            'mes'    => "DATE_FORMAT(m.created_at, '%Y-%m')",
            'semana' => "DATE_FORMAT(m.created_at, '%x-S%v')",
            default  => "DATE(m.created_at)",
        };

        $db = Database::getInstance();
        $stmt = $db->prepare("
            SELECT {$expr} AS periodo,
                   COUNT(*) AS num_ventas,
                   COALESCE(SUM(m.cantidad), 0) AS unidades,
                   COALESCE(SUM(m.cantidad * p.precio_venta), 0) AS ingreso,
                   COALESCE(SUM(m.cantidad * p.precio_compra), 0) AS costo,
                   COALESCE(SUM(m.cantidad * (p.precio_venta - p.precio_compra)), 0) AS utilidad
              FROM movimientos m
              JOIN productos p ON p.id = m.producto_id
             WHERE m.tipo = 'salida'
               AND DATE(m.created_at) BETWEEN :d AND :h
             GROUP BY periodo
             ORDER BY periodo DESC
             LIMIT 200
        ");
        $stmt->execute([':d' => $desde, ':h' => $hasta]);
        $datos = $stmt->fetchAll();

        $totales = [
            'num_ventas' => array_sum(array_column($datos, 'num_ventas')),
            'unidades'   => array_sum(array_column($datos, 'unidades')),
            'ingreso'    => array_sum(array_column($datos, 'ingreso')),
            'costo'      => array_sum(array_column($datos, 'costo')),
            'utilidad'   => array_sum(array_column($datos, 'utilidad')),
        ];

        $this->render('reportes/ventas_periodo', [
            'datos'      => $datos,
            'totales'    => $totales,
            'agrupacion' => $agrupacion,
            'desde'      => $desde,
            'hasta'      => $hasta,
            'titulo'     => 'Reporte · Ventas por período',
        ]);
    }

    public function exportarVentas(): void
    {
        $this->requireAuth();
        $agrupacion = $_GET['agrupacion'] ?? 'dia';
        $agrupacion = in_array($agrupacion, ['dia', 'semana', 'mes'], true) ? $agrupacion : 'dia';
        $desde      = trim((string) ($_GET['desde'] ?? date('Y-m-d', strtotime('-29 days'))));
        $hasta      = trim((string) ($_GET['hasta'] ?? date('Y-m-d')));
        $expr = match ($agrupacion) {
            'mes'    => "DATE_FORMAT(m.created_at, '%Y-%m')",
            'semana' => "DATE_FORMAT(m.created_at, '%x-S%v')",
            default  => "DATE(m.created_at)",
        };
        $stmt = Database::getInstance()->prepare("
            SELECT {$expr} AS periodo,
                   COUNT(*) AS num_ventas,
                   COALESCE(SUM(m.cantidad), 0) AS unidades,
                   COALESCE(SUM(m.cantidad * p.precio_venta), 0) AS ingreso,
                   COALESCE(SUM(m.cantidad * p.precio_compra), 0) AS costo,
                   COALESCE(SUM(m.cantidad * (p.precio_venta - p.precio_compra)), 0) AS utilidad
              FROM movimientos m JOIN productos p ON p.id = m.producto_id
             WHERE m.tipo = 'salida' AND DATE(m.created_at) BETWEEN :d AND :h
             GROUP BY periodo ORDER BY periodo DESC LIMIT 5000
        ");
        $stmt->execute([':d' => $desde, ':h' => $hasta]);
        $filas = array_map(fn($r) => [
            $r['periodo'],
            (int) $r['num_ventas'],
            (int) $r['unidades'],
            number_format((float) $r['ingreso'],  2, '.', ''),
            number_format((float) $r['costo'],    2, '.', ''),
            number_format((float) $r['utilidad'], 2, '.', ''),
        ], $stmt->fetchAll());
        Exporter::csv('ventas_periodo', [
            'Período', 'Ventas', 'Unidades', 'Ingreso', 'Costo', 'Utilidad',
        ], $filas);
    }

    /* ---------------------------------------------------------------------
     *  CONSOLIDADO DE PROVEEDORES · resumen + endpoint de detalle (modal).
     * ------------------------------------------------------------------- */

    public function consolidadoProveedores(): void
    {
        $this->requireAuth();
        $db = Database::getInstance();

        $datos = $db->query("
            SELECT pr.id, pr.nombre, pr.descripcion_productos,
                   pr.estado, pr.email, pr.telefono,
                   pa.nombre  AS pais,
                   ci.nombre  AS ciudad,
                   COALESCE(prods.total_productos, 0) AS total_productos,
                   COALESCE(prods.valor_inventario, 0) AS valor_inventario,
                   COALESCE(peds.total_pedidos, 0) AS total_pedidos,
                   COALESCE(peds.total_comprado, 0) AS total_comprado,
                   COALESCE(peds.total_pagado, 0) AS total_pagado,
                   COALESCE(peds.deuda_activa, 0) AS deuda_activa,
                   COALESCE(peds.pedidos_pendientes, 0) AS pedidos_pendientes
              FROM proveedores pr
              LEFT JOIN paises   pa ON pa.id = pr.pais_id
              LEFT JOIN ciudades ci ON ci.id = pr.ciudad_id
              LEFT JOIN (
                  SELECT proveedor_id,
                         COUNT(*)  AS total_productos,
                         COALESCE(SUM(stock_actual * precio_compra), 0) AS valor_inventario
                    FROM productos
                   WHERE estado = 1 AND proveedor_id IS NOT NULL
                   GROUP BY proveedor_id
              ) prods ON prods.proveedor_id = pr.id
              LEFT JOIN (
                  SELECT proveedor_id,
                         COUNT(*) AS total_pedidos,
                         COALESCE(SUM(total),  0) AS total_comprado,
                         COALESCE(SUM(pagado), 0) AS total_pagado,
                         COALESCE(SUM(CASE WHEN estado <> 'pagado' THEN total - pagado ELSE 0 END), 0) AS deuda_activa,
                         SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pedidos_pendientes
                    FROM pedidos
                   WHERE proveedor_id IS NOT NULL
                   GROUP BY proveedor_id
              ) peds ON peds.proveedor_id = pr.id
             ORDER BY pr.nombre ASC
        ")->fetchAll();

        $totales = [
            'total_productos'  => array_sum(array_column($datos, 'total_productos')),
            'valor_inventario' => array_sum(array_column($datos, 'valor_inventario')),
            'total_pedidos'    => array_sum(array_column($datos, 'total_pedidos')),
            'total_comprado'   => array_sum(array_column($datos, 'total_comprado')),
            'total_pagado'     => array_sum(array_column($datos, 'total_pagado')),
            'deuda_activa'     => array_sum(array_column($datos, 'deuda_activa')),
        ];

        $this->render('reportes/consolidado_proveedores', [
            'datos'   => $datos,
            'totales' => $totales,
            'titulo'  => 'Reporte · Consolidado de proveedores',
        ]);
    }

    /** Detalle de un proveedor — renderizado parcial para el modal. */
    public function proveedorDetalle(string $id = '0'): void
    {
        $this->requireAuth();
        $id = (int) $id;
        $db = Database::getInstance();

        $stmt = $db->prepare("
            SELECT pr.id, pr.nombre, pr.descripcion_productos,
                   pr.estado, pr.email, pr.telefono, pr.direccion,
                   pa.nombre AS pais,
                   ci.nombre AS ciudad,
                   COALESCE(prods.total_productos, 0)   AS total_productos,
                   COALESCE(prods.valor_inventario, 0)  AS valor_inventario,
                   COALESCE(peds.total_pedidos, 0)      AS total_pedidos,
                   COALESCE(peds.total_comprado, 0)     AS total_comprado,
                   COALESCE(peds.total_pagado, 0)       AS total_pagado,
                   COALESCE(peds.deuda_activa, 0)       AS deuda_activa,
                   COALESCE(peds.pedidos_pendientes, 0) AS pedidos_pendientes
              FROM proveedores pr
              LEFT JOIN paises   pa ON pa.id = pr.pais_id
              LEFT JOIN ciudades ci ON ci.id = pr.ciudad_id
              LEFT JOIN (
                  SELECT proveedor_id,
                         COUNT(*) AS total_productos,
                         COALESCE(SUM(stock_actual * precio_compra), 0) AS valor_inventario
                    FROM productos
                   WHERE estado = 1 AND proveedor_id IS NOT NULL
                   GROUP BY proveedor_id
              ) prods ON prods.proveedor_id = pr.id
              LEFT JOIN (
                  SELECT proveedor_id,
                         COUNT(*) AS total_pedidos,
                         COALESCE(SUM(total),  0) AS total_comprado,
                         COALESCE(SUM(pagado), 0) AS total_pagado,
                         COALESCE(SUM(CASE WHEN estado <> 'pagado' THEN total - pagado ELSE 0 END), 0) AS deuda_activa,
                         SUM(CASE WHEN estado = 'pendiente' THEN 1 ELSE 0 END) AS pedidos_pendientes
                    FROM pedidos
                   WHERE proveedor_id IS NOT NULL
                   GROUP BY proveedor_id
              ) peds ON peds.proveedor_id = pr.id
             WHERE pr.id = :id LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $proveedor = $stmt->fetch();

        if ($proveedor === false) {
            if ($this->isAjax()) {
                http_response_code(404);
                echo '<p class="modal__error">Proveedor no encontrado.</p>';
                return;
            }
            $this->setFlash('error', 'Proveedor no encontrado.');
            $this->redirect('/reporte/consolidadoProveedores');
        }

        $productos = $this->productos->porProveedor($id);

        $pedStmt = $db->prepare("
            SELECT p.id, p.numero, p.fecha_pedido, p.fecha_entrega,
                   p.total, p.pagado, p.estado,
                   (p.total - p.pagado) AS saldo,
                   u.nombre AS usuario_nombre
              FROM pedidos p
              LEFT JOIN usuarios u ON u.id = p.usuario_id
             WHERE p.proveedor_id = :pid
             ORDER BY p.fecha_pedido DESC, p.id DESC
             LIMIT 50
        ");
        $pedStmt->execute([':pid' => $id]);
        $pedidos = $pedStmt->fetchAll();

        $viewData = [
            'proveedor' => $proveedor,
            'productos' => $productos,
            'pedidos'   => $pedidos,
            'titulo'    => $proveedor['nombre'],
        ];
        if ($this->isAjax()) {
            $this->render('reportes/_proveedor_detalle', $viewData, withLayout: false);
            return;
        }
        $this->render('reportes/_proveedor_detalle', $viewData);
    }

    public function exportarConsolidadoProveedores(): void
    {
        $this->requireAuth();
        $datos = Database::getInstance()->query("
            SELECT pr.nombre, pa.nombre AS pais, ci.nombre AS ciudad,
                   pr.email, pr.telefono,
                   COALESCE(prods.total_productos, 0)   AS total_productos,
                   COALESCE(prods.valor_inventario, 0)  AS valor_inventario,
                   COALESCE(peds.total_pedidos, 0)      AS total_pedidos,
                   COALESCE(peds.total_comprado, 0)     AS total_comprado,
                   COALESCE(peds.total_pagado, 0)       AS total_pagado,
                   COALESCE(peds.deuda_activa, 0)       AS deuda_activa
              FROM proveedores pr
              LEFT JOIN paises   pa ON pa.id = pr.pais_id
              LEFT JOIN ciudades ci ON ci.id = pr.ciudad_id
              LEFT JOIN (
                  SELECT proveedor_id,
                         COUNT(*) AS total_productos,
                         COALESCE(SUM(stock_actual * precio_compra), 0) AS valor_inventario
                    FROM productos WHERE estado = 1 AND proveedor_id IS NOT NULL
                   GROUP BY proveedor_id
              ) prods ON prods.proveedor_id = pr.id
              LEFT JOIN (
                  SELECT proveedor_id,
                         COUNT(*) AS total_pedidos,
                         COALESCE(SUM(total),  0) AS total_comprado,
                         COALESCE(SUM(pagado), 0) AS total_pagado,
                         COALESCE(SUM(CASE WHEN estado <> 'pagado' THEN total - pagado ELSE 0 END), 0) AS deuda_activa
                    FROM pedidos WHERE proveedor_id IS NOT NULL GROUP BY proveedor_id
              ) peds ON peds.proveedor_id = pr.id
             ORDER BY pr.nombre ASC
        ")->fetchAll();
        $filas = array_map(fn($r) => [
            $r['nombre'],
            $r['pais']   ?? '',
            $r['ciudad'] ?? '',
            $r['email']   ?? '',
            $r['telefono'] ?? '',
            (int) $r['total_productos'],
            number_format((float) $r['valor_inventario'], 2, '.', ''),
            (int) $r['total_pedidos'],
            number_format((float) $r['total_comprado'], 2, '.', ''),
            number_format((float) $r['total_pagado'],   2, '.', ''),
            number_format((float) $r['deuda_activa'],   2, '.', ''),
        ], $datos);
        Exporter::csv('consolidado_proveedores', [
            'Proveedor', 'País', 'Ciudad', 'Email', 'Teléfono',
            'Productos', 'Valor inventario', 'Pedidos',
            'Total comprado', 'Total pagado', 'Deuda activa',
        ], $filas);
    }
}
