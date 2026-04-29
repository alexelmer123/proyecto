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
        $this->stockBajo();
    }

    public function exportarStockBajo(): void
    {
        $this->requireAuth();
        $filas = array_map(fn($p) => [
            $p['codigo'],
            $p['nombre'],
            $p['categoria_nombre'] ?? '',
            (int) $p['stock_actual'],
            (int) $p['stock_minimo'],
            max(0, (int) $p['stock_minimo'] - (int) $p['stock_actual']),
        ], $this->productos->stockBajo());
        Exporter::csv('stock_bajo', [
            'Código', 'Producto', 'Categoría', 'Stock actual', 'Stock mínimo', 'Faltante',
        ], $filas);
    }

    public function exportarValorInventario(): void
    {
        $this->requireAuth();
        $filas = array_map(fn($p) => [
            $p['codigo'],
            $p['nombre'],
            $p['categoria_nombre'] ?? '',
            (int) $p['stock_actual'],
            number_format((float) $p['precio_compra'], 2, '.', ''),
            number_format((float) $p['valor_total'],   2, '.', ''),
        ], $this->productos->valorInventario());
        Exporter::csv('valor_inventario', [
            'Código', 'Producto', 'Categoría', 'Stock', 'Precio compra', 'Valor total',
        ], $filas);
    }

    public function exportarMovimientosPorPeriodo(): void
    {
        $this->requireAuth();
        $agrupacion = ($_GET['agrupacion'] ?? 'dia') === 'mes' ? 'mes' : 'dia';
        $desde      = trim((string) ($_GET['desde'] ?? ''));
        $hasta      = trim((string) ($_GET['hasta'] ?? ''));
        $datos = $this->movimientos->agruparPorPeriodo(
            $agrupacion,
            $desde !== '' ? $desde : null,
            $hasta !== '' ? $hasta : null
        );
        $filas = array_map(fn($d) => [
            $d['periodo'],
            (int) $d['total_entradas'],
            (int) $d['total_salidas'],
            (int) $d['total_movimientos'],
        ], $datos);
        Exporter::csv('movimientos_periodo', [
            'Período', 'Total entradas', 'Total salidas', 'Total movimientos',
        ], $filas);
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

    public function stockBajo(): void
    {
        $this->requireAuth();
        $criticos = $this->productos->stockBajo();
        $this->render('reportes/stock_bajo', [
            'productos' => $criticos,
            'titulo'    => 'Reporte · Stock crítico',
        ]);
    }

    public function valorInventario(): void
    {
        $this->requireAuth();
        $items = $this->productos->valorInventario();
        $total = array_sum(array_map(static fn(array $r): float => (float) $r['valor_total'], $items));
        $this->render('reportes/valor_inventario', [
            'productos' => $items,
            'total'     => $total,
            'titulo'    => 'Reporte · Valor del inventario',
        ]);
    }

    public function movimientosPorPeriodo(): void
    {
        $this->requireAuth();
        $agrupacion = ($_GET['agrupacion'] ?? 'dia') === 'mes' ? 'mes' : 'dia';
        $desde      = trim((string) ($_GET['desde'] ?? ''));
        $hasta      = trim((string) ($_GET['hasta'] ?? ''));

        $datos = $this->movimientos->agruparPorPeriodo(
            $agrupacion,
            $desde !== '' ? $desde : null,
            $hasta !== '' ? $hasta : null
        );

        $this->render('reportes/movimientos_periodo', [
            'datos'      => $datos,
            'agrupacion' => $agrupacion,
            'desde'      => $desde,
            'hasta'      => $hasta,
            'titulo'     => 'Reporte · Movimientos por período',
        ]);
    }

    /**
     * Reporte de ventas (= salidas) periodizado por día/semana/mes.
     * Las "ventas" en este modelo son los movimientos de tipo 'salida':
     * cantidad × precio_venta del producto.
     */
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

    /**
     * Consolidado por proveedor: cuántos productos suministra, valor de
     * compra acumulado, pedidos, y deuda activa.
     */
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
}
