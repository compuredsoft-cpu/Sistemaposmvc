<?php
/**
 * Entidad Configuración del Sistema
 */

class Configuracion {
    public ?int $id = null;
    public string $nombre_empresa;
    public ?string $razon_social = null;
    public ?string $nit = null;
    public ?string $telefono = null;
    public ?string $email = null;
    public ?string $direccion = null;
    public ?string $ciudad = null;
    public string $pais = 'Colombia';
    public ?string $logo = null;
    public string $moneda = 'COP';
    public float $impuesto_porcentaje = 19;
    public string $prefijo_factura = 'FAC-';
    public int $numero_factura_inicial = 1;
    public string $prefijo_cotizacion = 'COT-';
    public int $numero_cotizacion_inicial = 1;
    public int $decimales = 0;
    public string $separador_decimales = ',';
    public string $separador_miles = '.';
    public ?string $fecha_creacion = null;
    public ?string $fecha_actualizacion = null;
    public ?int $usuario_creador = null;
    public ?int $usuario_actualizador = null;
    
    public function getMonedaData(): array {
        return MONEDAS[$this->moneda] ?? MONEDAS['COP'];
    }
    
    public function formatMonto(float $monto): string {
        $moneda = $this->getMonedaData();
        return $moneda['simbolo'] . ' ' . number_format($monto, $this->decimales, $this->separador_decimales, $this->separador_miles);
    }
    
    public function getNextFacturaCode(): string {
        return generateCode($this->prefijo_factura, $this->numero_factura_inicial);
    }
    
    public function getNextCotizacionCode(): string {
        return generateCode($this->prefijo_cotizacion, $this->numero_cotizacion_inicial);
    }
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'nombre_empresa' => $this->nombre_empresa,
            'razon_social' => $this->razon_social,
            'nit' => $this->nit,
            'telefono' => $this->telefono,
            'email' => $this->email,
            'direccion' => $this->direccion,
            'ciudad' => $this->ciudad,
            'pais' => $this->pais,
            'logo' => $this->logo,
            'moneda' => $this->moneda,
            'moneda_data' => $this->getMonedaData(),
            'impuesto_porcentaje' => $this->impuesto_porcentaje,
            'prefijo_factura' => $this->prefijo_factura,
            'numero_factura_inicial' => $this->numero_factura_inicial,
            'prefijo_cotizacion' => $this->prefijo_cotizacion,
            'numero_cotizacion_inicial' => $this->numero_cotizacion_inicial,
            'decimales' => $this->decimales,
            'separador_decimales' => $this->separador_decimales,
            'separador_miles' => $this->separador_miles
        ];
    }
}
