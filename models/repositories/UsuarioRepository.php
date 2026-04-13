<?php
/**
 * Repositorio de Usuarios
 */

class UsuarioRepository extends BaseRepository {
    protected string $table = 'usuarios';
    
    protected function mapToEntity(array $data): Usuario {
        $usuario = new Usuario();
        $usuario->id = (int) $data['id'];
        $usuario->rol_id = (int) $data['rol_id'];
        $usuario->nombre = $data['nombre'];
        $usuario->apellido = $data['apellido'];
        $usuario->email = $data['email'];
        $usuario->telefono = $data['telefono'] ?? null;
        $usuario->direccion = $data['direccion'] ?? null;
        $usuario->username = $data['username'];
        $usuario->password = $data['password'];
        $usuario->avatar = $data['avatar'] ?? null;
        $usuario->ultimo_acceso = $data['ultimo_acceso'] ?? null;
        $usuario->estado = (int) $data['estado'];
        $usuario->fecha_creacion = $data['fecha_creacion'] ?? null;
        $usuario->fecha_actualizacion = $data['fecha_actualizacion'] ?? null;
        return $usuario;
    }
    
    public function save(Usuario $usuario): bool {
        if ($usuario->id) {
            // Actualizar
            $sql = "UPDATE {$this->table} SET 
                    rol_id = ?, nombre = ?, apellido = ?, email = ?, telefono = ?, 
                    direccion = ?, username = ?, avatar = ?, estado = ?
                    WHERE id = ?";
            $params = [
                $usuario->rol_id, $usuario->nombre, $usuario->apellido, $usuario->email,
                $usuario->telefono, $usuario->direccion, $usuario->username, 
                $usuario->avatar, $usuario->estado, $usuario->id
            ];
            if (!empty($usuario->password)) {
                $sql = "UPDATE {$this->table} SET 
                        rol_id = ?, nombre = ?, apellido = ?, email = ?, telefono = ?, 
                        direccion = ?, username = ?, password = ?, avatar = ?, estado = ?
                        WHERE id = ?";
                $params = [
                    $usuario->rol_id, $usuario->nombre, $usuario->apellido, $usuario->email,
                    $usuario->telefono, $usuario->direccion, $usuario->username,
                    password_hash($usuario->password, PASSWORD_DEFAULT), $usuario->avatar, 
                    $usuario->estado, $usuario->id
                ];
            }
        } else {
            // Insertar
            $sql = "INSERT INTO {$this->table} 
                    (rol_id, nombre, apellido, email, telefono, direccion, username, password, avatar, estado) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $usuario->rol_id, $usuario->nombre, $usuario->apellido, $usuario->email,
                $usuario->telefono, $usuario->direccion, $usuario->username,
                password_hash($usuario->password, PASSWORD_DEFAULT), $usuario->avatar, $usuario->estado
            ];
        }
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        if (!$usuario->id && $result) {
            $usuario->id = (int) $this->db->lastInsertId();
        }
        
