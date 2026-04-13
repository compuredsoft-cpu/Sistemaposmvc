<?php
/**
 * Entidad Cuenta Por Cobrar
 */

class CuentaPorCobrar {
    public ?int $id = null;
    public int $cliente_id;
    public ?int $venta_id = null;
    public ?string $documento = null;
    public float $monto_total;
    public float $monto_pagado = 0;
    public float $monto_pendiente;
    public string $fecha_emision;
    public ?string $fecha_vencimiento = null;
    public int $plazo_dias = 30;
    public string $estado = 'PENDIENTE';
    public ?string $observaciones = null;
    public ?string $fecha_creacion = null;
    public ?string $fecha_actualizacion = null;
    public ?int $usuario_creador = null;
    
    // Campos adicionales de join
    public ?string $cliente_nombre = null;
    public ?string $cliente_documento = null;
    public ?string $venta_codigo = null;
    
    /** @var PagoCxC[] */
    public array $pagos = [];
    
    public function calcularSaldo(): void {
        $this->monto_pagado = array_sum(array_map(fn($p) => $p->monto, $this->pagos));
        $this->monto_pendiente = $this->monto_total - $this->monto_pagado;
        
        if ($this->monto_pendiente <= 0) {
            $this->estado = 'PAGADA';
        } elseif ($this->monto_pagado > 0) {
            $this->estado = 'PARCIAL';
        }
        
        // Verificar si está vencida
        if ($this->estado !== 'PAGADA' && $this->fecha_vencimiento) {
            if (strtotime($this->fecha_vencimiento) < strtotime(date('Y-m-d'))) {
                $this->estado = 'VENCIDA';
            }
        }
    }
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'cliente_id' => $this->cliente_id,
            'cliente_nombre' => $this->cliente_nombre,
            'cliente_documento' => $this->cliente_documento,
            'venta_id' => $this->venta_id,
            'venta_codigo' => $this->venta_codigo,
            'documento' => $this->documento,
            'monto_total' => $this->monto_total,
            'monto_pagado' => $this->monto_pagado,
            'monto_pendiente' => $this->monto_pendiente,
            'fecha_emision' => $this->fecha_emision,
            'fecha_vencimiento' => $this->fecha_vencimiento,
            'plazo_dias' => $this->plazo_dias,
            'estado' => $this->estado,
            'observaciones' => $this->observaciones,
            'pagos' => array_map(fn($p) => $p->toArray(), $this->pagos)
        ];
    }
}

class PagoCxC {
    public ?int $id = null;
    public int $cuenta_cobrar_id;
    public ?string $fecha_pago = null;
    public float $monto;
    public string $metodo_pago = 'EFECTIVO';
    public ?string $referencia = null;
    public ?string $observaciones = null;
    public ?int $usuario_creador = null;
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'fecha_pago' => $this->fecha_pago,
            'monto' => $this->monto,
            'metodo_pago' => $this->metodo_pago,
            'referencia' => $this->referencia,
            'observaciones' => $this->observaciones
        ];
    }
}
