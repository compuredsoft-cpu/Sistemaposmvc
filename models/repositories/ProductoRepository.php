<?php
/**
 * Repositorio de Productos
 */

class ProductoRepository extends BaseRepository {
    protected string $table = 'productos';
    
    protected function mapToEntity(array $data): Producto {
        $producto = new Producto();
        $producto->id = (int) $data['id'];
        $producto->codigo = $data['codigo'];
        $producto->codigo_barras = $data['codigo_barras'] ?? null;
        $producto->nombre = $data['nombre'];
        $producto->descripcion = $data['descripcion'] ?? null;
        $producto->categoria_id = $data['categoria_id'] ?? null;
        $producto->proveedor_id = $data['proveedor_id'] ?? null;
        $producto->unidad_medida = $data['unidad_medida'];
        $producto->precio_costo = (float) $data['precio_costo'];
        $producto->precio_venta = (float) $data['precio_venta'];
        $producto->precio_mayorista = (float) $data['precio_mayorista'];
        $producto->stock_minimo = (int) $data['stock_minimo'];
        $producto->stock_maximo = (int) $data['stock_maximo'];
        $producto->stock_actual = (int) $data['stock_actual'];
        $producto->ubicacion = $data['ubicacion'] ?? null;
        $producto->imagen = $data['imagen'] ?? null;
        $producto->estado = (int) $data['estado'];
        $producto->fecha_creacion = $data['fecha_creacion'] ?? null;
        $producto->fecha_actualizacion = $data['fecha_actualizacion'] ?? null;
        return $producto;
    }
    
    public function save(Producto $producto, ?int $usuarioId = null): bool {
        if ($producto->id) {
            $sql = "UPDATE {$this->table} SET 
                    codigo = ?, codigo_barras = ?, nombre = ?, descripcion = ?, categoria_id = ?, 
                    proveedor_id = ?, unidad_medida = ?, precio_costo = ?, precio_venta = ?, 
                    precio_mayorista = ?, stock_minimo = ?, stock_maximo = ?, ubicacion = ?, 
                    imagen = ?, estado = ?, usuario_actualizador = ? 
                    WHERE id = ?";
            $params = [
                $producto->codigo, $producto->codigo_barras, $producto->nombre, $producto->descripcion,
                $producto->categoria_id, $producto->proveedor_id, $producto->unidad_medida,
                $producto->precio_costo, $producto->precio_venta, $producto->precio_mayorista,
                $producto->stock_minimo, $producto->stock_maximo, $producto->ubicacion,
                $producto->imagen, $producto->estado, $usuarioId, $producto->id
            ];
        } else {
            $sql = "INSERT INTO {$this->table} 
                    (codigo, codigo_barras, nombre, descripcion, categoria_id, proveedor_id, unidad_medida, 
                     precio_costo, precio_venta, precio_mayorista, stock_minimo, stock_maximo, 
                     ubicacion, imagen, estado, usuario_creador) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $producto->codigo, $producto->codigo_barras, $producto->nombre, $producto->descripcion,
                $producto->categoria_id, $producto->proveedor_id, $producto->unidad_medida,
                $producto->precio_costo, $producto->precio_venta, $producto->precio_mayorista,
                $producto->stock_minimo, $producto->stock_maximo, $producto->ubicacion,
                $producto->imagen, $producto->estado, $usuarioId
            ];
        }
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        if (!$producto->id && $result) {
            $producto->id = (int) $this->db->lastInsertId();
        }
        