        return $result;
    }
    
    public function authenticate(string $username, string $password): ?array {
        $stmt = $this->db->prepare("SELECT u.*, r.nombre as rol_nombre, r.permisos 
                                    FROM {$this->table} u 
                                    JOIN roles r ON u.rol_id = r.id 
                                    WHERE (u.username = ? OR u.email = ?) AND u.estado = 1");
        $stmt->execute([$username, $username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Actualizar último acceso
            $this->db->prepare("UPDATE {$this->table} SET ultimo_acceso = NOW() WHERE id = ?")
                     ->execute([$user['id']]);
            return $user;
        }
        
        return null;
    }
    
    public function findByUsername(string $username): ?Usuario {
        return $this->findBy('username', $username);
    }
    
    public function findByEmail(string $email): ?Usuario {
        return $this->findBy('email', $email);
    }
    
    public function search(string $term): array {
        $sql = "SELECT u.*, r.nombre as rol_nombre FROM {$this->table} u 
                JOIN roles r ON u.rol_id = r.id 
                WHERE u.nombre LIKE ? OR u.apellido LIKE ? OR u.email LIKE ? OR u.username LIKE ?
                ORDER BY u.nombre";
        $term = "%$term%";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$term, $term, $term, $term]);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $usuario = $this->mapToEntity($row);
            $usuario->rol_nombre = $row['rol_nombre'];
            $results[] = $usuario;
        }
        return $results;
    }
    
    public function findAllWithRoles(array $filters = [], string $orderBy = 'id DESC', int $page = 1, int $perPage = ITEMS_PER_PAGE): array {
        $where = [];
        $params = [];
        
        if (isset($filters['estado']) && $filters['estado'] !== '') {
            $where[] = "u.estado = ?";
            $params[] = $filters['estado'];
        }
        if (isset($filters['rol_id']) && $filters['rol_id'] !== '') {
            $where[] = "u.rol_id = ?";
            $params[] = $filters['rol_id'];
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
        
        $sql = "SELECT u.*, r.nombre as rol_nombre FROM {$this->table} u 
                JOIN roles r ON u.rol_id = r.id 
                $whereClause 
                ORDER BY $orderBy 
                LIMIT $perPage OFFSET " . (($page - 1) * $perPage);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $usuario = $this->mapToEntity($row);
            $usuario->rol_nombre = $row['rol_nombre'];
            $results[] = $usuario;
        }
        return $results;
    }
    
    public function paginateWithRoles(int $page = 1, int $perPage = ITEMS_PER_PAGE, array $filters = [], string $orderBy = 'u.nombre ASC'): array {
        $where = [];
        $params = [];
        
        if (isset($filters['estado']) && $filters['estado'] !== '') {
            $where[] = "u.estado = ?";
            $params[] = $filters['estado'];
        }
        if (isset($filters['rol_id']) && $filters['rol_id'] !== '') {
            $where[] = "u.rol_id = ?";
            $params[] = $filters['rol_id'];
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(' AND ', $where) : "";
        
        // Obtener items
        $sql = "SELECT u.*, r.nombre as rol_nombre FROM {$this->table} u 
                JOIN roles r ON u.rol_id = r.id 
                $whereClause 
                ORDER BY $orderBy 
                LIMIT $perPage OFFSET " . (($page - 1) * $perPage);
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $usuario = $this->mapToEntity($row);
            $usuario->rol_nombre = $row['rol_nombre'];
            $results[] = $usuario;
        }
        
        // Contar total
        $sqlCount = "SELECT COUNT(*) FROM {$this->table} u 
                     JOIN roles r ON u.rol_id = r.id 
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
    
    public function setTokenRecuperacion(int $id, string $token, string $expira): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET token_recuperacion = ?, token_expira = ? WHERE id = ?");
        return $stmt->execute([$token, $expira, $id]);
    }
    
    public function findByToken(string $token): ?Usuario {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE token_recuperacion = ? AND token_expira > NOW() AND estado = 1");
        $stmt->execute([$token]);
        $data = $stmt->fetch();
        return $data ? $this->mapToEntity($data) : null;
    }
    
    public function updatePassword(int $id, string $password): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET password = ?, token_recuperacion = NULL, token_expira = NULL WHERE id = ?");
        return $stmt->execute([password_hash($password, PASSWORD_DEFAULT), $id]);
    }
    
    public function setRememberToken(int $id, string $token, string $expira): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET token_recuperacion = ?, token_expira = ? WHERE id = ?");
        return $stmt->execute([$token, $expira, $id]);
    }
    
    public function findByRememberToken(string $token): ?Usuario {
        $stmt = $this->db->prepare("SELECT u.*, r.nombre as rol_nombre, r.permisos FROM {$this->table} u JOIN roles r ON u.rol_id = r.id WHERE u.token_recuperacion = ? AND u.token_expira > NOW() AND u.estado = 1");
        $stmt->execute([$token]);
        $data = $stmt->fetch();
        if (!$data) return null;
        $usuario = $this->mapToEntity($data);
        $usuario->rol_nombre = $data['rol_nombre'];
        $usuario->permisos = $data['permisos'];
        return $usuario;
    }
    
    public function usernameExists(string $username, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE username = ?";
        $params = [$username];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
    
    public function emailExists(string $email, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE email = ?";
        $params = [$email];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
}
