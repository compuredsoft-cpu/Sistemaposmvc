<?php
/**
 * Entidad Compra
 */

class Compra {
    public ?int $id = null;
    public string $codigo;
    public int $proveedor_id;
    public string $fecha;
    public ?string $fecha_registro = null;
    public float $subtotal = 0;
    public float $impuesto = 0;
    public float $total = 0;
    public string $metodo_pago = 'EFECTIVO';
    public string $estado = 'PENDIENTE';
    public ?string $observaciones = null;
    public ?int $usuario_creador = null;
    
    // Campos adicionales de join
    public ?string $proveedor_nombre = null;
    public ?string $usuario_nombre = null;
    
    /** @var CompraDetalle[] */
    public array $detalles = [];
    
    public function calcularTotales(): void {
        $this->subtotal = 0;
        foreach ($this->detalles as $detalle) {
            $this->subtotal += $detalle->subtotal;
        }
        $this->total = $this->subtotal + $this->impuesto;
    }
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'proveedor_id' => $this->proveedor_id,
            'proveedor_nombre' => $this->proveedor_nombre,
            'fecha' => $this->fecha,
            'subtotal' => $this->subtotal,
            'impuesto' => $this->impuesto,
            'total' => $this->total,
            'metodo_pago' => $this->metodo_pago,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
            'detalles' => array_map(fn($d) => $d->toArray(), $this->detalles)
        ];
    }
}

class CompraDetalle {
    public ?int $id = null;
    public int $compra_id;
    public int $producto_id;
    public int $cantidad;
    public float $precio_unitario;
    public float $subtotal;
    
    // Campos adicionales de join
    public ?string $producto_codigo = null;
    public ?string $producto_nombre = null;
    
    public function calcularSubtotal(): void {
        $this->subtotal = $this->cantidad * $this->precio_unitario;
    }
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'producto_id' => $this->producto_id,
            'producto_codigo' => $this->producto_codigo,
            'producto_nombre' => $this->producto_nombre,
            'cantidad' => $this->cantidad,
            'precio_unitario' => $this->precio_unitario,
            'subtotal' => $this->subtotal
        ];
    }
}
