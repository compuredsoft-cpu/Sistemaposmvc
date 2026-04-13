<?php
/**
 * Entidad Kardex (Movimientos de Inventario)
 */

class Kardex {
    public ?int $id = null;
    public int $producto_id;
    public string $tipo_movimiento;
    public string $documento_tipo;
    public ?int $documento_id = null;
    public ?string $documento_codigo = null;
    public int $cantidad;
    public int $stock_anterior;
    public int $stock_nuevo;
    public ?float $costo_unitario = null;
    public ?float $costo_total = null;
    public ?string $observaciones = null;
    public ?string $fecha_movimiento = null;
    public ?int $usuario_creador = null;
    
    // Campos adicionales de join
    public ?string $producto_codigo = null;
    public ?string $producto_nombre = null;
    public ?string $usuario_nombre = null;
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'producto_codigo' => $this->producto_codigo,
            'producto_nombre' => $this->producto_nombre,
            'tipo_movimiento' => $this->tipo_movimiento,
            'documento_tipo' => $this->documento_tipo,
            'documento_id' => $this->documento_id,
            'documento_codigo' => $this->documento_codigo,
            'cantidad' => $this->cantidad,
            'stock_anterior' => $this->stock_anterior,
            'stock_nuevo' => $this->stock_nuevo,
            'costo_unitario' => $this->costo_unitario,
            'costo_total' => $this->costo_total,
            'observaciones' => $this->observaciones,
            'fecha_movimiento' => $this->fecha_movimiento
        ];
    }
}
