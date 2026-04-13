<?php
/**
 * Entidad Categoria
 */

class Categoria {
    public ?int $id = null;
    public ?string $codigo = null;
    public string $nombre;
    public ?string $descripcion = null;
    public int $estado = 1;
    public ?string $fecha_creacion = null;
    public ?string $fecha_actualizacion = null;
    public ?int $usuario_creador = null;
    public ?int $usuario_actualizador = null;
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'estado' => $this->estado
        ];
    }
}
