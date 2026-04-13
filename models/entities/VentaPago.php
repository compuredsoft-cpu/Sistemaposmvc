<?php
/**
 * Entidad VentaPago
 * Representa un pago individual dentro de una venta (soporta pagos divididos)
 */

class VentaPago {
    public ?int $id = null;
    public int $venta_id;
    public int $metodo_pago_id;
    public float $monto;
    public float $monto_recibido = 0;
    public float $cambio = 0;
    public ?string $referencia = null;
    public ?string $autorizacion = null;
    public ?string $ultimos_digitos = null;
    public ?string $tipo_tarjeta = null;
    public ?string $banco_origen = null;
    public ?string $numero_cuenta = null;
    public ?string $titular_cuenta = null;
    public ?string $numero_transaccion = null;
    public string $estado = 'PENDIENTE';
    public ?string $codigo_respuesta = null;
    public ?string $mensaje_respuesta = null;
    public ?array $datos_adicionales = null;
    public ?string $fecha_pago = null;
    public ?string $fecha_confirmacion = null;
    public ?int $procesado_por = null;
    
    // Campos adicionales de join
    public ?string $metodo_pago_nombre = null;
    public ?string $metodo_pago_codigo = null;
    public ?string $metodo_pago_tipo = null;
    public ?string $usuario_nombre = null;
    
    public function getDatosAdicionales(): array {
        return $this->datos_adicionales ?? [];
    }
    
    public function estaAprobado(): bool {
        return $this->estado === 'APROBADO';
    }
    
    public function estaPendiente(): bool {
        return $this->estado === 'PENDIENTE';
    }
    
    public function puedeDevolverse(): bool {
        return in_array($this->estado, ['APROBADO', 'DEVUELTO']);
    }
    
    public function calcularCambio(): void {
        if ($this->monto_recibido > $this->monto) {
            $this->cambio = $this->monto_recibido - $this->monto;
        } else {
            $this->cambio = 0;
        }
    }
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'venta_id' => $this->venta_id,
            'metodo_pago_id' => $this->metodo_pago_id,
            'metodo_pago_nombre' => $this->metodo_pago_nombre,
            'metodo_pago_codigo' => $this->metodo_pago_codigo,
            'metodo_pago_tipo' => $this->metodo_pago_tipo,
            'monto' => $this->monto,
            'monto_recibido' => $this->monto_recibido,
            'cambio' => $this->cambio,
            'referencia' => $this->referencia,
            'autorizacion' => $this->autorizacion,
            'ultimos_digitos' => $this->ultimos_digitos,
            'tipo_tarjeta' => $this->tipo_tarjeta,
            'banco_origen' => $this->banco_origen,
            'numero_cuenta' => $this->numero_cuenta,
            'titular_cuenta' => $this->titular_cuenta,
            'numero_transaccion' => $this->numero_transaccion,
            'estado' => $this->estado,
            'codigo_respuesta' => $this->codigo_respuesta,
            'mensaje_respuesta' => $this->mensaje_respuesta,
            'datos_adicionales' => $this->datos_adicionales,
            'fecha_pago' => $this->fecha_pago,
            'fecha_confirmacion' => $this->fecha_confirmacion,
            'procesado_por' => $this->procesado_por,
            'usuario_nombre' => $this->usuario_nombre,
            'esta_aprobado' => $this->estaAprobado(),
            'puede_devolverse' => $this->puedeDevolverse()
        ];
    }
}
