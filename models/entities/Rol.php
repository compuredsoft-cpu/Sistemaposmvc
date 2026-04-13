<?php
/**
 * Entidad Rol
 */

class Rol
{
    public ?int $id = null;
    public string $nombre;
    public ?string $descripcion         = null;
    public ?string $permisos            = null;
    public int $estado                  = 1;
    public ?string $fecha_creacion      = null;
    public ?string $fecha_actualizacion = null;
    public ?int $total_usuarios         = null;

    public function getPermisosArray(): array
    {
        return json_decode($this->permisos ?? '[]', true);
    }

    public function setPermisosArray(array $permisos): void
    {
        $this->permisos = json_encode($permisos);
    }

    public function toArray(): array
    {
        return [
            'id'          => $this->id,
            'nombre'      => $this->nombre,
            'descripcion' => $this->descripcion,
            'permisos'    => $this->permisos,
            'estado'      => $this->estado,
        ];
    }
}
