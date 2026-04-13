<?php
/**
 * Entidad Proveedor
 */

class Proveedor {
    public ?int $id = null;
    public string $tipo_documento = 'NIT';
    public string $documento;
    public string $nombre;
    public ?string $contacto = null;
    public ?string $telefono = null;
    public ?string $email = null;
    public ?string $direccion = null;
    public ?string $ciudad = null;
    public ?string $observaciones = null;
    public int $estado = 1;
    public ?string $fecha_creacion = null;
    public ?string $fecha_actualizacion = null;
    public ?int $usuario_creador = null;
    public ?int $usuario_actualizador = null;
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'tipo_documento' => $this->tipo_documento,
            'documento' => $this->documento,
            'nombre' => $this->nombre,
            'contacto' => $this->contacto,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'direccion' => $this->direccion,
            'ciudad' => $this->ciudad,
            'observaciones' => $this->observaciones,
            'estado' => $this->estado
        ];
    }
}
