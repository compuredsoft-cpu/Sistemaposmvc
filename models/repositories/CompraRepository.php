<?php
/**
 * Repositorio de Compras
 */

class CompraRepository extends BaseRepository {
    protected string $table = 'compras';
    
    protected function mapToEntity(array $data): Compra {
        $compra = new Compra();
        $compra->id = (int) $data['id'];
        $compra->codigo = $data['codigo'];
        $compra->proveedor_id = (int) $data['proveedor_id'];
        $compra->fecha = $data['fecha'];
        $compra->fecha_registro = $data['fecha_registro'] ?? null;
        $compra->subtotal = (float) $data['subtotal'];
        $compra->impuesto = (float) $data['impuesto'];
        $compra->total = (float) $data['total'];
        $compra->metodo_pago = $data['metodo_pago'];
        $compra->estado = $data['estado'];
        $compra->observaciones = $data['observaciones'] ?? null;
        $compra->usuario_creador = $data['usuario_creador'] ?? null;
        return $compra;
    }
    
    public function save(Compra $compra, array $detalles = []): bool {
        try {
            $this->db->beginTransaction();
            
            if ($compra->id) {
                $sql = "UPDATE {$this->table} SET proveedor_id = ?, fecha = ?, subtotal = ?, 
                        impuesto = ?, total = ?, metodo_pago = ?, estado = ?, observaciones = ? 
                        WHERE id = ?";
                $params = [
                    $compra->proveedor_id, $compra->fecha, $compra->subtotal, $compra->impuesto,
                    $compra->total, $compra->metodo_pago, $compra->estado, $compra->observaciones, $compra->id
                ];
            } else {
                $sql = "INSERT INTO {$this->table} 
                        (codigo, proveedor_id, fecha, subtotal, impuesto, total, metodo_pago, 
                         estado, observaciones, usuario_creador) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $params = [
                    $compra->codigo, $compra->proveedor_id, $compra->fecha, $compra->subtotal,
                    $compra->impuesto, $compra->total, $compra->metodo_pago, $compra->estado,
                    $compra->observaciones, $compra->usuario_creador
                ];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            if (!$compra->id) {
                $compra->id = (int) $this->db->lastInsertId();
            }
            
            // Guardar detalles
            if (!empty($detalles)) {
                // Eliminar detalles existentes si es actualización
                if ($compra->id) {
                    $this->db->prepare("DELETE FROM compras_detalle WHERE compra_id = ?")->execute([$compra->id]);
                }
                
                // Insertar nuevos detalles
                $detStmt = $this->db->prepare("INSERT INTO compras_detalle 
                                               (compra_id, producto_id, cantidad, precio_unitario, subtotal) 
                                               VALUES (?, ?, ?, ?, ?)");
                
                foreach ($detalles as $detalle) {
                    $detStmt->execute([
                        $compra->id, $detalle['producto_id'], $detalle['cantidad'],
                        $detalle['precio_unitario'], $detalle['subtotal']
                    ]);
                    
                    // Actualizar stock si la compra está completada
                    if ($compra->estado === 'COMPLETADA') {
                        $this->db->prepare("UPDATE productos SET stock_actual = stock_actual + ? WHERE id = ?")
                                  ->execute([$detalle['cantidad'], $detalle['producto_id']]);
                        
                        // Registrar en kardex
                        $kardexRepo = new KardexRepository();
                        $kardexRepo->registrarMovimiento(
                            $detalle['producto_id'],
                            'ENTRADA',
                            'COMPRA',
                            $compra->id,
                            $compra->codigo,
                            $detalle['cantidad'],
                            $detalle['precio_unitario'],
                            $compra->observaciones ?? 'Compra de mercancía',
                            $compra->usuario_creador
                        );
                    }
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en CompraRepository::save: " . $e->getMessage());
            return false;
        }
    }
    
    public function findByIdWithDetails(int $id): ?Compra {
        $stmt = $this->db->prepare("SELECT c.*, p.nombre as proveedor_nombre, u.nombre as usuario_nombre 
                                   FROM {$this->table} c 
                                   LEFT JOIN proveedores p ON c.proveedor_id = p.id 
                                   LEFT JOIN usuarios u ON c.usuario_creador = u.id 
                                   WHERE c.id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        
        if (!$data) return null;
        
        $compra = $this->mapToEntity($data);
        $compra->proveedor_nombre = $data['proveedor_nombre'];
        $compra->usuario_nombre = $data['usuario_nombre'];
        
        // Obtener detalles
        $detStmt = $this->db->prepare("SELECT cd.*, pr.codigo as producto_codigo, pr.nombre as producto_nombre 
                                      FROM compras_detalle cd 
                                      JOIN productos pr ON cd.producto_id = pr.id 
                                      WHERE cd.compra_id = ?");
        $detStmt->execute([$id]);
        
        while ($det = $detStmt->fetch()) {
            $detalle = new CompraDetalle();
            $detalle->id = (int) $det['id'];
            $detalle->compra_id = (int) $det['compra_id'];
            $detalle->producto_id = (int) $det['producto_id'];
            $detalle->cantidad = (int) $det['cantidad'];
            $detalle->precio_unitario = (float) $det['precio_unitario'];
            $detalle->subtotal = (float) $det['subtotal'];
            $detalle->producto_codigo = $det['producto_codigo'];
            $detalle->producto_nombre = $det['producto_nombre'];
            $compra->detalles[] = $detalle;
        }
        
        return $compra;
    }
    
    public function findAllWithFilters(array $filters = [], int $page = 1, int $perPage = ITEMS_PER_PAGE): array {
        $where = [];
        $params = [];
        
        if (!empty($filters['proveedor_id'])) {
            $where[] = "c.proveedor_id = ?";
            $params[] = $filters['proveedor_id'];
        }
        if (!empty($filters['estado'])) {
            $where[] = "c.estado = ?";
            $params[] = $filters['estado'];
        }
        if (!empty($filters['fecha_desde'])) {
            $where[] = "c.fecha >= ?";
            $params[] = $filters['fecha_desde'];
        }
        if (!empty($filters['fecha_hasta'])) {
            $where[] = "c.fecha <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        if (!empty($filters['busqueda'])) {
            $where[] = "(c.codigo LIKE ? OR p.nombre LIKE ?)";
            $term = "%{$filters['busqueda']}%";
            $params[] = $term;
            $params[] = $term;
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT c.*, p.nombre as proveedor_nombre, u.nombre as usuario_nombre 
                FROM {$this->table} c 
                LEFT JOIN proveedores p ON c.proveedor_id = p.id 
                LEFT JOIN usuarios u ON c.usuario_creador = u.id 
                $whereClause 
                ORDER BY c.fecha DESC, c.id DESC 
                LIMIT $perPage OFFSET $offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $compra = $this->mapToEntity($row);
            $compra->proveedor_nombre = $row['proveedor_nombre'];
            $compra->usuario_nombre = $row['usuario_nombre'];
            $results[] = $compra;
        }
        
        // Contar total
        $sqlCount = "SELECT COUNT(*) FROM {$this->table} c 
                     LEFT JOIN proveedores p ON c.proveedor_id = p.id 
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
    
    public function anular(int $id): bool {
        try {
            $this->db->beginTransaction();
            
            // Obtener la compra y sus detalles
            $compra = $this->findByIdWithDetails($id);
            if (!$compra || $compra->estado === 'CANCELADA') {
                $this->db->rollBack();
                return false;
            }
            
            // Revertir stock
            foreach ($compra->detalles as $detalle) {
                $this->db->prepare("UPDATE productos SET stock_actual = stock_actual - ? WHERE id = ?")
                          ->execute([$detalle->cantidad, $detalle->producto_id]);
            }
            
            // Cambiar estado de la compra
            $stmt = $this->db->prepare("UPDATE {$this->table} SET estado = 'CANCELADA' WHERE id = ?");
            $result = $stmt->execute([$id]);
            
            $this->db->commit();
            return $result;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function getLastCode(): ?string {
        $stmt = $this->db->query("SELECT codigo FROM {$this->table} ORDER BY id DESC LIMIT 1");
        return $stmt->fetchColumn() ?: null;
    }
}
