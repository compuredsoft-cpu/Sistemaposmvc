<?php
/**
 * Repositorio de Gastos
 */

class GastoRepository extends BaseRepository {
    protected string $table = 'gastos';
    
    protected function mapToEntity(array $data): Gasto {
        $gasto = new Gasto();
        $gasto->id = (int) $data['id'];
        $gasto->tipo_gasto_id = (int) $data['tipo_gasto_id'];
        $gasto->caja_id = $data['caja_id'] ?? null;
        $gasto->fecha = $data['fecha'] ?? null;
        $gasto->concepto = $data['concepto'];
        $gasto->monto = (float) $data['monto'];
        $gasto->metodo_pago = $data['metodo_pago'];
        $gasto->referencia = $data['referencia'] ?? null;
        $gasto->proveedor = $data['proveedor'] ?? null;
        $gasto->descripcion = $data['descripcion'] ?? null;
        $gasto->tipo = $data['tipo'];
        $gasto->estado = (int) $data['estado'];
        $gasto->usuario_creador = $data['usuario_creador'] ?? null;
        return $gasto;
    }
    
    public function save(Gasto $gasto, ?int $usuarioId = null): bool {
        if ($gasto->id) {
            $sql = "UPDATE {$this->table} SET 
                    tipo_gasto_id = ?, caja_id = ?, fecha = ?, concepto = ?, monto = ?, 
                    metodo_pago = ?, referencia = ?, proveedor = ?, descripcion = ?, tipo = ?, estado = ? 
                    WHERE id = ?";
            $params = [
                $gasto->tipo_gasto_id, $gasto->caja_id, $gasto->fecha, $gasto->concepto, $gasto->monto,
                $gasto->metodo_pago, $gasto->referencia, $gasto->proveedor, $gasto->descripcion,
                $gasto->tipo, $gasto->estado, $gasto->id
            ];
        } else {
            $sql = "INSERT INTO {$this->table} 
                    (tipo_gasto_id, caja_id, fecha, concepto, monto, metodo_pago, referencia, 
                     proveedor, descripcion, tipo, estado, usuario_creador) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $gasto->tipo_gasto_id, $gasto->caja_id, $gasto->fecha ?? date('Y-m-d H:i:s'), 
                $gasto->concepto, $gasto->monto, $gasto->metodo_pago, $gasto->referencia,
                $gasto->proveedor, $gasto->descripcion, $gasto->tipo, $gasto->estado, $usuarioId
            ];
        }
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        if (!$gasto->id && $result) {
            $gasto->id = (int) $this->db->lastInsertId();
        }
        
        return $result;
    }
    
    public function findAllWithFilters(array $filters = [], int $page = 1, int $perPage = ITEMS_PER_PAGE): array {
        $where = [];
        $params = [];
        
        if (!empty($filters['tipo_gasto_id'])) {
            $where[] = "g.tipo_gasto_id = ?";
            $params[] = $filters['tipo_gasto_id'];
        }
        if (!empty($filters['tipo'])) {
            $where[] = "g.tipo = ?";
            $params[] = $filters['tipo'];
        }
        if (!empty($filters['fecha_desde'])) {
            $where[] = "DATE(g.fecha) >= ?";
            $params[] = $filters['fecha_desde'];
        }
        if (!empty($filters['fecha_hasta'])) {
            $where[] = "DATE(g.fecha) <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        if (!empty($filters['caja_id'])) {
            $where[] = "g.caja_id = ?";
            $params[] = $filters['caja_id'];
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT g.*, tg.nombre as tipo_gasto_nombre, tg.tipo as tipo_gasto_categoria,
                       u.nombre as usuario_nombre 
                FROM {$this->table} g 
                JOIN tipos_gasto tg ON g.tipo_gasto_id = tg.id 
                LEFT JOIN usuarios u ON g.usuario_creador = u.id 
                $whereClause 
                ORDER BY g.fecha DESC 
                LIMIT $perPage OFFSET $offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $gasto = $this->mapToEntity($row);
            $gasto->tipo_gasto_nombre = $row['tipo_gasto_nombre'];
            $gasto->tipo_gasto_categoria = $row['tipo_gasto_categoria'];
            $gasto->usuario_nombre = $row['usuario_nombre'];
            $results[] = $gasto;
        }
        
        // Contar
        $sqlCount = "SELECT COUNT(*) FROM {$this->table} g $whereClause";
        $stmtCount = $this->db->prepare($sqlCount);
        $stmtCount->execute($params);
        $total = (int) $stmtCount->fetchColumn();
        
        return [
            'items' => $results,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => (int) ceil($total / $perPage)
        ];
    }
    
    public function getTotalesPorTipo(string $tipo, string $fechaDesde, string $fechaHasta): float {
        $stmt = $this->db->prepare("SELECT COALESCE(SUM(monto), 0) FROM {$this->table} 
                                    WHERE tipo = ? AND DATE(fecha) BETWEEN ? AND ? AND estado = 1");
        $stmt->execute([$tipo, $fechaDesde, $fechaHasta]);
        return (float) $stmt->fetchColumn();
    }
    
    public function getBalance(string $fechaDesde, string $fechaHasta): array {
        $gastos = $this->getTotalesPorTipo('GASTO', $fechaDesde, $fechaHasta);
        $ingresos = $this->getTotalesPorTipo('INGRESO', $fechaDesde, $fechaHasta);
        
        return [
            'total_gastos' => $gastos,
            'total_ingresos' => $ingresos,
            'balance' => $ingresos - $gastos
        ];
    }
    
    public function getGastosPorCategoria(string $fechaDesde, string $fechaHasta): array {
        $stmt = $this->db->prepare("SELECT tg.nombre, SUM(g.monto) as total 
                                    FROM {$this->table} g 
                                    JOIN tipos_gasto tg ON g.tipo_gasto_id = tg.id 
                                    WHERE g.tipo = 'GASTO' AND g.estado = 1 
                                    AND DATE(g.fecha) BETWEEN ? AND ? 
                                    GROUP BY tg.id, tg.nombre 
                                    ORDER BY total DESC");
        $stmt->execute([$fechaDesde, $fechaHasta]);
        return $stmt->fetchAll();
    }
}
