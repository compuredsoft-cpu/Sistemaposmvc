<?php
/**
 * Entidad Producto
 */

class Producto {
    public ?int $id = null;
    public string $codigo;
    public ?string $codigo_barras = null;
    public string $nombre;
    public ?string $descripcion = null;
    public ?int $categoria_id = null;
    public ?int $proveedor_id = null;
    public string $unidad_medida = 'UNIDAD';
    public float $precio_costo = 0;
    public float $precio_venta = 0;
    public float $precio_mayorista = 0;
    public int $stock_minimo = 5;
    public int $stock_maximo = 100;
    public int $stock_actual = 0;
    public ?string $ubicacion = null;
    public ?string $imagen = null;
    public int $estado = 1;
    public ?string $fecha_creacion = null;
    public ?string $fecha_actualizacion = null;
    public ?int $usuario_creador = null;
    public ?int $usuario_actualizador = null;
    
    // Campos adicionales de join
    public ?string $categoria_nombre = null;
    public ?string $proveedor_nombre = null;
    
    public function getMargenGanancia(): float {
        if ($this->precio_costo <= 0) return 0;
        return (($this->precio_venta - $this->precio_costo) / $this->precio_costo) * 100;
    }
    
    public function estaEnStockMinimo(): bool {
        return $this->stock_actual <= $this->stock_minimo;
    }
    
    public function estaEnStockMaximo(): bool {
        return $this->stock_actual >= $this->stock_maximo;
    }
    
    public function toArray(): array {
        return [
            'id' => $this->id,
            'codigo' => $this->codigo,
            'codigo_barras' => $this->codigo_barras,
            'nombre' => $this->nombre,
            'descripcion' => $this->descripcion,
            'categoria_id' => $this->categoria_id,
            'categoria_nombre' => $this->categoria_nombre,
            'proveedor_id' => $this->proveedor_id,
            'proveedor_nombre' => $this->proveedor_nombre,
            'unidad_medida' => $this->unidad_medida,
            'precio_costo' => $this->precio_costo,
            'precio_venta' => $this->precio_venta,
            'precio_mayorista' => $this->precio_mayorista,
            'stock_minimo' => $this->stock_minimo,
            'stock_maximo' => $this->stock_maximo,
            'stock_actual' => $this->stock_actual,
            'ubicacion' => $this->ubicacion,
            'imagen' => $this->imagen,
            'estado' => $this->estado,
            'margen_ganancia' => $this->getMargenGanancia(),
            'alerta_stock' => $this->estaEnStockMinimo()
        ];
    }
}
