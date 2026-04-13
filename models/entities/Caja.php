<?php
/**
 * Entidad Caja (Apertura y Cierre)
 */

class Caja {
    public ?int $id = null;
    public int $usuario_id;
    public ?string $fecha_apertura = null;
    public ?string $fecha_cierre = null;
    public float $monto_apertura = 0;
    public float $total_ventas = 0;
    public float $total_compras = 0;
    public float $total_ingresos = 0;
    public float $total_egresos = 0;
    public float $total_efectivo = 0;
    public float $total_tarjeta = 0;
    public float $total_transferencia = 0;
    public float $total_cheque = 0;
    public float $total_credito = 0;
    public float $monto_cierre = 0;
    public float $diferencia = 0;
    public ?string $observaciones_apertura = null;
    public ?string $observaciones_cierre = null;
    public string $estado = 'ABIERTA';
    
    // Campos adicionales de join
    public ?string $usuario_nombre = null;
    public ?string $usuario_apertura = null;
    
    public function calcularDiferencia(): void {
        $efectivoEsperado = $this->monto_apertura + $this->total_efectivo - $this->total_egresos;
        $this->diferencia = $this->monto_cierre - $efectivoEsperado;
    }
    
    public function getTotalVentasPorMedio(): float {
        return $this->total_efectivo + $this->total_tarjeta + 
               $this->total_transferencia + $this->total_cheque;
    }
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'usuario_id' => $this->usuario_id,
            'usuario_nombre' => $this->usuario_nombre,
            'fecha_apertura' => $this->fecha_apertura,
            'fecha_cierre' => $this->fecha_cierre,
            'monto_apertura' => $this->monto_apertura,
            'total_ventas' => $this->total_ventas,
            'total_compras' => $this->total_compras,
            'total_ingresos' => $this->total_ingresos,
            'total_egresos' => $this->total_egresos,
            'total_efectivo' => $this->total_efectivo,
            'total_tarjeta' => $this->total_tarjeta,
            'total_transferencia' => $this->total_transferencia,
            'total_cheque' => $this->total_cheque,
            'total_credito' => $this->total_credito,
            'monto_cierre' => $this->monto_cierre,
            'diferencia' => $this->diferencia,
            'observaciones_apertura' => $this->observaciones_apertura,
            'observaciones_cierre' => $this->observaciones_cierre,
            'estado' => $this->estado
        ];
    }
}
