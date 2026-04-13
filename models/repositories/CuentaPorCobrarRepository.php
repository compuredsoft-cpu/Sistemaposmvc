<?php
/**
 * Repositorio de Cuentas por Cobrar
 */

class CuentaPorCobrarRepository extends BaseRepository {
    protected string $table = 'cuentas_por_cobrar';
    
    protected function mapToEntity(array $data): CuentaPorCobrar {
        $cxc = new CuentaPorCobrar();
        $cxc->id = (int) $data['id'];
        $cxc->cliente_id = (int) $data['cliente_id'];
        $cxc->venta_id = $data['venta_id'] ?? null;
        $cxc->documento = $data['documento'] ?? null;
        $cxc->monto_total = (float) $data['monto_total'];
        $cxc->monto_pagado = (float) $data['monto_pagado'];
        $cxc->monto_pendiente = (float) $data['monto_pendiente'];
        $cxc->fecha_emision = $data['fecha_emision'];
        $cxc->fecha_vencimiento = $data['fecha_vencimiento'] ?? null;
        $cxc->plazo_dias = (int) $data['plazo_dias'];
        $cxc->estado = $data['estado'];
        $cxc->observaciones = $data['observaciones'] ?? null;
        $cxc->fecha_creacion = $data['fecha_creacion'] ?? null;
        return $cxc;
    }
    
    public function save(CuentaPorCobrar $cxc, ?int $usuarioId = null): bool {
        if ($cxc->id) {
            $sql = "UPDATE {$this->table} SET 
                    monto_pagado = ?, monto_pendiente = ?, estado = ?, observaciones = ? 
                    WHERE id = ?";
            $params = [$cxc->monto_pagado, $cxc->monto_pendiente, $cxc->estado, $cxc->observaciones, $cxc->id];
        } else {
            $sql = "INSERT INTO {$this->table} 
                    (cliente_id, venta_id, documento, monto_total, monto_pagado, monto_pendiente, 
                     fecha_emision, fecha_vencimiento, plazo_dias, estado, observaciones, usuario_creador) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $cxc->cliente_id, $cxc->venta_id, $cxc->documento, $cxc->monto_total,
                $cxc->monto_pagado, $cxc->monto_pendiente, $cxc->fecha_emision, $cxc->fecha_vencimiento,
                $cxc->plazo_dias, $cxc->estado, $cxc->observaciones, $usuarioId
            ];
        }
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        if (!$cxc->id && $result) {
            $cxc->id = (int) $this->db->lastInsertId();
        }
        
