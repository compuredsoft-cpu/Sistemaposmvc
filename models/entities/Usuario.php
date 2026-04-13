<?php
/**
 * Entidad Usuario
 */

class Usuario {
    public ?int $id = null;
    public int $rol_id;
    public string $nombre;
    public string $apellido;
    public string $email;
    public ?string $telefono = null;
    public ?string $direccion = null;
    public string $username;
    public string $password;
    public ?string $avatar = null;
    public ?string $ultimo_acceso = null;
    public ?string $token_recuperacion = null;
    public ?string $token_expira = null;
    public int $estado = 1;
    public ?string $fecha_creacion = null;
    public ?string $fecha_actualizacion = null;
    
    // Campos adicionales de join
    public ?string $rol_nombre = null;
    public ?string $permisos = null;
    
    public function getNombreCompleto(): string {
        return $this->nombre . ' ' . $this->apellido;
    }
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'rol_id' => $this->rol_id,
            'nombre' => $this->nombre,
            'apellido' => $this->apellido,
            'email' => $this->email,
            'telefono' => $this->telefono,
            'direccion' => $this->direccion,
            'username' => $this->username,
            'avatar' => $this->avatar,
            'estado' => $this->estado,
            'rol_nombre' => $this->rol_nombre,
            'ultimo_acceso' => $this->ultimo_acceso,
            'nombre_completo' => $this->getNombreCompleto()
        ];
    }
}
