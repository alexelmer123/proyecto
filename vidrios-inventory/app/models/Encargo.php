<?php
declare(strict_types=1);

final class Encargo extends BaseModel
{
    protected string $table = 'encargos';

    public const ESTADO_PENDIENTE = 'pendiente';
    public const ESTADO_ENTREGADO = 'entregado';
    public const ESTADO_CANCELADO = 'cancelado';

    /**
     * Crea un encargo con sus items y registra los N movimientos de salida
     * en una sola transacción. Si algún producto no tiene stock suficiente,
     * se hace rollback completo y no se descuenta nada.
     *
     * @param array<string, mixed> $datos
     * @param array<int, array{producto_id: int, cantidad: int, precio_unitario?: ?float}> $items
     */
    public function crearConItems(array $datos, array $items, int $usuarioId): int
    {
        if ($items === []) {
            throw new RuntimeException('Debes añadir al menos un producto al encargo.');
        }

        // Si el id del usuario no corresponde a una fila real, usamos NULL
        // (relación lógica, sin FK; el código preserva la integridad).
        $usuarioIdFk = $this->resolverUsuarioId($usuarioId);

        $this->db->beginTransaction();
        try {
            // 1) Codigo único autogenerado
            $codigo = $this->generarCodigoUnico();

            // 2) Insertar encargo
            $insE = $this->db->prepare(
                "INSERT INTO encargos
                    (codigo, cliente, telefono, lugar_entrega, fecha_entrega,
                     detalles, estado, usuario_id, created_at)
                 VALUES (:cod, :cli, :tel, :lug, :fe, :det, :est, :u, NOW())"
            );
            $insE->bindValue(':cod', $codigo);
            $insE->bindValue(':cli', $datos['cliente']);
            $insE->bindValue(':tel', $datos['telefono'] ?? null);
            $insE->bindValue(':lug', $datos['lugar_entrega'] ?? null);
            $insE->bindValue(':fe',  $datos['fecha_entrega'] ?? null);
            $insE->bindValue(':det', $datos['detalles'] ?? null);
            $insE->bindValue(':est', self::ESTADO_PENDIENTE);
            $insE->bindValue(':u',   $usuarioIdFk,
                $usuarioIdFk === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
            $insE->execute();
            $encargoId = (int) $this->db->lastInsertId();

            // 3) Insertar items y registrar movimientos
            $insI = $this->db->prepare(
                "INSERT INTO encargo_items (encargo_id, producto_id, cantidad, precio_unitario)
                 VALUES (:e, :p, :c, :pu)"
            );
            $movs = new Movimiento();
            foreach ($items as $it) {
                $pid = (int) ($it['producto_id'] ?? 0);
                $qty = (int) ($it['cantidad']    ?? 0);
                if ($pid <= 0 || $qty <= 0) {
                    throw new RuntimeException('Item de encargo inválido.');
                }
                $precio = isset($it['precio_unitario']) && $it['precio_unitario'] !== ''
                    ? (float) $it['precio_unitario'] : null;

                $insI->bindValue(':e',  $encargoId, PDO::PARAM_INT);
                $insI->bindValue(':p',  $pid,       PDO::PARAM_INT);
                $insI->bindValue(':c',  $qty,       PDO::PARAM_INT);
                $insI->bindValue(':pu', $precio === null ? null : number_format($precio, 2, '.', ''));
                $insI->execute();

                // Salida con motivo=encargo y ligada al encargo_id.
                // Movimiento::registrar detecta que ya hay tx y no abre otra.
                $movs->registrar(
                    productoId: $pid,
                    tipo: Movimiento::TIPO_SALIDA,
                    cantidad: $qty,
                    usuarioId: $usuarioIdFk ?? 0,
                    observacion: "Encargo {$codigo}",
                    motivo: Movimiento::MOTIVO_ENCARGO,
                    cliente: (string) $datos['cliente'],
                    fechaEntrega: $datos['fecha_entrega'] ?? null,
                    encargoId: $encargoId,
                );
            }

            $this->db->commit();
            return $encargoId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Actualiza un encargo pendiente reemplazando datos e items en una sola
     * transacción. La estrategia es "devolver y rehacer": devuelve el stock
     * de los items actuales (entradas), borra los items, inserta los nuevos
     * y registra las salidas. Más simple y atómico que diff por producto.
     *
     * @param array<string, mixed> $datos
     * @param array<int, array<string, mixed>> $items
     */
    public function actualizarConItems(int $encargoId, array $datos, array $items, int $usuarioId): void
    {
        $encargo = $this->findById($encargoId);
        if ($encargo === null) {
            throw new RuntimeException('Encargo no encontrado.');
        }
        if ($encargo['estado'] !== self::ESTADO_PENDIENTE) {
            throw new RuntimeException('Sólo se puede editar un encargo pendiente.');
        }
        if ($items === []) {
            throw new RuntimeException('El encargo debe tener al menos un producto.');
        }

        $usuarioIdFk = $this->resolverUsuarioId($usuarioId);
        $itemsActuales = $this->itemsDe($encargoId);

        $this->db->beginTransaction();
        try {
            $movs = new Movimiento();

            // 1) Devolver el stock de los items actuales (entradas).
            $nota = "Edición encargo {$encargo['codigo']}: reposición previa";
            foreach ($itemsActuales as $it) {
                $movs->registrar(
                    productoId: (int) $it['producto_id'],
                    tipo: Movimiento::TIPO_ENTRADA,
                    cantidad: (int) $it['cantidad'],
                    usuarioId: $usuarioIdFk ?? 0,
                    observacion: $nota,
                    encargoId: $encargoId,
                );
            }

            // 2) Borrar items actuales.
            $del = $this->db->prepare("DELETE FROM encargo_items WHERE encargo_id = :id");
            $del->execute([':id' => $encargoId]);

            // 3) Actualizar datos del encargo.
            $upd = $this->db->prepare(
                "UPDATE encargos
                    SET cliente = :cli, telefono = :tel,
                        lugar_entrega = :lug, fecha_entrega = :fe,
                        detalles = :det
                  WHERE id = :id"
            );
            $upd->bindValue(':cli', $datos['cliente']);
            $upd->bindValue(':tel', $datos['telefono'] ?? null);
            $upd->bindValue(':lug', $datos['lugar_entrega'] ?? null);
            $upd->bindValue(':fe',  $datos['fecha_entrega'] ?? null);
            $upd->bindValue(':det', $datos['detalles'] ?? null);
            $upd->bindValue(':id',  $encargoId, PDO::PARAM_INT);
            $upd->execute();

            // 4) Insertar nuevos items y registrar salidas.
            $insI = $this->db->prepare(
                "INSERT INTO encargo_items (encargo_id, producto_id, cantidad, precio_unitario)
                 VALUES (:e, :p, :c, :pu)"
            );
            foreach ($items as $it) {
                $pid = (int) ($it['producto_id'] ?? 0);
                $qty = (int) ($it['cantidad']    ?? 0);
                if ($pid <= 0 || $qty <= 0) {
                    throw new RuntimeException('Item de encargo inválido.');
                }
                $precio = isset($it['precio_unitario']) && $it['precio_unitario'] !== ''
                    ? (float) $it['precio_unitario'] : null;

                $insI->bindValue(':e',  $encargoId, PDO::PARAM_INT);
                $insI->bindValue(':p',  $pid,       PDO::PARAM_INT);
                $insI->bindValue(':c',  $qty,       PDO::PARAM_INT);
                $insI->bindValue(':pu', $precio === null ? null : number_format($precio, 2, '.', ''));
                $insI->execute();

                $movs->registrar(
                    productoId: $pid,
                    tipo: Movimiento::TIPO_SALIDA,
                    cantidad: $qty,
                    usuarioId: $usuarioIdFk ?? 0,
                    observacion: "Edición encargo {$encargo['codigo']}",
                    motivo: Movimiento::MOTIVO_ENCARGO,
                    cliente: (string) $datos['cliente'],
                    fechaEntrega: $datos['fecha_entrega'] ?? null,
                    encargoId: $encargoId,
                );
            }

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Cancela un encargo pendiente y devuelve el stock con N movimientos de entrada.
     */
    public function cancelar(int $encargoId, int $usuarioId, string $motivoTexto = ''): void
    {
        $encargo = $this->findById($encargoId);
        if ($encargo === null) {
            throw new RuntimeException('Encargo no encontrado.');
        }
        if ($encargo['estado'] !== self::ESTADO_PENDIENTE) {
            throw new RuntimeException('Sólo se puede cancelar un encargo pendiente.');
        }

        $items = $this->itemsDe($encargoId);

        $this->db->beginTransaction();
        try {
            $this->update($encargoId, ['estado' => self::ESTADO_CANCELADO]);

            $movs = new Movimiento();
            $nota = trim('Cancelación encargo ' . $encargo['codigo'] . ($motivoTexto !== '' ? " — {$motivoTexto}" : ''));
            foreach ($items as $it) {
                $movs->registrar(
                    productoId: (int) $it['producto_id'],
                    tipo: Movimiento::TIPO_ENTRADA,
                    cantidad: (int) $it['cantidad'],
                    usuarioId: $usuarioId,
                    observacion: $nota,
                    encargoId: $encargoId,
                );
            }

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Marca el encargo como entregado. El stock principal ya se descontó al
     * crear el encargo; aquí sólo se procesan las mermas/retazos detectados
     * al momento de entregar:
     *   - merma/accidente → registran un movimiento de salida adicional
     *     ligado al encargo (descuentan stock extra).
     *   - retazo → no afecta stock, se consolida como texto en encargos.notas_entrega.
     *
     * Cada fila incluye `medidas` (sub-array clave→valor con las dimensiones del
     * retazo/merma según la unidad del producto). Se consolidan como texto en
     * la observación del movimiento (mermas/accidentes) o en notas_entrega (retazos).
     *
     * @param array<int, array{producto_id:int, cantidad:float, motivo:string, medidas:array<string,float>}> $mermas
     */
    public function entregar(int $encargoId, int $usuarioId, array $mermas = []): void
    {
        $encargo = $this->findById($encargoId);
        if ($encargo === null) {
            throw new RuntimeException('Encargo no encontrado.');
        }
        if ($encargo['estado'] !== self::ESTADO_PENDIENTE) {
            throw new RuntimeException('Sólo se puede entregar un encargo pendiente.');
        }

        $usuarioIdFk = $this->resolverUsuarioId($usuarioId);

        $this->db->beginTransaction();
        try {
            $movs    = new Movimiento();
            $retazoModel = new Retazo();
            $retazosCount = 0;

            foreach ($mermas as $m) {
                $pid    = (int) ($m['producto_id'] ?? 0);
                $cant   = (float) ($m['cantidad']  ?? 0);
                $motivo = (string) ($m['motivo']   ?? Movimiento::MOTIVO_MERMA);
                $medidas = is_array($m['medidas'] ?? null) ? $m['medidas'] : [];
                if ($pid <= 0 || $cant <= 0) continue;

                $medidasTxt = self::formatMedidas($medidas);

                if ($motivo === Movimiento::MOTIVO_RETAZO) {
                    $retazoModel->registrar(
                        productoId: $pid,
                        cantidad: $cant,
                        medidas: $medidas,
                        origen: Retazo::ORIGEN_ENCARGO,
                        origenId: $encargoId,
                        observacion: null,
                        usuarioId: $usuarioIdFk,
                    );
                    $retazosCount++;
                    continue;
                }
                $movs->registrar(
                    productoId: $pid,
                    tipo: Movimiento::TIPO_SALIDA,
                    cantidad: $cant,
                    usuarioId: $usuarioIdFk ?? 0,
                    observacion: 'Entrega encargo ' . $encargo['codigo']
                        . ($medidasTxt !== '' ? ' — ' . $medidasTxt : ''),
                    motivo: $motivo,
                    encargoId: $encargoId,
                );
            }

            // notas_entrega ahora se queda como nota narrativa libre (vacío en V1
            // porque ya no consolidamos retazos aquí — viven en su tabla).
            $upd = $this->db->prepare(
                "UPDATE encargos
                    SET estado = :est
                  WHERE id = :id"
            );
            $upd->bindValue(':est', self::ESTADO_ENTREGADO);
            $upd->bindValue(':id',  $encargoId, PDO::PARAM_INT);
            $upd->execute();

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Convierte las medidas dimensionales del retazo/merma a texto compacto:
     *   ['ancho' => 20, 'alto' => 30] → "20×30 cm"
     *   ['longitud' => 50]            → "50 cm"
     *   []                            → ""
     */
    private static function formatMedidas(array $medidas): string
    {
        if ($medidas === []) return '';
        $valores = array_map(static function ($v) {
            return rtrim(rtrim(number_format((float) $v, 2, '.', ''), '0'), '.');
        }, $medidas);
        return implode('×', $valores) . ' cm';
    }

    /** Listado paginado con conteo de items y total. */
    public function listadoConResumen(?string $estado = null, ?int $limit = null, int $offset = 0): array
    {
        $sql = "
            SELECT e.*, u.nombre AS usuario_nombre,
                   (SELECT COUNT(*) FROM encargo_items ei WHERE ei.encargo_id = e.id)
                       AS items_count,
                   (SELECT SUM(ei.cantidad) FROM encargo_items ei WHERE ei.encargo_id = e.id)
                       AS total_unidades,
                   (SELECT SUM(ei.cantidad * COALESCE(ei.precio_unitario, 0))
                      FROM encargo_items ei WHERE ei.encargo_id = e.id)
                       AS total_valor
              FROM encargos e
              LEFT JOIN usuarios u ON u.id = e.usuario_id
        ";
        $params = [];
        if ($estado !== null && $estado !== '') {
            $sql .= " WHERE e.estado = :est";
            $params[':est'] = $estado;
        }
        $sql .= " ORDER BY e.created_at DESC, e.id DESC";
        if ($limit !== null) {
            $sql .= " LIMIT :__limit__ OFFSET :__offset__";
        }
        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        if ($limit !== null) {
            $stmt->bindValue(':__limit__',  $limit,  PDO::PARAM_INT);
            $stmt->bindValue(':__offset__', $offset, PDO::PARAM_INT);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function contar(?string $estado = null): int
    {
        $sql = "SELECT COUNT(*) FROM encargos";
        $params = [];
        if ($estado !== null && $estado !== '') {
            $sql .= " WHERE estado = :est";
            $params[':est'] = $estado;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }

    /** Trae un encargo con info enriquecida (nombre del usuario). */
    public function findEnriquecido(int $id): ?array
    {
        $stmt = $this->db->prepare("
            SELECT e.*, u.nombre AS usuario_nombre
              FROM encargos e
              LEFT JOIN usuarios u ON u.id = e.usuario_id
             WHERE e.id = :id
             LIMIT 1
        ");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? $row : null;
    }

    /** @return array<int, array<string, mixed>> Items del encargo con datos del producto. */
    public function itemsDe(int $encargoId): array
    {
        $stmt = $this->db->prepare("
            SELECT ei.*, p.codigo AS producto_codigo, p.nombre AS producto_nombre,
                   p.unidad AS producto_unidad
              FROM encargo_items ei
              INNER JOIN productos p ON p.id = ei.producto_id
             WHERE ei.encargo_id = :id
             ORDER BY ei.id
        ");
        $stmt->execute([':id' => $encargoId]);
        return $stmt->fetchAll();
    }

    private function generarCodigoUnico(): string
    {
        do {
            $codigo = 'ENC-' . str_pad((string) random_int(1, 99999), 5, '0', STR_PAD_LEFT);
        } while ($this->existsBy('codigo', $codigo));
        return $codigo;
    }

    /** Devuelve el id si existe en `usuarios`; null en caso contrario. */
    private function resolverUsuarioId(int $id): ?int
    {
        if ($id <= 0) return null;
        $stmt = $this->db->prepare("SELECT id FROM usuarios WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row !== false ? (int) $row['id'] : null;
    }
}
