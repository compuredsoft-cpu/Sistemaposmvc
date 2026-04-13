<?php
/**
 * Repositorio de Categorías
 */

class CategoriaRepository extends BaseRepository {
    protected string $table = 'categorias';
    
    protected function mapToEntity(array $data): Categoria {
        $categoria = new Categoria();
        $categoria->id = (int) $data['id'];
        $categoria->codigo = $data['codigo'] ?? null;
        $categoria->nombre = $data['nombre'];
        $categoria->descripcion = $data['descripcion'] ?? null;
        $categoria->estado = (int) $data['estado'];
        $categoria->fecha_creacion = $data['fecha_creacion'] ?? null;
        $categoria->fecha_actualizacion = $data['fecha_actualizacion'] ?? null;
        return $categoria;
    }
    
    public function save(Categoria $categoria, ?int $usuarioId = null): bool {
        if ($categoria->id) {
            $sql = "UPDATE {$this->table} SET codigo = ?, nombre = ?, descripcion = ?, estado = ?, usuario_actualizador = ? WHERE id = ?";
            $params = [$categoria->codigo, $categoria->nombre, $categoria->descripcion, $categoria->estado, $usuarioId, $categoria->id];
        } else {
            $sql = "INSERT INTO {$this->table} (codigo, nombre, descripcion, estado, usuario_creador) VALUES (?, ?, ?, ?, ?)";
            $params = [$categoria->codigo, $categoria->nombre, $categoria->descripcion, $categoria->estado, $usuarioId];
        }
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        if (!$categoria->id && $result) {
            $categoria->id = (int) $this->db->lastInsertId();
        }
        
        return $result;
    }
    
    public function findAllActive(): array {
        return $this->findAll(['estado' => 1], 'nombre ASC');
    }
    
    public function search(string $term): array {
        $sql = "SELECT * FROM {$this->table} WHERE (nombre LIKE ? OR codigo LIKE ?) AND estado = 1 ORDER BY nombre";
        $stmt = $this->db->prepare($sql);
        $term = "%$term%";
        $stmt->execute([$term, $term]);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = $this->mapToEntity($row);
        }
        return $results;
    }
    
    public function isUsed(int $id): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM productos WHERE categoria_id = ?");
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }
    
    public function codeExists(string $codigo, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE codigo = ?";
        $params = [$codigo];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
    
    /**
     * Generar siguiente código disponible tipo CAT001
     */
    public function generateCode(): string {
        $stmt = $this->db->query("SELECT MAX(CAST(SUBSTRING(codigo, 4) AS UNSIGNED)) as max_num FROM {$this->table} WHERE codigo LIKE 'CAT%'");
        $result = $stmt->fetch();
        $nextNum = ($result['max_num'] ?? 0) + 1;
        return 'CAT' . str_pad((string)$nextNum, 3, '0', STR_PAD_LEFT);
    }
}
