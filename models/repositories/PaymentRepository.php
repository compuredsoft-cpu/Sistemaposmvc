<?php
/**
 * PaymentRepository
 * Gestiona métodos de pago y transacciones de ventas
 */

class PaymentRepository extends BaseRepository {
    protected string $table = 'metodos_pago';
    
    protected function mapToEntity(array $data): MetodoPago {
        $metodo = new MetodoPago();
        $metodo->id = (int) $data['id'];
        $metodo->codigo = $data['codigo'];
        $metodo->nombre = $data['nombre'];
        $metodo->tipo = $data['tipo'];
        $metodo->descripcion = $data['descripcion'] ?? null;
        $metodo->imagen = $data['imagen'] ?? null;
        $metodo->requiere_autorizacion = (int) ($data['requiere_autorizacion'] ?? 0);
        $metodo->requiere_referencia = (int) ($data['requiere_referencia'] ?? 0);
        $metodo->permite_devolucion = (int) ($data['permite_devolucion'] ?? 1);
        $metodo->comision_porcentaje = (float) ($data['comision_porcentaje'] ?? 0);
        $metodo->comision_fija = (float) ($data['comision_fija'] ?? 0);
        $metodo->configuracion = json_decode($data['configuracion'] ?? '{}', true);
        $metodo->orden = (int) ($data['orden'] ?? 0);
        $metodo->estado = (int) ($data['estado'] ?? 1);
        $metodo->fecha_creacion = $data['fecha_creacion'] ?? null;
        return $metodo;
    }
    
    /**
     * Obtener todos los métodos de pago activos
     */
    public function findAllActive(): array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE estado = 1 ORDER BY orden ASC, nombre ASC");
        $stmt->execute();
        
