<?php
/**
 * PaymentGatewayService
 * Integración con pasarelas de pago (Wompi, PlaceToPay, Stripe)
 */

class PaymentGatewayService {
    private PaymentRepository $paymentRepo;
    
    public function __construct() {
        $this->paymentRepo = new PaymentRepository();
    }
    
    /**
     * Crear transacción en Wompi
     */
    public function crearTransaccionWompi(array $data): array {
        $config = $this->paymentRepo->getConfiguracionPasarela('WOMPI');
        
        if (!$config) {
            return ['success' => false, 'message' => 'Wompi no está configurado'];
        }
        
        $baseUrl = $config['endpoint_base'];
        $publicKey = $config['public_key'];
        
        $reference = 'VENTA-' . $data['venta_id'] . '-' . time();
        
        $payload = [
            'acceptance_token' => $data['acceptance_token'] ?? '',
            'amount_in_cents' => (int) ($data['monto'] * 100),
            'currency' => 'COP',
            'customer_email' => $data['cliente_email'],
            'payment_method' => $this->mapPaymentMethodWompi($data['metodo_pago']),
            'reference' => $reference,
            'customer_data' => [
                'phone_number' => $data['cliente_telefono'] ?? '',
                'full_name' => $data['cliente_nombre'] ?? '',
                'legal_id' => $data['cliente_documento'] ?? '',
                'legal_id_type' => 'CC'
            ]
        ];
        
        // Si es tarjeta, agregar token
        if (isset($data['token_tarjeta'])) {
            $payload['payment_method']['token'] = $data['token_tarjeta'];
        }
        
        try {
            $ch = curl_init("$baseUrl/transactions");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $publicKey,
                'Content-Type: application/json'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($httpCode === 201 && isset($result['data']['id'])) {
                // Guardar transacción
                $transaccionId = $this->paymentRepo->crearTransaccionPasarela([
                    'venta_id' => $data['venta_id'],
                    'venta_pago_id' => $data['venta_pago_id'] ?? null,
                    'pasarela' => 'WOMPI',
                    'referencia_interna' => $reference,
                    'referencia_externa' => $result['data']['id'],
                    'monto' => $data['monto'],
                    'cliente_email' => $data['cliente_email'],
                    'cliente_nombre' => $data['cliente_nombre'] ?? null,
                    'cliente_documento' => $data['cliente_documento'] ?? null,
                    'cliente_telefono' => $data['cliente_telefono'] ?? null
                ]);
                
                return [
                    'success' => true,
                    'transaccion_id' => $transaccionId,
                    'reference' => $reference,
                    'wompi_id' => $result['data']['id'],
                    'status' => $result['data']['status'],
                    'redirect_url' => $result['data']['redirect_url'] ?? null
                ];
            }
            
            return [
                'success' => false,
                'message' => $result['error']['message'] ?? 'Error al crear transacción Wompi',
                'details' => $result['error'] ?? null
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Crear sesión en PlaceToPay
     */
    public function crearSesionPlaceToPay(array $data): array {
        $config = $this->paymentRepo->getConfiguracionPasarela('PLACETOPAY');
        
        if (!$config) {
            return ['success' => false, 'message' => 'PlaceToPay no está configurado'];
        }
        
        $baseUrl = $config['endpoint_base'];
        $login = $config['api_key'];
        $secretKey = $config['api_secret'];
        
        // Generar nonce y seed
        $nonce = uniqid();
        $seed = date('c');
        $tranKey = base64_encode(hash('sha256', $nonce . $seed . $secretKey, true));
        
        $reference = 'VENTA-' . $data['venta_id'] . '-' . time();
        
        $payload = [
            'auth' => [
                'login' => $login,
                'tranKey' => $tranKey,
                'nonce' => base64_encode($nonce),
                'seed' => $seed
            ],
            'buyer' => [
                'name' => $data['cliente_nombre'] ?? '',
                'surname' => '',
                'email' => $data['cliente_email'],
                'document' => $data['cliente_documento'] ?? '',
                'documentType' => 'CC',
                'mobile' => $data['cliente_telefono'] ?? ''
            ],
            'payment' => [
                'reference' => $reference,
                'description' => 'Pago venta #' . $data['venta_id'],
                'amount' => [
                    'currency' => 'COP',
                    'total' => (float) $data['monto']
                ]
            ],
            'expiration' => date('c', strtotime('+1 hour')),
            'returnUrl' => $data['return_url'] ?? SITE_URL . '/views/pagos/confirmar.php',
            'ipAddress' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            'userAgent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Mozilla/5.0'
        ];
        
        try {
            $ch = curl_init("$baseUrl/api/session");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($httpCode === 200 && isset($result['requestId'])) {
                // Guardar transacción
                $transaccionId = $this->paymentRepo->crearTransaccionPasarela([
                    'venta_id' => $data['venta_id'],
                    'venta_pago_id' => $data['venta_pago_id'] ?? null,
                    'pasarela' => 'PLACETOPAY',
                    'referencia_interna' => $reference,
                    'referencia_externa' => $result['requestId'],
                    'request_id' => $result['requestId'],
                    'monto' => $data['monto'],
                    'cliente_email' => $data['cliente_email'],
                    'cliente_nombre' => $data['cliente_nombre'] ?? null,
                    'cliente_documento' => $data['cliente_documento'] ?? null,
                    'cliente_telefono' => $data['cliente_telefono'] ?? null,
                    'url_checkout' => $result['processUrl']
                ]);
                
                return [
                    'success' => true,
                    'transaccion_id' => $transaccionId,
                    'reference' => $reference,
                    'request_id' => $result['requestId'],
                    'process_url' => $result['processUrl'],
                    'status' => $result['status']['status']
                ];
            }
            
            return [
                'success' => false,
                'message' => $result['status']['message'] ?? 'Error al crear sesión PlaceToPay',
                'details' => $result['status'] ?? null
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Consultar estado de transacción PlaceToPay
     */
    public function consultarSesionPlaceToPay(string $requestId): array {
        $config = $this->paymentRepo->getConfiguracionPasarela('PLACETOPAY');
        
        if (!$config) {
            return ['success' => false, 'message' => 'PlaceToPay no está configurado'];
        }
        
        $baseUrl = $config['endpoint_base'];
        $login = $config['api_key'];
        $secretKey = $config['api_secret'];
        
        $nonce = uniqid();
        $seed = date('c');
        $tranKey = base64_encode(hash('sha256', $nonce . $seed . $secretKey, true));
        
        $auth = [
            'auth' => [
                'login' => $login,
                'tranKey' => $tranKey,
                'nonce' => base64_encode($nonce),
                'seed' => $seed
            ]
        ];
        
        try {
            $ch = curl_init("$baseUrl/api/session/$requestId");
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($auth));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            return [
                'success' => true,
                'status' => $result['status']['status'] ?? 'UNKNOWN',
                'reason' => $result['status']['reason'] ?? null,
                'message' => $result['status']['message'] ?? null,
                'payments' => $result['payment'] ?? [],
                'raw' => $result
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Crear intención de pago en Stripe
     */
    public function crearPaymentIntentStripe(array $data): array {
        $config = $this->paymentRepo->getConfiguracionPasarela('STRIPE');
        
        if (!$config) {
            return ['success' => false, 'message' => 'Stripe no está configurado'];
        }
        
        $secretKey = $config['private_key'];
        
        $reference = 'VENTA-' . $data['venta_id'] . '-' . time();
        
        $payload = [
            'amount' => (int) ($data['monto'] * 100), // Stripe usa centavos
            'currency' => 'cop',
            'description' => 'Pago venta #' . $data['venta_id'],
            'metadata' => [
                'venta_id' => $data['venta_id'],
                'referencia' => $reference
            ],
            'receipt_email' => $data['cliente_email']
        ];
        
        try {
            $ch = curl_init('https://api.stripe.com/v1/payment_intents');
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($payload));
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Authorization: Bearer ' . $secretKey,
                'Content-Type: application/x-www-form-urlencoded'
            ]);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            $result = json_decode($response, true);
            
            if ($httpCode === 200 && isset($result['id'])) {
                // Guardar transacción
                $transaccionId = $this->paymentRepo->crearTransaccionPasarela([
                    'venta_id' => $data['venta_id'],
                    'venta_pago_id' => $data['venta_pago_id'] ?? null,
                    'pasarela' => 'STRIPE',
                    'referencia_interna' => $reference,
                    'referencia_externa' => $result['id'],
                    'monto' => $data['monto'],
                    'cliente_email' => $data['cliente_email'],
                    'cliente_nombre' => $data['cliente_nombre'] ?? null,
                    'cliente_documento' => $data['cliente_documento'] ?? null,
                    'cliente_telefono' => $data['cliente_telefono'] ?? null
                ]);
                
                return [
                    'success' => true,
                    'transaccion_id' => $transaccionId,
                    'client_secret' => $result['client_secret'],
                    'payment_intent_id' => $result['id'],
                    'status' => $result['status']
                ];
            }
            
            return [
                'success' => false,
                'message' => $result['error']['message'] ?? 'Error al crear PaymentIntent',
                'details' => $result['error'] ?? null
            ];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Generar código QR para pagos bancarios
     */
    public function generarQRCode(array $data): array {
        $tipo = $data['tipo'] ?? 'BANCOLOMBIA';
        $monto = $data['monto'];
        $referencia = 'VENTA-' . $data['venta_id'] . '-' . time();
        
        // Obtener cuenta bancaria predeterminada
        $cuentas = $this->paymentRepo->getCuentasBancarias();
        $cuenta = $cuentas[0] ?? null;
        
        if (!$cuenta) {
            return ['success' => false, 'message' => 'No hay cuentas bancarias configuradas'];
        }
        
        // Generar datos QR según el tipo
        $qrData = $this->generarDatosQR($tipo, $monto, $referencia, $cuenta);
        
        // Guardar en BD
        $qrId = $this->paymentRepo->crearCodigoQR([
            'venta_id' => $data['venta_id'],
            'venta_pago_id' => $data['venta_pago_id'] ?? null,
            'tipo' => $tipo,
            'monto' => $monto,
            'qr_data' => $qrData,
            'referencia' => $referencia
        ]);
        
        return [
            'success' => true,
            'qr_id' => $qrId,
            'referencia' => $referencia,
            'qr_data' => $qrData,
            'monto' => $monto,
            'cuenta' => [
                'banco' => $cuenta['banco'],
                'tipo' => $cuenta['tipo_cuenta'],
                'numero' => substr($cuenta['numero_cuenta'], -4),
                'titular' => $cuenta['titular']
            ]
        ];
    }
    
    /**
     * Verificar estado de QR
     */
    public function verificarEstadoQR(int $qrId): array {
        // En producción, esto consultaría la API del banco
        // Por ahora simulamos la consulta
        
        $db = Database::getConnection();
        
        $stmt = $db->prepare("SELECT * FROM codigos_qr WHERE id = ?");
        $stmt->execute([$qrId]);
        $qr = $stmt->fetch();
        
        if (!$qr) {
            return ['success' => false, 'message' => 'QR no encontrado'];
        }
        
        // Incrementar intentos
        $db->prepare("UPDATE codigos_qr SET intentos_consulta = intentos_consulta + 1 WHERE id = ?")
           ->execute([$qrId]);
        
        return [
            'success' => true,
            'estado' => $qr['estado'],
            'referencia' => $qr['referencia'],
            'monto' => $qr['monto'],
            'fecha_expiracion' => $qr['fecha_expiracion'],
            'intentos' => $qr['intentos_consulta'] + 1
        ];
    }
    
    /**
     * Mapear método de pago a formato Wompi
     */
    private function mapPaymentMethodWompi(string $metodo): array {
        $map = [
            'TARJETA_DEBITO' => ['type' => 'CARD', ' installments' => 1],
            'TARJETA_CREDITO' => ['type' => 'CARD'],
            'QR_NEQUI' => ['type' => 'NEQUI'],
            'PSE' => ['type' => 'PSE', 'user_type' => 0, 'financial_institution_code' => '1']
        ];
        
        return $map[$metodo] ?? ['type' => 'CARD'];
    }
    
    /**
     * Generar datos de QR según especificación
     */
    private function generarDatosQR(string $tipo, float $monto, string $referencia, array $cuenta): string {
        switch ($tipo) {
            case 'BANCOLOMBIA':
            case 'NEQUI':
            case 'DAVIPLATA':
                // Formato estandarizado para apps bancarias colombianas
                $data = [
                    'banco' => $cuenta['banco'],
                    'tipo_cuenta' => $cuenta['tipo_cuenta'],
                    'numero' => $cuenta['numero_cuenta'],
                    'titular' => $cuenta['titular'],
                    'monto' => $monto,
                    'referencia' => $referencia,
                    'descripcion' => 'Pago en tienda'
                ];
                return json_encode($data);
                
            case 'PSE':
                return "PSE|{$cuenta['banco']}|{$cuenta['numero_cuenta']}|{$monto}|{$referencia}";
                
            default:
                return "{$tipo}|{$monto}|{$referencia}";
        }
    }
    
    /**
     * Procesar webhook de Wompi
     */
    public function procesarWebhookWompi(array $data): array {
        $event = $data['event'] ?? '';
        $transaction = $data['data']['transaction'] ?? null;
        
        if (!$transaction) {
            return ['success' => false, 'message' => 'Datos de transacción no encontrados'];
        }
        
        $reference = $transaction['reference'] ?? '';
        $status = $transaction['status'] ?? '';
        $transactionId = $transaction['id'] ?? '';
        
        // Buscar transacción por referencia
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM transacciones_pasarela WHERE referencia_interna = ? AND pasarela = 'WOMPI'");
        $stmt->execute([$reference]);
        $transaccion = $stmt->fetch();
        
        if (!$transaccion) {
            return ['success' => false, 'message' => 'Transacción no encontrada: ' . $reference];
        }
        
        // Actualizar estado
        $nuevoEstado = match($status) {
            'APPROVED' => 'APROBADA',
            'DECLINED' => 'RECHAZADA',
            'ERROR' => 'ERROR',
            'VOIDED' => 'CANCELADA',
            default => 'PENDIENTE'
        };
        
        $this->paymentRepo->actualizarTransaccionPasarela($transaccion['id'], [
            'estado' => $nuevoEstado,
            'referencia_externa' => $transactionId,
            'datos_respuesta' => $transaction
        ]);
        
        // Actualizar pago de venta
        if ($transaccion['venta_pago_id']) {
            $this->paymentRepo->actualizarEstadoPago(
                $transaccion['venta_pago_id'],
                $nuevoEstado === 'APROBADA' ? 'APROBADO' : ($nuevoEstado === 'RECHAZADA' ? 'RECHAZADO' : 'PENDIENTE'),
                $transactionId,
                $transaction['status_message'] ?? null
            );
        }
        
        return [
            'success' => true,
            'transaccion_id' => $transaccion['id'],
            'estado' => $nuevoEstado,
            'venta_id' => $transaccion['venta_id']
        ];
    }
    
    /**
     * Procesar webhook de PlaceToPay
     */
    public function procesarWebhookPlaceToPay(string $requestId): array {
        $consulta = $this->consultarSesionPlaceToPay($requestId);
        
        if (!$consulta['success']) {
            return $consulta;
        }
        
        // Buscar transacción
        $db = Database::getConnection();
        $stmt = $db->prepare("SELECT * FROM transacciones_pasarela WHERE referencia_externa = ? AND pasarela = 'PLACETOPAY'");
        $stmt->execute([$requestId]);
        $transaccion = $stmt->fetch();
        
        if (!$transaccion) {
            return ['success' => false, 'message' => 'Transacción no encontrada: ' . $requestId];
        }
        
        // Actualizar estado
        $status = $consulta['status'];
        $nuevoEstado = match($status) {
            'APPROVED' => 'APROBADA',
            'REJECTED' => 'RECHAZADA',
            'PENDING' => 'PENDIENTE',
            'FAILED' => 'ERROR',
            default => 'PENDIENTE'
        };
        
        $this->paymentRepo->actualizarTransaccionPasarela($transaccion['id'], [
            'estado' => $nuevoEstado,
            'datos_respuesta' => $consulta['raw']
        ]);
        
        // Actualizar pago de venta
        if ($transaccion['venta_pago_id']) {
            $this->paymentRepo->actualizarEstadoPago(
                $transaccion['venta_pago_id'],
                $nuevoEstado === 'APROBADA' ? 'APROBADO' : ($nuevoEstado === 'RECHAZADA' ? 'RECHAZADO' : 'PENDIENTE'),
                $requestId,
                $consulta['message']
            );
        }
        
        return [
            'success' => true,
            'transaccion_id' => $transaccion['id'],
            'estado' => $nuevoEstado,
            'venta_id' => $transaccion['venta_id']
        ];
    }
}
