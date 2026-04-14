<?php
/**
 * Repositorio de Ventas
 */

function debugLog($msg)
{
    $logFile = dirname(__DIR__, 2) . '/debug_venta.log';
    file_put_contents($logFile, date('Y-m-d H:i:s') . ' - ' . $msg . "\n", FILE_APPEND);
}

class VentaRepository extends BaseRepository
{
    protected string $table = 'ventas';

    protected function mapToEntity(array $data): Venta
    {
        $venta                      = new Venta();
        $venta->id                  = (int) $data['id'];
        $venta->codigo              = $data['codigo'];
        $venta->cliente_id          = (int) $data['cliente_id'];
        $venta->usuario_id          = (int) $data['usuario_id'];
        $venta->caja_id             = $data['caja_id'] ?? null;
        $venta->fecha               = $data['fecha'] ?? null;
        $venta->subtotal            = (float) $data['subtotal'];
        $venta->impuesto_porcentaje = (float) $data['impuesto_porcentaje'];
        $venta->impuesto            = (float) $data['impuesto'];
        $venta->descuento           = (float) $data['descuento'];
        $venta->total               = (float) $data['total'];
        $venta->metodo_pago         = $data['metodo_pago'];
        $venta->estado              = $data['estado'];
        $venta->observaciones       = $data['observaciones'] ?? null;
        $venta->es_credito          = (int) $data['es_credito'];
        $venta->cuotas              = (int) $data['cuotas'];
        $venta->valor_cuota         = (float) $data['valor_cuota'];
        $venta->saldo_pendiente     = (float) $data['saldo_pendiente'];
        $venta->fecha_vencimiento   = $data['fecha_vencimiento'] ?? null;
        return $venta;
    }