        $result = [];
        while ($data = $stmt->fetch()) {
            $result[] = $this->mapToEntity($data);
        }
        return $result;
    }
    
    /**
     * Buscar método de pago por código
     */
    public function findByCodigo(string $codigo): ?MetodoPago {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE codigo = ? AND estado = 1 LIMIT 1");
        $stmt->execute([$codigo]);
        $data = $stmt->fetch();
        return $data ? $this->mapToEntity($data) : null;
    }
    
    /**
     * Obtener métodos por tipo
     */
    public function findByTipo(string $tipo): array {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE tipo = ? AND estado = 1 ORDER BY orden ASC");
        $stmt->execute([$tipo]);
        
        $result = [];
        while ($data = $stmt->fetch()) {
            $result[] = $this->mapToEntity($data);
        }
        return $result;
    }
    
    /**
     * Guardar pago de venta (soporte para split payments)
     */
    public function guardarVentaPago(VentaPago $pago): bool {
        $sql = "INSERT INTO venta_pagos 
                (venta_id, metodo_pago_id, monto, monto_recibido, cambio, referencia, 
                 autorizacion, ultimos_digitos, tipo_tarjeta, banco_origen, 
                 numero_cuenta, titular_cuenta, numero_transaccion, estado, 
                 codigo_respuesta, mensaje_respuesta, datos_adicionales, procesado_por) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $pago->venta_id,
            $pago->metodo_pago_id,
            $pago->monto,
            $pago->monto_recibido,
            $pago->cambio,
            $pago->referencia,
            $pago->autorizacion,
            $pago->ultimos_digitos,
            $pago->tipo_tarjeta,
            $pago->banco_origen,
            $pago->numero_cuenta,
            $pago->titular_cuenta,
            $pago->numero_transaccion,
            $pago->estado,
            $pago->codigo_respuesta,
            $pago->mensaje_respuesta,
            json_encode($pago->datos_adicionales ?? []),
            $pago->procesado_por
        ];
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute($params);
        
        if ($result && !$pago->id) {
            $pago->id = (int) $this->db->lastInsertId();
        }
        
        return $result;
    }
    
    /**
     * Actualizar estado de un pago
     */
    public function actualizarEstadoPago(int $pagoId, string $estado, ?string $codigoRespuesta = null, ?string $mensajeRespuesta = null): bool {
        $sql = "UPDATE venta_pagos SET estado = ?, codigo_respuesta = ?, mensaje_respuesta = ?, fecha_confirmacion = NOW() WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$estado, $codigoRespuesta, $mensajeRespuesta, $pagoId]);
    }
    
    /**
     * Obtener pagos de una venta
     */
    public function getPagosByVenta(int $ventaId): array {
        $sql = "SELECT vp.*, mp.nombre as metodo_pago_nombre, mp.codigo as metodo_pago_codigo, 
                       mp.tipo as metodo_pago_tipo, CONCAT(u.nombre, ' ', u.apellido) as usuario_nombre
                FROM venta_pagos vp
                JOIN metodos_pago mp ON vp.metodo_pago_id = mp.id
                LEFT JOIN usuarios u ON vp.procesado_por = u.id
                WHERE vp.venta_id = ?
                ORDER BY vp.id ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$ventaId]);
        
        $result = [];
        while ($data = $stmt->fetch()) {
            $pago = new VentaPago();
            $pago->id = (int) $data['id'];
            $pago->venta_id = (int) $data['venta_id'];
            $pago->metodo_pago_id = (int) $data['metodo_pago_id'];
            $pago->monto = (float) $data['monto'];
            $pago->monto_recibido = (float) $data['monto_recibido'];
            $pago->cambio = (float) $data['cambio'];
            $pago->referencia = $data['referencia'];
            $pago->autorizacion = $data['autorizacion'];
            $pago->ultimos_digitos = $data['ultimos_digitos'];
            $pago->tipo_tarjeta = $data['tipo_tarjeta'];
            $pago->banco_origen = $data['banco_origen'];
            $pago->numero_cuenta = $data['numero_cuenta'];
            $pago->titular_cuenta = $data['titular_cuenta'];
            $pago->numero_transaccion = $data['numero_transaccion'];
            $pago->estado = $data['estado'];
            $pago->codigo_respuesta = $data['codigo_respuesta'];
            $pago->mensaje_respuesta = $data['mensaje_respuesta'];
            $pago->datos_adicionales = json_decode($data['datos_adicionales'] ?? '{}', true);
            $pago->fecha_pago = $data['fecha_pago'];
            $pago->fecha_confirmacion = $data['fecha_confirmacion'];
            $pago->procesado_por = $data['procesado_por'] ? (int) $data['procesado_por'] : null;
            $pago->metodo_pago_nombre = $data['metodo_pago_nombre'];
            $pago->metodo_pago_codigo = $data['metodo_pago_codigo'];
            $pago->metodo_pago_tipo = $data['metodo_pago_tipo'];
            $pago->usuario_nombre = $data['usuario_nombre'];
            $result[] = $pago;
        }
        
        return $result;
    }
    
    /**
     * Calcular total pagado de una venta
     */
    public function getTotalPagado(int $ventaId): float {
        $stmt = $this->db->prepare("SELECT SUM(monto) as total FROM venta_pagos WHERE venta_id = ? AND estado = 'APROBADO'");
        $stmt->execute([$ventaId]);
        return (float) ($stmt->fetchColumn() ?? 0);
    }
    
    /**
     * Verificar si venta está completamente pagada
     */
    public function ventaEstaPagada(int $ventaId, float $totalVenta): bool {
        $totalPagado = $this->getTotalPagado($ventaId);
        return $totalPagado >= $totalVenta;
    }
    
    /**
     * Procesar múltiples pagos (split payment)
     */
    public function procesarPagosVenta(int $ventaId, array $pagos, ?int $usuarioId = null): array {
        $resultado = [
            'success' => true,
            'pagos_guardados' => [],
            'total_pagado' => 0,
            'errors' => []
        ];
        
        try {
            $this->db->beginTransaction();
            
            foreach ($pagos as $pagoData) {
                $pago = new VentaPago();
                $pago->venta_id = $ventaId;
                $pago->metodo_pago_id = $pagoData['metodo_pago_id'];
                $pago->monto = $pagoData['monto'];
                $pago->monto_recibido = $pagoData['monto_recibido'] ?? $pagoData['monto'];
                $pago->calcularCambio();
                $pago->referencia = $pagoData['referencia'] ?? null;
                $pago->autorizacion = $pagoData['autorizacion'] ?? null;
                $pago->ultimos_digitos = $pagoData['ultimos_digitos'] ?? null;
                $pago->tipo_tarjeta = $pagoData['tipo_tarjeta'] ?? null;
                $pago->estado = $pagoData['estado'] ?? 'APROBADO';
                $pago->procesado_por = $usuarioId;
                
                if ($this->guardarVentaPago($pago)) {
                    $resultado['pagos_guardados'][] = $pago->toArray();
                    $resultado['total_pagado'] += $pago->monto;
                } else {
                    $resultado['errors'][] = "Error al guardar pago con método {$pagoData['metodo_pago_id']}";
                }
            }
            
            $this->db->commit();
            
        } catch (Exception $e) {
            $this->db->rollBack();
            $resultado['success'] = false;
            $resultado['errors'][] = $e->getMessage();
        }
        
        return $resultado;
    }
    
    /**
     * Crear transacción de pasarela
     */
    public function crearTransaccionPasarela(array $data): ?int {
        $sql = "INSERT INTO transacciones_pasarela 
                (venta_id, venta_pago_id, pasarela, referencia_interna, monto, moneda, 
                 cliente_email, cliente_nombre, cliente_documento, cliente_telefono, url_checkout)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['venta_id'],
            $data['venta_pago_id'] ?? null,
            $data['pasarela'],
            $data['referencia_interna'],
            $data['monto'],
            $data['moneda'] ?? 'COP',
            $data['cliente_email'] ?? null,
            $data['cliente_nombre'] ?? null,
            $data['cliente_documento'] ?? null,
            $data['cliente_telefono'] ?? null,
            $data['url_checkout'] ?? null
        ]);
        
        return $result ? (int) $this->db->lastInsertId() : null;
    }
    
    /**
     * Actualizar transacción de pasarela
     */
    public function actualizarTransaccionPasarela(int $id, array $data): bool {
        $campos = [];
        $valores = [];
        
        if (isset($data['estado'])) {
            $campos[] = 'estado = ?';
            $valores[] = $data['estado'];
        }
        if (isset($data['referencia_externa'])) {
            $campos[] = 'referencia_externa = ?';
            $valores[] = $data['referencia_externa'];
        }
        if (isset($data['datos_respuesta'])) {
            $campos[] = 'datos_respuesta = ?';
            $valores[] = json_encode($data['datos_respuesta']);
        }
        if (isset($data['token_tarjeta'])) {
            $campos[] = 'token_tarjeta = ?';
            $valores[] = $data['token_tarjeta'];
        }
        
        if (empty($campos)) return false;
        
        $valores[] = $id;
        $sql = "UPDATE transacciones_pasarela SET " . implode(', ', $campos) . " WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($valores);
    }
    
    /**
     * Crear código QR
     */
    public function crearCodigoQR(array $data): ?int {
        $sql = "INSERT INTO codigos_qr 
                (venta_id, venta_pago_id, tipo, monto, qr_data, qr_imagen, referencia, fecha_expiracion)
                VALUES (?, ?, ?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 10 MINUTE))";
        
        $stmt = $this->db->prepare($sql);
        $result = $stmt->execute([
            $data['venta_id'],
            $data['venta_pago_id'] ?? null,
            $data['tipo'],
            $data['monto'],
            $data['qr_data'],
            $data['qr_imagen'] ?? null,
            $data['referencia']
        ]);
        
        return $result ? (int) $this->db->lastInsertId() : null;
    }
    
    /**
     * Obtener configuración de pasarela
     */
    public function getConfiguracionPasarela(string $pasarela): ?array {
        $stmt = $this->db->prepare("SELECT * FROM configuracion_pasarelas WHERE pasarela = ? AND activo = 1 LIMIT 1");
        $stmt->execute([$pasarela]);
        $data = $stmt->fetch();
        
        if (!$data) return null;
        
        $data['configuracion_adicional'] = json_decode($data['configuracion_adicional'] ?? '{}', true);
        return $data;
    }
    
    /**
     * Obtener cuentas bancarias activas
     */
    public function getCuentasBancarias(): array {
        $stmt = $this->db->prepare("SELECT * FROM cuentas_bancarias WHERE estado = 1 ORDER BY es_predeterminada DESC, banco ASC");
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
