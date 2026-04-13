<?php
/**
 * Entidad Cotización
 */

class Cotizacion {
    public ?int $id = null;
    public string $codigo;
    public int $cliente_id;
    public int $usuario_id;
    public ?string $fecha = null;
    public ?string $fecha_vencimiento = null;
    public float $subtotal = 0;
    public float $impuesto_porcentaje = 19;
    public float $impuesto = 0;
    public float $descuento = 0;
    public float $total = 0;
    public string $estado = 'PENDIENTE';
    public ?string $observaciones = null;
    public ?string $condiciones = null;
    public ?string $tiempo_entrega = null;
    public ?string $forma_pago = null;
    public ?int $venta_id = null;
    public ?string $fecha_creacion = null;
    
    // Campos adicionales de join
    public ?string $cliente_nombre = null;
    public ?string $cliente_documento = null;
    public ?string $usuario_nombre = null;
    public ?string $venta_codigo = null;
    
    /** @var CotizacionDetalle[] */
    public array $detalles = [];
    
    public function calcularTotales(): void {
        $this->subtotal = 0;
        foreach ($this->detalles as $detalle) {
            $this->subtotal += $detalle->subtotal;
        }
        $this->impuesto = $this->subtotal * ($this->impuesto_porcentaje / 100);
        $this->total = $this->subtotal + $this->impuesto - $this->descuento;
    }
    
    public function estaVencida(): bool {
        if (!$this->fecha_vencimiento) return false;
        return strtotime($this->fecha_vencimiento) < strtotime(date('Y-m-d'));
    }
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'cliente_id' => $this->cliente_id,
            'cliente_nombre' => $this->cliente_nombre,
            'cliente_documento' => $this->cliente_documento,
            'usuario_id' => $this->usuario_id,
            'usuario_nombre' => $this->usuario_nombre,
            'fecha' => $this->fecha,
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'subtotal' => $this->subtotal,
            'impuesto_porcentaje' => $this->impuesto_porcentaje,
            'impuesto' => $this->impuesto,
            'descuento' => $this->descuento,
            'total' => $this->total,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
            'condiciones' => $this->condiciones,
            'tiempo_entrega' => $this->tiempo_entrega,
            'forma_pago' => $this->forma_pago,
            'venta_id' => $this->venta_id,
            'venta_codigo' => $this->venta_codigo,
            'detalles' => array_map(fn($d) => $d->toArray(), $this->detalles)
        ];
    }
}

class CotizacionDetalle {
    public ?int $id = null;
    public int $cotizacion_id;
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
