<?php
/**
 * Repositorio de Clientes
 */

class ClienteRepository extends BaseRepository {
    protected string $table = 'clientes';
    
    protected function mapToEntity(array $data): Cliente {
        $cliente = new Cliente();
        $cliente->id = (int) $data['id'];
        $cliente->tipo_documento = $data['tipo_documento'];
        $cliente->documento = $data['documento'];
        $cliente->nombre = $data['nombre'];
        $cliente->apellido = $data['apellido'] ?? null;
        $cliente->razon_social = $data['razon_social'] ?? null;
        $cliente->telefono = $data['telefono'] ?? null;
        $cliente->email = $data['email'] ?? null;
        $cliente->direccion = $data['direccion'] ?? null;
        $cliente->ciudad = $data['ciudad'] ?? null;
        $cliente->fecha_nacimiento = $data['fecha_nacimiento'] ?? null;
        $cliente->limite_credito = (float) ($data['limite_credito'] ?? 0);
        $cliente->saldo_pendiente = (float) ($data['saldo_pendiente'] ?? 0);
        $cliente->observaciones = $data['observaciones'] ?? null;
        $cliente->estado = (int) $data['estado'];
        $cliente->fecha_creacion = $data['fecha_creacion'] ?? null;
        return $cliente;
    }
    
    public function save(Cliente $cliente, ?int $usuarioId = null): bool {
        if ($cliente->id) {
            $sql = "UPDATE {$this->table} SET 
                    tipo_documento = ?, documento = ?, nombre = ?, apellido = ?, razon_social = ?, 
                    telefono = ?, email = ?, direccion = ?, ciudad = ?, fecha_nacimiento = ?, 
                    limite_credito = ?, observaciones = ?, estado = ? 
                    WHERE id = ?";
            $params = [
                $cliente->tipo_documento, $cliente->documento, $cliente->nombre, $cliente->apellido,
                $cliente->razon_social, $cliente->telefono, $cliente->email, $cliente->direccion,
                $cliente->ciudad, $cliente->fecha_nacimiento, $cliente->limite_credito,
                $cliente->observaciones, $cliente->estado, $cliente->id
            ];
        } else {
            $sql = "INSERT INTO {$this->table} 
                    (tipo_documento, documento, nombre, apellido, razon_social, telefono, email, 
                     direccion, ciudad, fecha_nacimiento, limite_credito, observaciones, estado, usuario_creador) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $cliente->tipo_documento, $cliente->documento, $cliente->nombre, $cliente->apellido,
                $cliente->razon_social, $cliente->telefono, $cliente->email, $cliente->direccion,
                $cliente->ciudad, $cliente->fecha_nacimiento, $cliente->limite_credito,
                $cliente->observaciones, $cliente->estado, $usuarioId
            ];
        }
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        if (!$cliente->id && $result) {
            $cliente->id = (int) $this->db->lastInsertId();
        }
        
        return $result;
    }
    
    public function updateSaldo(int $id, float $monto, string $tipo = 'SUMAR'): bool {
        $operador = $tipo === 'SUMAR' ? '+' : '-';
        $stmt = $this->db->prepare("UPDATE {$this->table} SET saldo_pendiente = saldo_pendiente $operador ? WHERE id = ?");
        return $stmt->execute([$monto, $id]);
    }
    
    public function findAllActive(): array {
        return $this->findAll(['estado' => 1], 'nombre ASC');
    }
    
    public function search(string $term): array {
        $sql = "SELECT * FROM {$this->table} WHERE (nombre LIKE ? OR documento LIKE ? OR telefono LIKE ?) 
                AND estado = 1 ORDER BY nombre LIMIT 20";
        $stmt = $this->db->prepare($sql);
        $term = "%$term%";
        $stmt->execute([$term, $term, $term]);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = $this->mapToEntity($row);
        }
        return $results;
    }
    
    public function findByDocumento(string $documento): ?Cliente {
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
    
    public function getClientesConDeuda(): array {
        $sql = "SELECT * FROM {$this->table} WHERE saldo_pendiente > 0 AND estado = 1 ORDER BY saldo_pendiente DESC";
        $stmt = $this->db->query($sql);
        
        $results = [];
        while ($row = $stmt->fetch()) {
            $results[] = $this->mapToEntity($row);
        }
        return $results;
    }
}
