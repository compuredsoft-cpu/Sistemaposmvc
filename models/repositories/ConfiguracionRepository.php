<?php
/**
 * Repositorio de Configuración
 */

class ConfiguracionRepository extends BaseRepository {
    protected string $table = 'configuracion';
    
    protected function mapToEntity(array $data): Configuracion {
        $config = new Configuracion();
        $config->id = (int) $data['id'];
        $config->nombre_empresa = $data['nombre_empresa'];
        $config->razon_social = $data['razon_social'] ?? null;
        $config->nit = $data['nit'] ?? null;
        $config->telefono = $data['telefono'] ?? null;
        $config->email = $data['email'] ?? null;
        $config->direccion = $data['direccion'] ?? null;
        $config->ciudad = $data['ciudad'] ?? null;
        $config->pais = $data['pais'];
        $config->logo = $data['logo'] ?? null;
        $config->moneda = $data['moneda'];
        $config->impuesto_porcentaje = (float) $data['impuesto_porcentaje'];
        $config->prefijo_factura = $data['prefijo_factura'];
        $config->numero_factura_inicial = (int) $data['numero_factura_inicial'];
        $config->prefijo_cotizacion = $data['prefijo_cotizacion'];
        $config->numero_cotizacion_inicial = (int) $data['numero_cotizacion_inicial'];
        $config->decimales = (int) $data['decimales'];
        $config->separador_decimales = $data['separador_decimales'];
        $config->separador_miles = $data['separador_miles'];
        $config->fecha_creacion = $data['fecha_creacion'] ?? null;
        return $config;
    }
    
    public function getConfig(): Configuracion {
        $stmt = $this->db->query("SELECT * FROM {$this->table} ORDER BY id DESC LIMIT 1");
        $data = $stmt->fetch();
        
        if (!$data) {
            // Crear configuración por defecto
            $config = new Configuracion();
            $config->nombre_empresa = 'Mi Empresa POS';
            $config->razon_social = 'Mi Empresa POS S.A.S.';
            $config->ciudad = 'Bogotá';
            $this->save($config);
            return $config;
        }
        
        return $this->mapToEntity($data);
    }
    
    public function save(Configuracion $config, ?int $usuarioId = null): bool {
        if ($config->id) {
            $sql = "UPDATE {$this->table} SET 
                    nombre_empresa = ?, razon_social = ?, nit = ?, telefono = ?, email = ?, 
                    direccion = ?, ciudad = ?, pais = ?, logo = ?, moneda = ?, 
                    impuesto_porcentaje = ?, prefijo_factura = ?, numero_factura_inicial = ?, 
                    prefijo_cotizacion = ?, numero_cotizacion_inicial = ?, decimales = ?, 
                    separador_decimales = ?, separador_miles = ?, usuario_actualizador = ? 
                    WHERE id = ?";
            $params = [
                $config->nombre_empresa, $config->razon_social, $config->nit, $config->telefono,
                $config->email, $config->direccion, $config->ciudad, $config->pais, $config->logo,
                $config->moneda, $config->impuesto_porcentaje, $config->prefijo_factura,
                $config->numero_factura_inicial, $config->prefijo_cotizacion,
                $config->numero_cotizacion_inicial, $config->decimales, $config->separador_decimales,
                $config->separador_miles, $usuarioId, $config->id
            ];
        } else {
            $sql = "INSERT INTO {$this->table} 
                    (nombre_empresa, razon_social, nit, telefono, email, direccion, ciudad, pais, 
                     logo, moneda, impuesto_porcentaje, prefijo_factura, numero_factura_inicial, 
                     prefijo_cotizacion, numero_cotizacion_inicial, decimales, separador_decimales, 
                     separador_miles, usuario_creador) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $params = [
                $config->nombre_empresa, $config->razon_social, $config->nit, $config->telefono,
                $config->email, $config->direccion, $config->ciudad, $config->pais, $config->logo,
                $config->moneda, $config->impuesto_porcentaje, $config->prefijo_factura,
                $config->numero_factura_inicial, $config->prefijo_cotizacion,
                $config->numero_cotizacion_inicial, $config->decimales, $config->separador_decimales,
                $config->separador_miles, $usuarioId
            ];
        }
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        if (!$config->id && $result) {
            $config->id = (int) $this->db->lastInsertId();
        }
        
        return $result;
    }
    
    public function incrementarNumeroFactura(): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET numero_factura_inicial = numero_factura_inicial + 1 ORDER BY id DESC LIMIT 1");
        return $stmt->execute();
    }
    
    public function incrementarNumeroCotizacion(): bool {
        $stmt = $this->db->prepare("UPDATE {$this->table} SET numero_cotizacion_inicial = numero_cotizacion_inicial + 1 ORDER BY id DESC LIMIT 1");
        return $stmt->execute();
    }
}