        return $result;
    }
    
    public function updateStock(int $id, int $cantidad, string $tipo = 'SUMAR'): bool {
        $operador = $tipo === 'SUMAR' ? '+' : '-';
        $stmt = $this->db->prepare("UPDATE {$this->table} SET stock_actual = stock_actual $operador ? WHERE id = ?");
        return $stmt->execute([$cantidad, $id]);
    }
    
    public function findAllActive(): array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE estado = 1 ORDER BY nombre ASC");
        $stmt->execute();
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = $this->mapToEntity($row);
        }
        return $results;
    }
    
    public function findAllWithCategories(array $filters = [], string $orderBy = 'p.id DESC', int $page = 1, int $perPage = ITEMS_PER_PAGE): array {
        $where = ["p.estado = 1"];
        $params = [];
        
        if (!empty($filters['categoria_id'])) {
            $where[] = "p.categoria_id = ?";
            $params[] = $filters['categoria_id'];
        }
        if (!empty($filters['proveedor_id'])) {
            $where[] = "p.proveedor_id = ?";
            $params[] = $filters['proveedor_id'];
        }
        if (!empty($filters['stock_alerta'])) {
            $where[] = "p.stock_actual <= p.stock_minimo";
        }
        if (!empty($filters['busqueda'])) {
            $where[] = "(p.nombre LIKE ? OR p.codigo LIKE ? OR p.codigo_barras LIKE ?)";
            $term = "%{$filters['busqueda']}%";
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }
        
        $whereClause = implode(' AND ', $where);
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT p.*, c.nombre as categoria_nombre, pr.nombre as proveedor_nombre 
                FROM {$this->table} p 
                LEFT JOIN categorias c ON p.categoria_id = c.id 
                LEFT JOIN proveedores pr ON p.proveedor_id = pr.id 
                WHERE $whereClause 
                ORDER BY $orderBy 
                LIMIT $perPage OFFSET $offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $producto = $this->mapToEntity($row);
            $producto->categoria_nombre = $row['categoria_nombre'];
            $producto->proveedor_nombre = $row['proveedor_nombre'];
            $results[] = $producto;
        }
        
        // Contar total
        $sqlCount = "SELECT COUNT(*) FROM {$this->table} p WHERE $whereClause";
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
    
    public function findByCode(string $codigo): ?Producto {
        $stmt = $this->db->prepare("SELECT p.*, c.nombre as categoria_nombre, pr.nombre as proveedor_nombre 
                                   FROM {$this->table} p 
                                   LEFT JOIN categorias c ON p.categoria_id = c.id 
                                   LEFT JOIN proveedores pr ON p.proveedor_id = pr.id 
                                   WHERE (p.codigo = ? OR p.codigo_barras = ?) AND p.estado = 1");
        $stmt->execute([$codigo, $codigo]);
        $data = $stmt->fetch();
        
        if (!$data) return null;
        
        $producto = $this->mapToEntity($data);
        $producto->categoria_nombre = $data['categoria_nombre'];
        $producto->proveedor_nombre = $data['proveedor_nombre'];
        return $producto;
    }
    
    public function search(string $term): array {
        $sql = "SELECT p.*, c.nombre as categoria_nombre FROM {$this->table} p 
                LEFT JOIN categorias c ON p.categoria_id = c.id 
                WHERE (p.nombre LIKE ? OR p.codigo LIKE ? OR p.codigo_barras LIKE ?) AND p.estado = 1 
                ORDER BY p.nombre LIMIT 20";
        $stmt = $this->db->prepare($sql);
        $term = "%$term%";
        $stmt->execute([$term, $term, $term]);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $producto = $this->mapToEntity($row);
            $producto->categoria_nombre = $row['categoria_nombre'];
            $results[] = $producto;
        }
        return $results;
    }
    
    public function getStockAlerts(): array {
        $sql = "SELECT p.*, c.nombre as categoria_nombre FROM {$this->table} p 
                LEFT JOIN categorias c ON p.categoria_id = c.id 
                WHERE p.stock_actual <= p.stock_minimo AND p.estado = 1 
                ORDER BY p.stock_actual ASC";
        $stmt = $this->db->query($sql);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $producto = $this->mapToEntity($row);
            $producto->categoria_nombre = $row['categoria_nombre'];
            $results[] = $producto;
        }
        return $results;
    }
    
    public function codeExists(string $codigo, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE (codigo = ? OR codigo_barras = ?)";
        $params = [$codigo, $codigo];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
    
    public function getValorInventario(): float {
        $stmt = $this->db->query("SELECT SUM(precio_costo * stock_actual) as total FROM {$this->table} WHERE estado = 1");
        return (float) ($stmt->fetchColumn() ?? 0);
    }
}
