<?php
/**
 * Entidad Gasto
 */

class Gasto {
    public ?int $id = null;
    public int $tipo_gasto_id;
    public ?int $caja_id = null;
    public ?string $fecha = null;
    public string $concepto;
    public float $monto;
    public string $metodo_pago = 'EFECTIVO';
    public ?string $referencia = null;
    public ?string $proveedor = null;
    public ?string $descripcion = null;
    public string $tipo = 'GASTO';
    public int $estado = 1;
    public ?int $usuario_creador = null;
    
    // Campos adicionales de join
    public ?string $tipo_gasto_nombre = null;
    public ?string $tipo_gasto_categoria = null;
    public ?string $usuario_nombre = null;
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'tipo_gasto_id' => $this->tipo_gasto_id,
            'tipo_gasto_nombre' => $this->tipo_gasto_nombre,
            'tipo_gasto_categoria' => $this->tipo_gasto_categoria,
            'fecha' => $this->fecha,
            'concepto' => $this->concepto,
            'monto' => $this->monto,
            'metodo_pago' => $this->metodo_pago,
            'referencia' => $this->referencia,
            'proveedor' => $this->proveedor,
            'descripcion' => $this->descripcion,
            'tipo' => $this->tipo,
            'estado' => $this->estado
        ];
    }
}

class TipoGasto {
    public ?int $id = null;
    public string $nombre;
    public ?string $descripcion = null;
    public string $tipo = 'GASTO';
    public int $estado = 1;
    public ?string $fecha_creacion = null;
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'tipo' => $this->tipo,
            'estado' => $this->estado
        ];
    }
}
