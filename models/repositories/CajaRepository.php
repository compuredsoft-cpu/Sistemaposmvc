<?php
/**
 * Repositorio de Cajas (Apertura y Cierre)
 */

class CajaRepository extends BaseRepository {
    protected string $table = 'cajas';
    
    protected function mapToEntity(array $data): Caja {
        $caja = new Caja();
        $caja->id = (int) $data['id'];
        $caja->usuario_id = (int) $data['usuario_id'];
        $caja->fecha_apertura = $data['fecha_apertura'] ?? null;
        $caja->fecha_cierre = $data['fecha_cierre'] ?? null;
        $caja->monto_apertura = (float) $data['monto_apertura'];
        $caja->total_ventas = (float) $data['total_ventas'];
        $caja->total_compras = (float) $data['total_compras'];
        $caja->total_ingresos = (float) $data['total_ingresos'];
        $caja->total_egresos = (float) $data['total_egresos'];
        $caja->total_efectivo = (float) $data['total_efectivo'];
        $caja->total_tarjeta = (float) $data['total_tarjeta'];
        $caja->total_transferencia = (float) $data['total_transferencia'];
        $caja->total_cheque = (float) $data['total_cheque'];
        $caja->total_credito = (float) $data['total_credito'];
        $caja->monto_cierre = (float) $data['monto_cierre'];
        $caja->diferencia = (float) $data['diferencia'];
        $caja->observaciones_apertura = $data['observaciones_apertura'] ?? null;
        $caja->observaciones_cierre = $data['observaciones_cierre'] ?? null;
        $caja->estado = $data['estado'];
        return $caja;
    }
    
    public function save(Caja $caja): bool {
        if ($caja->id) {
            $sql = "UPDATE {$this->table} SET 
                    usuario_id = ?, fecha_cierre = ?, monto_apertura = ?, total_ventas = ?, 
                    total_compras = ?, total_ingresos = ?, total_egresos = ?, total_efectivo = ?, 
                    total_tarjeta = ?, total_transferencia = ?, total_cheque = ?, total_credito = ?, 
                    monto_cierre = ?, diferencia = ?, observaciones_apertura = ?, observaciones_cierre = ?, 
                    estado = ? 
                    WHERE id = ?";
            $params = [
                $caja->usuario_id, $caja->fecha_cierre, $caja->monto_apertura, $caja->total_ventas,
                $caja->total_compras, $caja->total_ingresos, $caja->total_egresos, $caja->total_efectivo,
                $caja->total_tarjeta, $caja->total_transferencia, $caja->total_cheque, $caja->total_credito,
                $caja->monto_cierre, $caja->diferencia, $caja->observaciones_apertura, $caja->observaciones_cierre,
                $caja->estado, $caja->id
            ];
        } else {
            $sql = "INSERT INTO {$this->table} 
                    (usuario_id, monto_apertura, observaciones_apertura) 
                    VALUES (?, ?, ?)";
            $params = [$caja->usuario_id, $caja->monto_apertura, $caja->observaciones_apertura];
        }
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        if (!$caja->id && $result) {
            $caja->id = (int) $this->db->lastInsertId();
        }
        
        return $result;
    }
    
    public function getCajaAbierta(int $usuarioId): ?Caja {
        $stmt = $this->db->prepare("SELECT c.*, u.nombre as usuario_nombre FROM {$this->table} c 
                                    JOIN usuarios u ON c.usuario_id = u.id 
                                    WHERE c.usuario_id = ? AND c.estado = 'ABIERTA' 
                                    ORDER BY c.id DESC LIMIT 1");
        $stmt->execute([$usuarioId]);
        $data = $stmt->fetch();
        
        if (!$data) return null;
        
        $caja = $this->mapToEntity($data);
        $caja->usuario_nombre = $data['usuario_nombre'];
        return $caja;
    }
    
    public function cerrarCaja(Caja $caja): bool {
        try {
            $this->db->beginTransaction();
            
            $caja->calcularDiferencia();
            $caja->estado = 'CERRADA';
            $caja->fecha_cierre = date('Y-m-d H:i:s');
            
            $sql = "UPDATE {$this->table} SET 
                    fecha_cierre = NOW(), monto_cierre = ?, diferencia = ?, 
                    observaciones_cierre = ?, estado = 'CERRADA' 
                    WHERE id = ?";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                $caja->monto_cierre, $caja->diferencia, $caja->observaciones_cierre, $caja->id
            ]);
            
            $this->db->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function findAllWithUser(array $filters = [], int $page = 1, int $perPage = ITEMS_PER_PAGE): array {
        $where = [];
        $params = [];
        
        if (!empty($filters['usuario_id'])) {
            $where[] = "c.usuario_id = ?";
            $params[] = $filters['usuario_id'];
        }
        if (!empty($filters['estado'])) {
            $where[] = "c.estado = ?";
            $params[] = $filters['estado'];
        }
        if (!empty($filters['fecha_desde'])) {
            $where[] = "DATE(c.fecha_apertura) >= ?";
            $params[] = $filters['fecha_desde'];
        }
        if (!empty($filters['fecha_hasta'])) {
            $where[] = "DATE(c.fecha_apertura) <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT c.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido 
                FROM {$this->table} c 
                JOIN usuarios u ON c.usuario_id = u.id 
                $whereClause 
                ORDER BY c.fecha_apertura DESC 
                LIMIT $perPage OFFSET $offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $caja = $this->mapToEntity($row);
            $caja->usuario_nombre = $row['usuario_nombre'] . ' ' . ($row['usuario_apellido'] ?? '');
            $results[] = $caja;
        }
        
        // Contar
        $sqlCount = "SELECT COUNT(*) FROM {$this->table} c $whereClause";
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
    
    public function hayCajaAbierta(int $usuarioId): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM {$this->table} WHERE usuario_id = ? AND estado = 'ABIERTA'");
        $stmt->execute([$usuarioId]);
        return (int) $stmt->fetchColumn() > 0;
    }
    
    public function getResumenCaja(int $cajaId): array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE id = ?");
        $stmt->execute([$cajaId]);
        $caja = $stmt->fetch();
        
        if (!$caja) return [];
        
        // Obtener ventas por método de pago
        $ventasStmt = $this->db->prepare("SELECT 
            metodo_pago, COUNT(*) as cantidad, SUM(total) as total 
            FROM ventas 
            WHERE caja_id = ? AND estado = 'COMPLETADA' 
            GROUP BY metodo_pago");
        $ventasStmt->execute([$cajaId]);
        $ventasPorMetodo = $ventasStmt->fetchAll();
        
        return [
            'caja' => $caja,
            'ventas_por_metodo' => $ventasPorMetodo
        ];
    }
}
