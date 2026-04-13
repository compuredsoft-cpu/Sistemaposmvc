<?php
/**
 * Repositorio de Proveedores
 */

class ProveedorRepository extends BaseRepository {
    protected string $table = 'proveedores';
    
    protected function mapToEntity(array $data): Proveedor {
        $proveedor = new Proveedor();
        $proveedor->id = (int) $data['id'];
        $proveedor->tipo_documento = $data['tipo_documento'];
        $proveedor->documento = $data['documento'];
        $proveedor->nombre = $data['nombre'];
        $proveedor->contacto = $data['contacto'] ?? null;
        $proveedor->telefono = $data['telefono'] ?? null;
        $proveedor->email = $data['email'] ?? null;
        $proveedor->direccion = $data['direccion'] ?? null;
        $proveedor->ciudad = $data['ciudad'] ?? null;
        $proveedor->observaciones = $data['observaciones'] ?? null;
        $proveedor->estado = (int) $data['estado'];
        $proveedor->fecha_creacion = $data['fecha_creacion'] ?? null;
        $proveedor->fecha_actualizacion = $data['fecha_actualizacion'] ?? null;
        return $proveedor;
    }
    
    public function save(Proveedor $proveedor, ?int $usuarioId = null): bool {
        if ($proveedor->id) {
            $sql = "UPDATE {$this->table} SET tipo_documento = ?, documento = ?, nombre = ?, contacto = ?, 
                    telefono = ?, email = ?, direccion = ?, ciudad = ?, observaciones = ?, estado = ?, 
                    usuario_actualizador = ? WHERE id = ?";
            $params = [
                $proveedor->tipo_documento, $proveedor->documento, $proveedor->nombre, $proveedor->contacto,
                $proveedor->telefono, $proveedor->email, $proveedor->direccion, $proveedor->ciudad,
                $proveedor->observaciones, $proveedor->estado, $usuarioId, $proveedor->id
            ];
        } else {
            $sql = "INSERT INTO {$this->table} (tipo_documento, documento, nombre, contacto, telefono, email, 
                    direccion, ciudad, observaciones, estado, usuario_creador) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $proveedor->tipo_documento, $proveedor->documento, $proveedor->nombre, $proveedor->contacto,
                $proveedor->telefono, $proveedor->email, $proveedor->direccion, $proveedor->ciudad,
                $proveedor->observaciones, $proveedor->estado, $usuarioId
            ];
        }
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        if (!$proveedor->id && $result) {
            $proveedor->id = (int) $this->db->lastInsertId();
        }
        
        return $result;
    }
    
    public function findAllActive(): array {
        return $this->findAll(['estado' => 1], 'nombre ASC');
    }
    
    public function search(string $term): array {
        $sql = "SELECT * FROM {$this->table} WHERE (nombre LIKE ? OR documento LIKE ?) AND estado = 1 ORDER BY nombre";
        $stmt = $this->db->prepare($sql);
        $term = "%$term%";
        $stmt->execute([$term, $term]);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = $this->mapToEntity($row);
        }
        return $results;
    }
    
    public function findByDocumento(string $documento): ?Proveedor {
        return $this->findBy('documento', $documento);
    }
    
    public function documentoExists(string $documento, ?int $excludeId = null): bool {
        $sql = "SELECT COUNT(*) FROM {$this->table} WHERE documento = ?";
        $params = [$documento];
        if ($excludeId) {
            $sql .= " AND id != ?";
            $params[] = $excludeId;
        }
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }
    
    public function isUsed(int $id): bool {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM productos WHERE proveedor_id = ?");
        $stmt->execute([$id]);
        return (int) $stmt->fetchColumn() > 0;
    }
}
