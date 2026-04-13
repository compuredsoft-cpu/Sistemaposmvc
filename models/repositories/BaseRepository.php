<?php
/**
 * Repositorio Base - Clase abstracta para todos los repositorios
 */

abstract class BaseRepository {
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';
    
    public function __construct() {
        $this->db = Database::getConnection();
    }
    
    /**
     * Buscar por ID
     */
    public function findById(int $id): ?object {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        $data = $stmt->fetch();
        
        if (!$data) return null;
        
        return $this->mapToEntity($data);
    }
    
    /**
     * Obtener todos los registros
     */
    public function findAll(array $filters = [], string $orderBy = 'id DESC', int $page = 1, int $perPage = ITEMS_PER_PAGE): array {
        $where = [];
        $params = [];
        
        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '') {
                $where[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        $sql = "SELECT * FROM {$this->table}";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        $sql .= " ORDER BY $orderBy";
        
        // Paginación
        $offset = ($page - 1) * $perPage;
        $sql .= " LIMIT $perPage OFFSET $offset";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = $this->mapToEntity($row);
        }
        
        return $results;
    }
    
    /**
     * Contar registros
     */
    public function count(array $filters = []): int {
        $where = [];
        $params = [];
        
        foreach ($filters as $key => $value) {
            if ($value !== null && $value !== '') {
                $where[] = "$key = ?";
                $params[] = $value;
            }
        }
        
        $sql = "SELECT COUNT(*) FROM {$this->table}";
        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn();
    }
    
    /**
     * Buscar con paginación
     */
    public function paginate(int $page = 1, int $perPage = ITEMS_PER_PAGE, array $filters = [], string $orderBy = 'id DESC'): array {
        $items = $this->findAll($filters, $orderBy, $page, $perPage);
        $total = $this->count($filters);
        $totalPages = (int) ceil($total / $perPage);
        
        return [
            'items' => $items,
            'page' => $page,
            'per_page' => $perPage,
            'total' => $total,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ];
    }
    
    /**
     * Buscar por campo
     */
    public function findBy(string $field, $value): ?object {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE $field = ? LIMIT 1");
        $stmt->execute([$value]);
        $data = $stmt->fetch();
        
        if (!$data) return null;
        
        return $this->mapToEntity($data);
    }
    
    /**
     * Buscar múltiples por campo
     */
    public function findAllBy(string $field, $value): array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE $field = ?");
        $stmt->execute([$value]);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = $this->mapToEntity($row);
        }
        
        return $results;
    }
    
    /**
     * Eliminar por ID
     */
    public function delete(int $id): bool {
        $stmt = $this->db->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Cambiar estado (activar/desactivar)
     */
    public function toggleStatus(int $id, string $field = 'estado'): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET $field = IF($field = 1, 0, 1) WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }
    
    /**
     * Mapear array a entidad
     */
    abstract protected function mapToEntity(array $data): object;
}
