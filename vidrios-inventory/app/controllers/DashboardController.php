<?php
declare(strict_types=1);

final class DashboardController extends Controller
{
    public function index(): void
    {
        $this->requireAuth();
        $db = Database::getInstance();

        // ─── KPIs principales ───────────────────────────────────────────────
        $totalProductos = (int) $db->query(
            "SELECT COUNT(*) FROM productos WHERE estado = 1"
        )->fetchColumn();

        $stockBajo = (int) $db->query(
            "SELECT COUNT(*) FROM productos WHERE estado = 1 AND stock_actual <= stock_minimo"
        )->fetchColumn();

        $valorInventario = (float) $db->query(
            "SELECT COALESCE(SUM(stock_actual * precio_compra), 0)
               FROM productos WHERE estado = 1"
        )->fetchColumn();

        $totalCategorias = (int) $db->query(
            "SELECT COUNT(*) FROM categorias WHERE estado = 1"
        )->fetchColumn();

        $totalProveedores = (int) $db->query(
            "SELECT COUNT(*) FROM proveedores WHERE estado = 1"
        )->fetchColumn();

        $movHoy = (int) $db->query(
            "SELECT COUNT(*) FROM movimientos WHERE DATE(created_at) = CURDATE()"
        )->fetchColumn();

        // ─── Comparativo: últimos 7 días vs 7 anteriores ────────────────────
        $compRow = $db->query("
            SELECT
              SUM(CASE WHEN created_at >= NOW() - INTERVAL 7  DAY THEN 1 ELSE 0 END) AS sem_actual,
              SUM(CASE WHEN created_at <  NOW() - INTERVAL 7  DAY
                    AND created_at >= NOW() - INTERVAL 14 DAY THEN 1 ELSE 0 END) AS sem_previa
              FROM movimientos
        ")->fetch() ?: ['sem_actual' => 0, 'sem_previa' => 0];

        $movSemana   = (int) $compRow['sem_actual'];
        $movPrevSem  = (int) $compRow['sem_previa'];
        $movTrendPct = $movPrevSem > 0
            ? (($movSemana - $movPrevSem) / $movPrevSem) * 100
            : ($movSemana > 0 ? 100.0 : 0.0);

        // Entradas vs salidas en la última semana (unidades)
        $flujoSemana = $db->query("
            SELECT
              COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN cantidad END), 0) AS entradas,
              COALESCE(SUM(CASE WHEN tipo = 'salida'  THEN cantidad END), 0) AS salidas
              FROM movimientos
             WHERE created_at >= NOW() - INTERVAL 7 DAY
        ")->fetch() ?: ['entradas' => 0, 'salidas' => 0];

        // ─── Serie diaria de movimientos (últimos 14 días) ──────────────────
        // Generamos primero el rango completo en PHP para mostrar días con cero.
        $movPorDia = $db->query("
            SELECT DATE(created_at) AS dia,
                   COALESCE(SUM(CASE WHEN tipo = 'entrada' THEN cantidad END), 0) AS entradas,
                   COALESCE(SUM(CASE WHEN tipo = 'salida'  THEN cantidad END), 0) AS salidas
              FROM movimientos
             WHERE created_at >= CURDATE() - INTERVAL 13 DAY
             GROUP BY DATE(created_at)
        ")->fetchAll();

        $serie14 = [];
        $indice  = [];
        foreach ($movPorDia as $row) {
            $indice[$row['dia']] = $row;
        }
        for ($i = 13; $i >= 0; $i--) {
            $fecha = date('Y-m-d', strtotime("-{$i} days"));
            $r = $indice[$fecha] ?? ['entradas' => 0, 'salidas' => 0];
            $serie14[] = [
                'fecha'    => $fecha,
                'entradas' => (int) $r['entradas'],
                'salidas'  => (int) $r['salidas'],
            ];
        }

        // ─── Distribución de valor por categoría ────────────────────────────
        $valorPorCategoria = $db->query("
            SELECT COALESCE(c.nombre, 'Sin categoría') AS categoria,
                   COUNT(p.id) AS productos,
                   COALESCE(SUM(p.stock_actual * p.precio_compra), 0) AS valor
              FROM productos p
              LEFT JOIN categorias c ON c.id = p.categoria_id
             WHERE p.estado = 1
             GROUP BY p.categoria_id, c.nombre
             HAVING valor > 0
             ORDER BY valor DESC
        ")->fetchAll();

        // ─── Top 5 productos más movidos (últimos 30 días) ──────────────────
        $topProductos = $db->query("
            SELECT p.id, p.codigo, p.nombre, p.stock_actual,
                   c.nombre AS categoria_nombre,
                   COUNT(m.id) AS num_movimientos,
                   COALESCE(SUM(m.cantidad), 0) AS unidades_movidas
              FROM productos p
              JOIN movimientos m ON m.producto_id = p.id
              LEFT JOIN categorias c ON c.id = p.categoria_id
             WHERE p.estado = 1
               AND m.created_at >= NOW() - INTERVAL 30 DAY
             GROUP BY p.id
             ORDER BY num_movimientos DESC, unidades_movidas DESC
             LIMIT 5
        ")->fetchAll();

        $ultimosMovs = $db->query("
            SELECT m.id, m.tipo, m.cantidad, m.created_at,
                   p.codigo AS producto_codigo, p.nombre AS producto_nombre,
                   u.nombre AS usuario_nombre
              FROM movimientos m
              LEFT JOIN productos p ON p.id = m.producto_id
              LEFT JOIN usuarios u ON u.id = m.usuario_id
             ORDER BY m.created_at DESC
             LIMIT 5
        ")->fetchAll();

        $topStockBajo = $db->query("
            SELECT p.codigo, p.nombre, p.stock_actual, p.stock_minimo,
                   c.nombre AS categoria_nombre
              FROM productos p
              LEFT JOIN categorias c ON c.id = p.categoria_id
             WHERE p.estado = 1 AND p.stock_actual <= p.stock_minimo
             ORDER BY (p.stock_actual / NULLIF(p.stock_minimo, 0)) ASC
             LIMIT 5
        ")->fetchAll();

        $this->render('dashboard/index', [
            'titulo'             => 'Tablero',
            'totalProductos'     => $totalProductos,
            'stockBajo'          => $stockBajo,
            'valorInventario'    => $valorInventario,
            'totalCategorias'    => $totalCategorias,
            'totalProveedores'   => $totalProveedores,
            'movHoy'             => $movHoy,
            'movSemana'          => $movSemana,
            'movPrevSem'         => $movPrevSem,
            'movTrendPct'        => $movTrendPct,
            'entradasSemana'     => (int) $flujoSemana['entradas'],
            'salidasSemana'      => (int) $flujoSemana['salidas'],
            'serie14'            => $serie14,
            'valorPorCategoria'  => $valorPorCategoria,
            'topProductos'       => $topProductos,
            'ultimosMovimientos' => $ultimosMovs,
            'topStockBajo'       => $topStockBajo,
        ]);
    }
}
