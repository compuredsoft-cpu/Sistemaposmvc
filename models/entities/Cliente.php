<?php
/**
 * Entidad Cliente
 */

class Cliente {
    public ?int $id = null;
    public string $tipo_documento = 'CC';
    public string $documento;
    public string $nombre;
    public ?string $apellido = null;
    public ?string $razon_social = null;
    public ?string $telefono = null;
    public ?string $email = null;
    public ?string $direccion = null;
    public ?string $ciudad = null;
    public ?string $fecha_nacimiento = null;
    public float $limite_credito = 0;
    public float $saldo_pendiente = 0;
    public ?string $observaciones = null;
    public int $estado = 1;
    public ?string $fecha_creacion = null;
    public ?string $fecha_actualizacion = null;
    public ?int $usuario_creador = null;
    
    public function getNombreCompleto(): string {
        if ($this->razon_social) {
            return $this->razon_social;
        }
        return $this->nombre . ' ' . ($this->apellido ?? '');
    }
    
    public function getNombreCorto(): string {
        return $this->nombre . ' ' . ($this->apellido ?? '');
    }
    
    public function tieneCreditoDisponible(): bool {
        return ($this->limite_credito - $this->saldo_pendiente) > 0;
    }
    
    public function getCreditoDisponible(): float {
        return $this->limite_credito - $this->saldo_pendiente;
    }
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'tipo_documento' => $this->tipo_documento,
            'documento' => $this->documento,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'razon_social' => $this->razon_social,
            'nombre_completo' => $this->getNombreCompleto(),
            'telefono' => $this->telefono,
            'email' => $this->email,
            'direccion' => $this->direccion,
            'ciudad' => $this->ciudad,
            'fecha_nacimiento' => $this->fecha_nacimiento,
            'limite_credito' => $this->limite_credito,
            'saldo_pendiente' => $this->saldo_pendiente,
            'credito_disponible' => $this->getCreditoDisponible(),
            'observaciones' => $this->observaciones,
            'estado' => $this->estado
        ];
    }
}
