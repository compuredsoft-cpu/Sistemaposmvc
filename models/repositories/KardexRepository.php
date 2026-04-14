<?php
/**
 * Repositorio de Kardex (Movimientos de Inventario)
 */

class KardexRepository extends BaseRepository
{
    protected string $table = 'kardex';

    protected function mapToEntity(array $data): Kardex
    {
        $kardex                   = new Kardex();
        $kardex->id               = (int) $data['id'];
        $kardex->producto_id      = (int) $data['producto_id'];
        $kardex->tipo_movimiento  = $data['tipo_movimiento'];
        $kardex->documento_tipo   = $data['documento_tipo'];
        $kardex->documento_id     = $data['documento_id'] ?? null;
        $kardex->documento_codigo = $data['documento_codigo'] ?? null;
        $kardex->cantidad         = (int) $data['cantidad'];
        $kardex->stock_anterior   = (int) $data['stock_anterior'];
        $kardex->stock_nuevo      = (int) $data['stock_nuevo'];
        $kardex->costo_unitario   = $data['costo_unitario'] ? (float) $data['costo_unitario'] : null;
        $kardex->costo_total      = $data['costo_total'] ? (float) $data['costo_total'] : null;
        $kardex->observaciones    = $data['observaciones'] ?? null;
        $kardex->fecha_movimiento = $data['fecha_movimiento'] ?? null;
        $kardex->usuario_creador  = $data['usuario_creador'] ?? null;
        return $kardex;
    }

    public function registrarMovimiento(int $productoId, string $tipoMovimiento, string $documentoTipo, ?int $documentoId, ?string $documentoCodigo, int $cantidad, ?float $costoUnitario = null, ?string $observaciones = null, ?int $usuarioId = null): bool
    {
        // Obtener stock actual del producto
        $stmt = $this->db->prepare("SELECT stock_actual FROM productos WHERE id = ?");
        $stmt->execute([$productoId]);
        $stockAnterior = (int) $stmt->fetchColumn();

        // Calcular nuevo stock
        $stockNuevo = match ($tipoMovimiento) {
            'ENTRADA', 'DEVOLUCION' => $stockAnterior + $cantidad,
            'SALIDA', 'AJUSTE'      => $stockAnterior - $cantidad,
            default => $stockAnterior
        };

        $costoTotal = $costoUnitario ? $costoUnitario * $cantidad : null;

        try {
            // Insertar movimiento en kardex (sin transacción - VentaRepository la maneja)
            $sql = "INSERT INTO {$this->table}
                    (producto_id, tipo_movimiento, documento_tipo, documento_id, documento_codigo,
                     cantidad, stock_anterior, stock_nuevo, costo_unitario, costo_total, observaciones, usuario_creador)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                $productoId, $tipoMovimiento, $documentoTipo, $documentoId, $documentoCodigo,
                $cantidad, $stockAnterior, $stockNuevo, $costoUnitario, $costoTotal, $observaciones, $usuarioId,
            ]);

            // Actualizar stock del producto
            $stmtUpdate = $this->db->prepare("UPDATE productos SET stock_actual = ? WHERE id = ?");
            $stmtUpdate->execute([$stockNuevo, $productoId]);

            return true;

        } catch (Exception $e) {
            return false;
        }
    }

    public function save(Kardex $kardex): bool
    {
        // Este método no se usa directamente, se usa registrarMovimiento
        return false;
    }

    public function findAllWithFilters(array $filters = [], int $page = 1, int $perPage = ITEMS_PER_PAGE): array
    {
        $where  = [];
        $params = [];

        if (! empty($filters['producto_id'])) {
            $where[]  = "k.producto_id = ?";
            $params[] = $filters['producto_id'];
        }
        if (! empty($filters['tipo_movimiento'])) {
            $where[]  = "k.tipo_movimiento = ?";
            $params[] = $filters['tipo_movimiento'];
        }
        if (! empty($filters['documento_tipo'])) {
            $where[]  = "k.documento_tipo = ?";
            $params[] = $filters['documento_tipo'];
        }
        if (! empty($filters['fecha_desde'])) {
            $where[]  = "DATE(k.fecha_movimiento) >= ?";
            $params[] = $filters['fecha_desde'];
        }
        if (! empty($filters['fecha_hasta'])) {
            $where[]  = "DATE(k.fecha_movimiento) <= ?";
            $params[] = $filters['fecha_hasta'];
        }

        $whereClause = ! empty($where) ? "WHERE " . implode(' AND ', $where) : "";
        $offset      = ($page - 1) * $perPage;

        $sql = "SELECT k.*, p.codigo as producto_codigo, p.nombre as producto_nombre,
                       u.nombre as usuario_nombre
                FROM {$this->table} k
                JOIN productos p ON k.producto_id = p.id
                LEFT JOIN usuarios u ON k.usuario_creador = u.id
                $whereClause
                ORDER BY k.fecha_movimiento DESC
                LIMIT $perPage OFFSET $offset";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        $results = [];
        while ($row = $stmt->fetch()) {
            $kardex                  = $this->mapToEntity($row);
            $kardex->producto_codigo = $row['producto_codigo'];
            $kardex->producto_nombre = $row['producto_nombre'];
            $kardex->usuario_nombre  = $row['usuario_nombre'];
            $results[]               = $kardex;
        }

        // Contar
        $sqlCount  = "SELECT COUNT(*) FROM {$this->table} k $whereClause";
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

    public function getHistorialProducto(int $productoId, int $limit = 50): array
    {
        $sql = "SELECT k.*, u.nombre as usuario_nombre
                FROM {$this->table} k
                LEFT JOIN usuarios u ON k.usuario_creador = u.id
                WHERE k.producto_id = ?
                ORDER BY k.fecha_movimiento DESC
                LIMIT $limit";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$productoId]);

        $results = [];
        while ($row = $stmt->fetch()) {
            $kardex                 = $this->mapToEntity($row);
            $kardex->usuario_nombre = $row['usuario_nombre'];
            $results[]              = $kardex;
        }
        return $results;
    }
}