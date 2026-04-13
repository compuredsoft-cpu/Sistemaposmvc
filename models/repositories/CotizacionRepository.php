<?php
/**
 * Repositorio de Cotizaciones
 */

class CotizacionRepository extends BaseRepository {
    protected string $table = 'cotizaciones';
    
    protected function mapToEntity(array $data): Cotizacion {
        $cotizacion = new Cotizacion();
        $cotizacion->id = (int) $data['id'];
        $cotizacion->codigo = $data['codigo'];
        $cotizacion->cliente_id = (int) $data['cliente_id'];
        $cotizacion->usuario_id = (int) $data['usuario_id'];
        $cotizacion->fecha = $data['fecha'] ?? null;
        $cotizacion->fecha_vencimiento = $data['fecha_vencimiento'] ?? null;
        $cotizacion->subtotal = (float) $data['subtotal'];
        $cotizacion->impuesto_porcentaje = (float) $data['impuesto_porcentaje'];
        $cotizacion->impuesto = (float) $data['impuesto'];
        $cotizacion->descuento = (float) $data['descuento'];
        $cotizacion->total = (float) $data['total'];
        $cotizacion->estado = $data['estado'];
        $cotizacion->observaciones = $data['observaciones'] ?? null;
        $cotizacion->condiciones = $data['condiciones'] ?? null;
        $cotizacion->tiempo_entrega = $data['tiempo_entrega'] ?? null;
        $cotizacion->forma_pago = $data['forma_pago'] ?? null;
        $cotizacion->venta_id = $data['venta_id'] ?? null;
        $cotizacion->fecha_creacion = $data['fecha_creacion'] ?? null;
        return $cotizacion;
    }
    
