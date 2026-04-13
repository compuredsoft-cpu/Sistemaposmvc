<?php
/**
 * Entidad Venta
 */

class Venta {
    public ?int $id = null;
    public string $codigo;
    public int $cliente_id;
    public int $usuario_id;
    public ?int $caja_id = null;
    public ?string $fecha = null;
    public float $subtotal = 0;
    public float $impuesto_porcentaje = 19;
    public float $impuesto = 0;
    public float $descuento = 0;
    public float $total = 0;
    public string $metodo_pago = 'EFECTIVO';
    public string $estado = 'PENDIENTE';
    public ?string $observaciones = null;
    public int $es_credito = 0;
    public int $cuotas = 1;
    public float $valor_cuota = 0;
    public float $saldo_pendiente = 0;
    public ?string $fecha_vencimiento = null;
    
    // Campos adicionales de join
    public ?string $cliente_nombre = null;
    public ?string $usuario_nombre = null;
    public ?string $caja_codigo = null;
    
    /** @var VentaDetalle[] */
    public array $detalles = [];
    
    public function calcularTotales(): void {
        $this->subtotal = 0;
        foreach ($this->detalles as $detalle) {
            $this->subtotal += $detalle->subtotal;
        }
        $this->impuesto = $this->subtotal * ($this->impuesto_porcentaje / 100);
        $this->total = $this->subtotal + $this->impuesto - $this->descuento;
        if ($this->es_credito && $this->cuotas > 0) {
            $this->valor_cuota = $this->total / $this->cuotas;
            $this->saldo_pendiente = $this->total;
        }
    }
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'cliente_id' => $this->cliente_id,
            'cliente_nombre' => $this->cliente_nombre,
            'usuario_id' => $this->usuario_id,
            'usuario_nombre' => $this->usuario_nombre,
            'caja_id' => $this->caja_id,
            'fecha' => $this->fecha,
            'subtotal' => $this->subtotal,
            'impuesto_porcentaje' => $this->impuesto_porcentaje,
            'impuesto' => $this->impuesto,
            'descuento' => $this->descuento,
            'total' => $this->total,
            'metodo_pago' => $this->metodo_pago,
            'estado' => $this->estado,
            'es_credito' => $this->es_credito,
            'cuotas' => $this->cuotas,
            'valor_cuota' => $this->valor_cuota,
            'saldo_pendiente' => $this->saldo_pendiente,
            'observaciones' => $this->observaciones,
            'detalles' => array_map(fn($d) => $d->toArray(), $this->detalles)
        ];
    }
}

class VentaDetalle {
    public ?int $id = null;
    public int $venta_id;
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
