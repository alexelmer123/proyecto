<?php
declare(strict_types=1);

final class Exporter
{
    /**
     * Envía un archivo CSV al navegador listo para descargar.
     *
     * @param string   $nombre    Nombre base del archivo (sin extensión ni fecha).
     * @param string[] $columnas  Cabeceras de las columnas.
     * @param array    $filas     Arreglo de filas; cada fila es un arreglo de valores.
     */
    public static function csv(string $nombre, array $columnas, array $filas): never
    {
        $archivo = $nombre . '_' . date('Y-m-d') . '.csv';

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $archivo . '"');
        header('Cache-Control: max-age=0');
        header('Pragma: public');

        $out = fopen('php://output', 'wb');
        fwrite($out, "\xEF\xBB\xBF"); // BOM — Excel necesita esto para mostrar tildes correctamente

        fputcsv($out, $columnas, ';');
        foreach ($filas as $fila) {
            fputcsv($out, array_values((array) $fila), ';');
        }
        fclose($out);
        exit;
    }
}