    public function save(Cotizacion $cotizacion, array $detalles = []): bool {
        try {
            $this->db->beginTransaction();
            
            if ($cotizacion->id) {
                $sql = "UPDATE {$this->table} SET 
                        cliente_id = ?, fecha_vencimiento = ?, subtotal = ?, impuesto_porcentaje = ?, 
                        impuesto = ?, descuento = ?, total = ?, estado = ?, observaciones = ?, 
                        condiciones = ?, tiempo_entrega = ?, forma_pago = ?, venta_id = ? 
                        WHERE id = ?";
                $params = [
                    $cotizacion->cliente_id, $cotizacion->fecha_vencimiento, $cotizacion->subtotal,
                    $cotizacion->impuesto_porcentaje, $cotizacion->impuesto, $cotizacion->descuento,
                    $cotizacion->total, $cotizacion->estado, $cotizacion->observaciones,
                    $cotizacion->condiciones, $cotizacion->tiempo_entrega, $cotizacion->forma_pago,
                    $cotizacion->venta_id, $cotizacion->id
                ];
            } else {
                $sql = "INSERT INTO {$this->table} 
                        (codigo, cliente_id, usuario_id, fecha_vencimiento, subtotal, impuesto_porcentaje, 
                         impuesto, descuento, total, estado, observaciones, condiciones, 
                         tiempo_entrega, forma_pago) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $params = [
                    $cotizacion->codigo, $cotizacion->cliente_id, $cotizacion->usuario_id,
                    $cotizacion->fecha_vencimiento, $cotizacion->subtotal, $cotizacion->impuesto_porcentaje,
                    $cotizacion->impuesto, $cotizacion->descuento, $cotizacion->total, $cotizacion->estado,
                    $cotizacion->observaciones, $cotizacion->condiciones, $cotizacion->tiempo_entrega,
                    $cotizacion->forma_pago
                ];
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            if (!$cotizacion->id) {
                $cotizacion->id = (int) $this->db->lastInsertId();
            }
            
            // Guardar detalles
            if (!empty($detalles)) {
                $this->db->prepare("DELETE FROM cotizaciones_detalle WHERE cotizacion_id = ?")
                          ->execute([$cotizacion->id]);
                
                $detStmt = $this->db->prepare("INSERT INTO cotizaciones_detalle 
                                               (cotizacion_id, producto_id, cantidad, precio_unitario, subtotal) 
                                               VALUES (?, ?, ?, ?, ?)");
                
                foreach ($detalles as $detalle) {
                    $detStmt->execute([
                        $cotizacion->id, $detalle['producto_id'], $detalle['cantidad'],
                        $detalle['precio_unitario'], $detalle['subtotal']
                    ]);
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    public function findByIdWithDetails(int $id): ?Cotizacion {
        $stmt = $this->db->prepare("SELECT c.*, cl.nombre as cliente_nombre, cl.apellido as cliente_apellido,
                                           cl.documento as cliente_documento, u.nombre as usuario_nombre,
                                           v.codigo as venta_codigo 
                                    FROM {$this->table} c 
                                    LEFT JOIN clientes cl ON c.cliente_id = cl.id 
                                    LEFT JOIN usuarios u ON c.usuario_id = u.id 
                                    LEFT JOIN ventas v ON c.venta_id = v.id 
                                    WHERE c.id = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        
        if (!$data) return null;
        
        $cotizacion = $this->mapToEntity($data);
        $cotizacion->cliente_nombre = $data['cliente_nombre'] . ' ' . ($data['cliente_apellido'] ?? '');
        $cotizacion->cliente_documento = $data['cliente_documento'];
        $cotizacion->usuario_nombre = $data['usuario_nombre'];
        $cotizacion->venta_codigo = $data['venta_codigo'];
        
        // Detalles
        $detStmt = $this->db->prepare("SELECT cd.*, p.codigo as producto_codigo, p.nombre as producto_nombre 
                                      FROM cotizaciones_detalle cd 
                                      JOIN productos p ON cd.producto_id = p.id 
                                      WHERE cd.cotizacion_id = ?");
        $detStmt->execute([$id]);
        
        while ($det = $detStmt->fetch()) {
            $detalle = new CotizacionDetalle();
            $detalle->id = (int) $det['id'];
            $detalle->cotizacion_id = (int) $det['cotizacion_id'];
            $detalle->producto_id = (int) $det['producto_id'];
            $detalle->cantidad = (int) $det['cantidad'];
            $detalle->precio_unitario = (float) $det['precio_unitario'];
            $detalle->subtotal = (float) $det['subtotal'];
            $detalle->producto_codigo = $det['producto_codigo'];
            $detalle->producto_nombre = $det['producto_nombre'];
            $cotizacion->detalles[] = $detalle;
        }
        
        return $cotizacion;
    }
    
    public function findAllWithFilters(array $filters = [], int $page = 1, int $perPage = ITEMS_PER_PAGE): array {
        $where = [];
        $params = [];
        
        if (!empty($filters['cliente_id'])) {
            $where[] = "c.cliente_id = ?";
            $params[] = $filters['cliente_id'];
        }
        if (!empty($filters['estado'])) {
            $where[] = "c.estado = ?";
            $params[] = $filters['estado'];
        }
        if (!empty($filters['fecha_desde'])) {
            $where[] = "DATE(c.fecha) >= ?";
            $params[] = $filters['fecha_desde'];
        }
        if (!empty($filters['fecha_hasta'])) {
            $where[] = "DATE(c.fecha) <= ?";
            $params[] = $filters['fecha_hasta'];
        }
        if (!empty($filters['busqueda'])) {
            $where[] = "(c.codigo LIKE ? OR cl.nombre LIKE ? OR cl.documento LIKE ?)";
            $term = "%{$filters['busqueda']}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT c.*, cl.nombre as cliente_nombre, cl.apellido as cliente_apellido, 
                       u.nombre as usuario_nombre 
                FROM {$this->table} c 
                LEFT JOIN clientes cl ON c.cliente_id = cl.id 
                LEFT JOIN usuarios u ON c.usuario_id = u.id 
                $whereClause 
                ORDER BY c.fecha DESC 
                LIMIT $perPage OFFSET $offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $cotizacion = $this->mapToEntity($row);
            $cotizacion->cliente_nombre = $row['cliente_nombre'] . ' ' . ($row['cliente_apellido'] ?? '');
            $cotizacion->usuario_nombre = $row['usuario_nombre'];
            $results[] = $cotizacion;
        }
        
        // Contar
        $sqlCount = "SELECT COUNT(*) FROM {$this->table} c 
                     LEFT JOIN clientes cl ON c.cliente_id = cl.id 
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
    
    public function convertirAVenta(int $cotizacionId, Venta $venta, array $detalles, ?int $usuarioId = null): ?int {
        try {
            $this->db->beginTransaction();
            
            // Crear la venta
            $ventaRepo = new VentaRepository();
            if (!$ventaRepo->save($venta, $detalles, $usuarioId)) {
                $this->db->rollBack();
                return null;
            }
            
            // Actualizar cotización
            $this->db->prepare("UPDATE {$this->table} SET estado = 'CONVERTIDA', venta_id = ? WHERE id = ?")
                      ->execute([$venta->id, $cotizacionId]);
            
            $this->db->commit();
            return $venta->id;
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return null;
        }
    }
    
    public function getLastCode(): ?string {
        $stmt = $this->db->query("SELECT codigo FROM {$this->table} ORDER BY id DESC LIMIT 1");
        return $stmt->fetchColumn() ?: null;
    }
}
