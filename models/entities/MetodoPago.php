<?php
/**
 * Entidad MetodoPago
 * Representa los métodos de pago disponibles en el sistema
 */

class MetodoPago {
    public ?int $id = null;
    public string $codigo;
    public string $nombre;
    public string $tipo;
    public ?string $descripcion = null;
    public ?string $imagen = null;
    public int $requiere_autorizacion = 0;
    public int $requiere_referencia = 0;
    public int $permite_devolucion = 1;
    public float $comision_porcentaje = 0;
    public float $comision_fija = 0;
    public ?array $configuracion = null;
    public int $orden = 0;
    public int $estado = 1;
    public ?string $fecha_creacion = null;
    
    public function getConfiguracion(): array {
        return $this->configuracion ?? [];
    }
    
    public function getIcon(): string {
        $config = $this->getConfiguracion();
        return $config['icon'] ?? 'bi-credit-card';
    }
    
    public function getColor(): string {
        $config = $this->getConfiguracion();
        return $config['color'] ?? '#6b7280';
    }
    
    public function esTarjeta(): bool {
        return $this->tipo === 'TARJETA';
    }
    
    public function esQR(): bool {
        return $this->tipo === 'QR';
    }
    
    public function esPasarela(): bool {
        return $this->tipo === 'PASARELA';
    }
    
    public function esEfectivo(): bool {
        return $this->tipo === 'EFECTIVO';
    }
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'nombre' => $this->nombre,
            'tipo' => $this->tipo,
            'descripcion' => $this->descripcion,
            'imagen' => $this->imagen,
            'requiere_autorizacion' => $this->requiere_autorizacion,
            'requiere_referencia' => $this->requiere_referencia,
            'permite_devolucion' => $this->permite_devolucion,
            'comision_porcentaje' => $this->comision_porcentaje,
            'comision_fija' => $this->comision_fija,
            'configuracion' => $this->configuracion,
            'icon' => $this->getIcon(),
            'color' => $this->getColor(),
            'orden' => $this->orden,
            'estado' => $this->estado,
            'fecha_creacion' => $this->fecha_creacion
        ];
    }
}
