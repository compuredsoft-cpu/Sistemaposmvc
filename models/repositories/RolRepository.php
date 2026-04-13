<?php
/**
 * Repositorio de Roles
 */

class RolRepository extends BaseRepository
{
    protected string $table = 'roles';

    protected function mapToEntity(array $data): Rol
    {
        $rol                      = new Rol();
        $rol->id                  = (int) $data['id'];
        $rol->nombre              = $data['nombre'];
        $rol->descripcion         = $data['descripcion'] ?? null;
        $rol->permisos            = $data['permisos'] ?? null;
        $rol->estado              = (int) $data['estado'];
        $rol->fecha_creacion      = $data['fecha_creacion'] ?? null;
        $rol->fecha_actualizacion = $data['fecha_actualizacion'] ?? null;
        return $rol;
    }

    public function save(Rol $rol): bool
    {
        if ($rol->id) {
            $sql    = "UPDATE {$this->table} SET nombre = ?, descripcion = ?, permisos = ?, estado = ? WHERE id = ?";
            $params = [$rol->nombre, $rol->descripcion, $rol->permisos, $rol->estado, $rol->id];
        } else {
            $sql    = "INSERT INTO {$this->table} (nombre, descripcion, permisos, estado) VALUES (?, ?, ?, ?)";
            $params = [$rol->nombre, $rol->descripcion, $rol->permisos, $rol->estado];
        }

        $stmt   = $this->db->prepare($sql);
        $result = $stmt->execute($params);

        if (! $rol->id && $result) {
            $rol->id = (int) $this->db->lastInsertId();
        }

        return $result;
    }

    public function findAllActive(): array
    {
        return $this->findAll(['estado' => 1], 'nombre ASC');
    }

    public function search(string $term): array
    {
        $sql  = "SELECT * FROM {$this->table} WHERE nombre LIKE ? ORDER BY nombre";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(["%$term%"]);

        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = $this->mapToEntity($row);
        }
        return $results;
    }

    public function isUsed(int $id): bool
    {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM usuarios WHERE rol_id = ?");
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function findAllWithUserCount(): array
    {
        $sql = "SELECT r.*, COUNT(u.id) as total_usuarios
                FROM {$this->table} r
                LEFT JOIN usuarios u ON r.id = u.rol_id
                WHERE r.estado = 1
                GROUP BY r.id
                ORDER BY r.nombre ASC";
        $stmt = $this->db->query($sql);

        $results = [];
        while ($row = $stmt->fetch()) {
            $rol                 = $this->mapToEntity($row);
            $rol->total_usuarios = (int) $row['total_usuarios'];
            $results[]           = $rol;
        }
        return $results;
    }

    public function nameExists(string $nombre, ?int $excludeId = null): bool
    {
        $sql    = "SELECT COUNT(*) FROM {$this->table} WHERE nombre = ?";
        $params = [$nombre];
        if ($excludeId) {
            $sql      .= " AND id != ?";
            $params[]  = $excludeId;
        }
        $stmt  = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
}