    public function save(Venta $venta, array $detalles = [], ?int $usuarioId = null): bool
    {
        debugLog("VentaRepository::save() - INICIO. Venta ID actual: " . ($venta->id ?? 'null'));
        try {
            $this->db->beginTransaction();
            debugLog("VentaRepository::save() - Transaction iniciada");

            if ($venta->id) {
                $sql = "UPDATE {$this->table} SET cliente_id = ?, caja_id = ?, subtotal = ?,
                        impuesto_porcentaje = ?, impuesto = ?, descuento = ?, total = ?,
                        metodo_pago = ?, estado = ?, observaciones = ?, es_credito = ?,
                        cuotas = ?, valor_cuota = ?, saldo_pendiente = ?, fecha_vencimiento = ?
                        WHERE id = ?";
                $params = [
                    $venta->cliente_id, $venta->caja_id, $venta->subtotal, $venta->impuesto_porcentaje,
                    $venta->impuesto, $venta->descuento, $venta->total, $venta->metodo_pago,
                    $venta->estado, $venta->observaciones, $venta->es_credito, $venta->cuotas,
                    $venta->valor_cuota, $venta->saldo_pendiente, $venta->fecha_vencimiento, $venta->id,
                ];
            } else {
                $sql = "INSERT INTO {$this->table}
                        (codigo, cliente_id, usuario_id, caja_id, subtotal, impuesto_porcentaje,
                         impuesto, descuento, total, metodo_pago, estado, observaciones,
                         es_credito, cuotas, valor_cuota, saldo_pendiente, fecha_vencimiento)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $params = [
                    $venta->codigo, $venta->cliente_id, $venta->usuario_id, $venta->caja_id,
                    $venta->subtotal, $venta->impuesto_porcentaje, $venta->impuesto, $venta->descuento,
                    $venta->total, $venta->metodo_pago, $venta->estado, $venta->observaciones,
                    $venta->es_credito, $venta->cuotas, $venta->valor_cuota, $venta->saldo_pendiente,
                    $venta->fecha_vencimiento,
                ];
            }

            $stmt = $this->db->prepare($sql);
            debugLog("VentaRepository::save() - SQL preparado, ejecutando...");
            $result = $stmt->execute($params);
            debugLog("VentaRepository::save() - Execute resultado: " . var_export($result, true));

            if (! $result) {
                throw new Exception("Error al ejecutar INSERT de venta: " . implode(', ', $stmt->errorInfo()));
            }

            if (! $venta->id) {
                $venta->id = (int) $this->db->lastInsertId();
                debugLog("VentaRepository::save() - lastInsertId: " . $venta->id);
                if (! $venta->id) {
                    throw new Exception("No se pudo obtener el ID de la venta insertada");
                }
            }

            // Guardar detalles
            debugLog("VentaRepository::save() - Procesando detalles...");
            if (! empty($detalles)) {
                debugLog("VentaRepository::save() - Cantidad de detalles: " . count($detalles));
                // Eliminar detalles existentes
                $this->db->prepare("DELETE FROM ventas_detalle WHERE venta_id = ?")->execute([$venta->id]);

                $detStmt = $this->db->prepare("INSERT INTO ventas_detalle
                                               (venta_id, producto_id, cantidad, precio_unitario, subtotal)
                                               VALUES (?, ?, ?, ?, ?)");

                foreach ($detalles as $detalle) {
                    $detStmt->execute([
                        $venta->id, $detalle['producto_id'], $detalle['cantidad'],
                        $detalle['precio_unitario'], $detalle['subtotal'],
                    ]);

                    // Descontar stock y registrar en kardex (KardexRepository maneja ambos)
                    if ($venta->estado === 'COMPLETADA') {
                        $kardexRepo = new KardexRepository();
                        $kardexRepo->registrarMovimiento(
                            $detalle['producto_id'],
                            'SALIDA',
                            'VENTA',
                            $venta->id,
                            $venta->codigo,
                            $detalle['cantidad'],
                            $detalle['precio_unitario'],
                            'Venta de producto',
                            $usuarioId
                        );
                    }
                }
            }

            // Si es crédito, crear cuenta por cobrar
            if ($venta->estado === 'COMPLETADA' && $venta->es_credito && $venta->saldo_pendiente > 0) {
                $cxcRepo                = new CuentaPorCobrarRepository();
                $cxc                    = new CuentaPorCobrar();
                $cxc->cliente_id        = $venta->cliente_id;
                $cxc->venta_id          = $venta->id;
                $cxc->documento         = $venta->codigo;
                $cxc->monto_total       = $venta->total;
                $cxc->monto_pendiente   = $venta->saldo_pendiente;
                $cxc->fecha_emision     = date('Y-m-d');
                $cxc->fecha_vencimiento = $venta->fecha_vencimiento;
                $cxc->plazo_dias        = $venta->cuotas * 30;
                $cxcRepo->save($cxc, $usuarioId);

                // Actualizar saldo del cliente
                $this->db->prepare("UPDATE clientes SET saldo_pendiente = saldo_pendiente + ? WHERE id = ?")
                    ->execute([$venta->saldo_pendiente, $venta->cliente_id]);
            }

            // Actualizar totales de caja
            if ($venta->caja_id && $venta->estado === 'COMPLETADA') {
                $this->actualizarCaja($venta);
            }

            $this->db->commit();
            debugLog("VentaRepository::save() - Commit exitoso. Venta ID: " . $venta->id);
            return true;

        } catch (Exception $e) {
            debugLog("VentaRepository::save() - EXCEPTION: " . $e->getMessage());
            debugLog("VentaRepository::save() - TRACE: " . $e->getTraceAsString());
            $this->db->rollBack();
            debugLog("VentaRepository::save() - Rollback ejecutado");
            return false;
        }
    }

    private function actualizarCaja(Venta $venta): void
    {
        $metodoPago = strtoupper($venta->metodo_pago);
        $columna    = match ($metodoPago) {
            'EFECTIVO' => 'total_efectivo',
            'TARJETA', 'TARJETA_DEBITO', 'TARJETA_CREDITO' => 'total_tarjeta',
            'TRANSFERENCIA', 'QR_BANCOLOMBIA', 'QR_NEQUI', 'QR_DAVIPLATA', 'QR_PSE', 'TRANSFERENCIA_NEQUI', 'TRANSFERENCIA_DAVIPLATA', 'WOMPI', 'PLACETOPAY' => 'total_transferencia',
            'CHEQUE'   => 'total_cheque',
            'CREDITO', 'STRIPE' => 'total_credito',
            default    => 'total_efectivo'
        };

        $sql = "UPDATE cajas SET total_ventas = total_ventas + ?, $columna = $columna + ? WHERE id = ?";
        $this->db->prepare($sql)->execute([$venta->total, $venta->total, $venta->caja_id]);
    }

    public function findByIdWithDetails(int $id): ?Venta
    {
        $stmt = $this->db->prepare("SELECT v.*, c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                                           c.documento as cliente_documento, u.nombre as usuario_nombre
                                    FROM {$this->table} v
                                    LEFT JOIN clientes c ON v.cliente_id = c.id
                                    LEFT JOIN usuarios u ON v.usuario_id = u.id
                                    WHERE v.id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();

        if (! $data) {
            return null;
        }

        $venta                    = $this->mapToEntity($data);
        $venta->cliente_nombre    = $data['cliente_nombre'] . ' ' . ($data['cliente_apellido'] ?? '');
        $venta->cliente_documento = $data['cliente_documento'];
        $venta->usuario_nombre    = $data['usuario_nombre'];

        // Obtener detalles
        $detStmt = $this->db->prepare("SELECT vd.*, p.codigo as producto_codigo, p.nombre as producto_nombre
                                        FROM ventas_detalle vd
                                        JOIN productos p ON vd.producto_id = p.id
                                        WHERE vd.venta_id = ?");
        $detStmt->execute([$id]);

        while ($det = $detStmt->fetch()) {
            $detalle                  = new VentaDetalle();
            $detalle->id              = (int) $det['id'];
            $detalle->venta_id        = (int) $det['venta_id'];
            $detalle->producto_id     = (int) $det['producto_id'];
            $detalle->cantidad        = (int) $det['cantidad'];
            $detalle->precio_unitario = (float) $det['precio_unitario'];
            $detalle->subtotal        = (float) $det['subtotal'];
            $detalle->producto_codigo = $det['producto_codigo'];
            $detalle->producto_nombre = $det['producto_nombre'];
            $venta->detalles[]        = $detalle;
        }

        return $venta;
    }

    public function findAllWithFilters(array $filters = [], int $page = 1, int $perPage = ITEMS_PER_PAGE): array
    {
        $where  = [];
        $params = [];

        if (! empty($filters['cliente_id'])) {
            $where[]  = "v.cliente_id = ?";
            $params[] = $filters['cliente_id'];
        }
        if (! empty($filters['estado'])) {
            $where[]  = "v.estado = ?";
            $params[] = $filters['estado'];
        }
        if (! empty($filters['metodo_pago'])) {
            $where[]  = "v.metodo_pago = ?";
            $params[] = $filters['metodo_pago'];
        }
        if (! empty($filters['caja_id'])) {
            $where[]  = "v.caja_id = ?";
            $params[] = $filters['caja_id'];
        }
        if (! empty($filters['fecha_desde'])) {
            $where[]  = "DATE(v.fecha) >= ?";
            $params[] = $filters['fecha_desde'];
        }
        if (! empty($filters['fecha_hasta'])) {
            $where[]  = "DATE(v.fecha) <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        if (! empty($filters['busqueda'])) {
            $where[]  = "(v.codigo LIKE ? OR c.nombre LIKE ? OR c.documento LIKE ?)";
            $term     = "%{$filters['busqueda']}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $whereClause = ! empty($where) ? "WHERE " . implode(' AND ', $where) : "";
        $offset      = ($page - 1) * $perPage;

        $sql = "SELECT v.*, c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                       u.nombre as usuario_nombre
                FROM {$this->table} v
                LEFT JOIN clientes c ON v.cliente_id = c.id
                LEFT JOIN usuarios u ON v.usuario_id = u.id
                $whereClause
                ORDER BY v.fecha DESC
                LIMIT $perPage OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $results = [];
        while ($row = $stmt->fetch()) {
            $venta                 = $this->mapToEntity($row);
            $venta->cliente_nombre = $row['cliente_nombre'] . ' ' . ($row['cliente_apellido'] ?? '');
            $venta->usuario_nombre = $row['usuario_nombre'];
            $results[]             = $venta;
        }

        // Contar
        $sqlCount = "SELECT COUNT(*) FROM {$this->table} v
                     LEFT JOIN clientes c ON v.cliente_id = c.id
                     $whereClause";
        $stmtCount = $this->db->prepare($sqlCount);
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();

        return [
            'items'       => $results,
            'page'        => $page,
            'per_page'    => $perPage,
            'total'       => $total,
            'total_pages' => (int) ceil($total / $perPage),
        ];
    }

    public function anular(int $id, ?int $usuarioId = null): bool
    {
        try {
            $this->db->beginTransaction();

            $venta = $this->findByIdWithDetails($id);
            if (! $venta || $venta->estado === 'CANCELADA') {
                $this->db->rollBack();
                return false;
            }

            // Revertir stock
            foreach ($venta->detalles as $detalle) {
                $this->db->prepare("UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?")
                    ->execute([$detalle->cantidad, $detalle->producto_id]);
            }

            // Si era crédito, revertir saldo
            if ($venta->es_credito && $venta->saldo_pendiente > 0) {
                $this->db->prepare("UPDATE clientes SET saldo_pendiente = saldo_pendiente - ? WHERE id = ?")
                    ->execute([$venta->saldo_pendiente, $venta->cliente_id]);
            }

            // Cambiar estado
            $stmt   = $this->db->prepare("UPDATE {$this->table} SET estado = 'CANCELADA' WHERE id = ?");
            $result = $stmt->execute([$id]);

            $this->db->commit();
            return $result;

        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    public function getLastCode(): ?string
    {
        $stmt = $this->db->query("SELECT codigo FROM {$this->table} ORDER BY id DESC LIMIT 1");
        return $stmt->fetchColumn() ?: null;
    }

    public function getEstadisticasHoy(): array
    {
        $hoy = date('Y-m-d');

        $stmt = $this->db->prepare("SELECT
                                    COUNT(*) as total_ventas,
                                    SUM(total) as total_vendido,
                                    SUM(CASE WHEN metodo_pago = 'EFECTIVO' THEN total ELSE 0 END) as efectivo,
                                    SUM(CASE WHEN metodo_pago = 'TARJETA' THEN total ELSE 0 END) as tarjeta,
                                    SUM(CASE WHEN metodo_pago = 'TRANSFERENCIA' THEN total ELSE 0 END) as transferencia
                                FROM {$this->table}
                                WHERE DATE(fecha) = ? AND estado = 'COMPLETADA'");
        $stmt->execute([$hoy]);
        return $stmt->fetch();
    }
}