        return $result;
    }
    
    public function findByIdWithDetails(int $id): ?CuentaPorCobrar {
        $stmt = $this->db->prepare("SELECT cxc.*, c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                                           c.documento as cliente_documento, v.codigo as venta_codigo 
                                    FROM {$this->table} cxc 
                                    LEFT JOIN clientes c ON cxc.cliente_id = c.id 
                                    LEFT JOIN ventas v ON cxc.venta_id = v.id 
                                    WHERE cxc.id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        
        if (!$data) return null;
        
        $cxc = $this->mapToEntity($data);
        $cxc->cliente_nombre = $data['cliente_nombre'] . ' ' . ($data['cliente_apellido'] ?? '');
        $cxc->cliente_documento = $data['cliente_documento'];
        $cxc->venta_codigo = $data['venta_codigo'];
        
        // Obtener pagos
        $cxc->pagos = $this->getPagos($id);
        $cxc->calcularSaldo();
        
        return $cxc;
    }
    
    public function findAllWithFilters(array $filters = [], int $page = 1, int $perPage = ITEMS_PER_PAGE): array {
        $where = [];
        $params = [];
        
        if (!empty($filters['cliente_id'])) {
            $where[] = "cxc.cliente_id = ?";
            $params[] = $filters['cliente_id'];
        }
        if (!empty($filters['estado'])) {
            $where[] = "cxc.estado = ?";
            $params[] = $filters['estado'];
        }
        if (!empty($filters['vencidas'])) {
            $where[] = "cxc.estado IN ('PENDIENTE', 'PARCIAL') AND cxc.fecha_vencimiento < CURDATE()";
        }
        if (!empty($filters['busqueda'])) {
            $where[] = "(cxc.documento LIKE ? OR c.nombre LIKE ? OR c.documento LIKE ?)";
            $term = "%{$filters['busqueda']}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT cxc.*, c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                       c.documento as cliente_documento, v.codigo as venta_codigo 
                FROM {$this->table} cxc 
                LEFT JOIN clientes c ON cxc.cliente_id = c.id 
                LEFT JOIN ventas v ON cxc.venta_id = v.id 
                $whereClause 
                ORDER BY cxc.fecha_vencimiento ASC 
                LIMIT $perPage OFFSET $offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $cxc = $this->mapToEntity($row);
            $cxc->cliente_nombre = $row['cliente_nombre'] . ' ' . ($row['cliente_apellido'] ?? '');
            $cxc->cliente_documento = $row['cliente_documento'];
            $cxc->venta_codigo = $row['venta_codigo'];
            $results[] = $cxc;
        }
        
        // Contar
        $sqlCount = "SELECT COUNT(*) FROM {$this->table} cxc 
                     LEFT JOIN clientes c ON cxc.cliente_id = c.id 
                     $whereClause";
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
    
    public function registrarPago(int $cuentaId, float $monto, string $metodoPago, ?string $referencia = null, ?string $observaciones = null, ?int $usuarioId = null): bool {
        try {
            $this->db->beginTransaction();
            
            // Insertar el pago
            $stmt = $this->db->prepare("INSERT INTO pagos_cxc 
                                        (cuenta_cobrar_id, monto, metodo_pago, referencia, observaciones, usuario_creador) 
                                        VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$cuentaId, $monto, $metodoPago, $referencia, $observaciones, $usuarioId]);
            
            // Actualizar la cuenta
            $this->db->prepare("UPDATE {$this->table} SET 
                                monto_pagado = monto_pagado + ?, 
                                monto_pendiente = monto_pendiente - ? 
                                WHERE id = ?")
                      ->execute([$monto, $monto, $cuentaId]);
            
            // Verificar si está pagada
            $stmt = $this->db->prepare("SELECT monto_total, monto_pagado FROM {$this->table} WHERE id = ?");
            $stmt->execute([$cuentaId]);
            $row = $stmt->fetch();
            
            if ($row && (float)$row['monto_total'] <= (float)$row['monto_pagado']) {
                $this->db->prepare("UPDATE {$this->table} SET estado = 'PAGADA' WHERE id = ?")
                          ->execute([$cuentaId]);
            } else {
                $this->db->prepare("UPDATE {$this->table} SET estado = 'PARCIAL' WHERE id = ?")
                          ->execute([$cuentaId]);
            }
            
            // Actualizar saldo del cliente
            $stmt = $this->db->prepare("SELECT cliente_id FROM {$this->table} WHERE id = ?");
            $stmt->execute([$cuentaId]);
            $clienteId = $stmt->fetchColumn();
            
            $this->db->prepare("UPDATE clientes SET saldo_pendiente = saldo_pendiente - ? WHERE id = ?")
                      ->execute([$monto, $clienteId]);
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function getPagos(int $cuentaId): array {
        $stmt = $this->db->prepare("SELECT * FROM pagos_cxc WHERE cuenta_cobrar_id = ? ORDER BY fecha_pago DESC");
        $stmt->execute([$cuentaId]);
        
        $pagos = [];
        while ($row = $stmt->fetch()) {
            $pago = new PagoCxC();
            $pago->id = (int) $row['id'];
            $pago->cuenta_cobrar_id = (int) $row['cuenta_cobrar_id'];
            $pago->fecha_pago = $row['fecha_pago'];
            $pago->monto = (float) $row['monto'];
            $pago->metodo_pago = $row['metodo_pago'];
            $pago->referencia = $row['referencia'] ?? null;
            $pago->observaciones = $row['observaciones'] ?? null;
            $pagos[] = $pago;
        }
        return $pagos;
    }
    
    public function getEstadisticas(): array {
        $stmt = $this->db->query("SELECT 
            COUNT(*) as total_cuentas,
            SUM(monto_pendiente) as total_pendiente,
            SUM(CASE WHEN estado = 'VENCIDA' THEN monto_pendiente ELSE 0 END) as total_vencido,
            COUNT(CASE WHEN estado = 'VENCIDA' THEN 1 END) as cuentas_vencidas
            FROM {$this->table} WHERE estado IN ('PENDIENTE', 'PARCIAL', 'VENCIDA')");
        return $stmt->fetch();
    }
}
