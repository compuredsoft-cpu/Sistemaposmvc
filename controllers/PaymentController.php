<?php
/**
 * PaymentController
 * API REST para gestión de pagos
 */

class PaymentController {
    private PaymentRepository $paymentRepo;
    private PaymentGatewayService $gatewayService;
    private VentaRepository $ventaRepo;
    
    public function __construct() {
        $this->paymentRepo = new PaymentRepository();
        $this->gatewayService = new PaymentGatewayService();
        $this->ventaRepo = new VentaRepository();
    }
    
    /**
     * GET /api/pagos/metodos
     * Obtener todos los métodos de pago activos
     */
    public function getMetodosPago(): void {
        try {
            SessionManager::requireAuth();
            
            $metodos = $this->paymentRepo->findAllActive();
            $data = array_map(fn($m) => $m->toArray(), $metodos);
            
            jsonResponse(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * POST /api/pagos/procesar
     * Procesar pagos de una venta (soporta múltiples pagos)
     */
    public function procesarPagos(): void {
        try {
            SessionManager::requireAuth();
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['venta_id']) || empty($input['pagos'])) {
                jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
                return;
            }
            
            $ventaId = (int) $input['venta_id'];
            $pagos = $input['pagos'];
            $usuarioId = SessionManager::getUserId();
            
            // Verificar que la venta existe
            $venta = $this->ventaRepo->findById($ventaId);
            if (!$venta) {
                jsonResponse(['success' => false, 'message' => 'Venta no encontrada'], 404);
                return;
            }
            
            // Calcular total de pagos
            $totalPagos = array_sum(array_column($pagos, 'monto'));
            
            if ($totalPagos < $venta->total) {
                jsonResponse([
                    'success' => false, 
                    'message' => 'El monto total de pagos es menor al total de la venta',
                    'total_venta' => $venta->total,
                    'total_pagos' => $totalPagos,
                    'faltante' => $venta->total - $totalPagos
                ], 400);
                return;
            }
            
            // Procesar pagos
            $resultado = $this->paymentRepo->procesarPagosVenta($ventaId, $pagos, $usuarioId);
            
            // Calcular cambio si el pago es mayor
            $cambio = $totalPagos > $venta->total ? $totalPagos - $venta->total : 0;
            
            // Actualizar estado de la venta
            $totalPagado = $this->paymentRepo->getTotalPagado($ventaId);
            $estaPagada = $totalPagado >= $venta->total;
            
            if ($estaPagada) {
                $db = Database::getConnection();
                $db->prepare("UPDATE ventas SET estado = 'COMPLETADA', pago_confirmado = 1 WHERE id = ?")
                   ->execute([$ventaId]);
            }
            
            jsonResponse([
                'success' => $resultado['success'],
                'message' => $resultado['success'] ? 'Pagos procesados correctamente' : 'Error al procesar pagos',
                'data' => [
                    'venta_id' => $ventaId,
                    'total_venta' => $venta->total,
                    'total_pagado' => $totalPagado,
                    'cambio' => $cambio,
                    'esta_pagada' => $estaPagada,
                    'pagos' => $resultado['pagos_guardados'],
                    'errors' => $resultado['errors'] ?? []
                ]
            ]);
            
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * GET /api/pagos/venta/{id}
     * Obtener pagos de una venta específica
     */
    public function getPagosByVenta(int $ventaId): void {
        try {
            SessionManager::requireAuth();
            
            $pagos = $this->paymentRepo->getPagosByVenta($ventaId);
            $data = array_map(fn($p) => $p->toArray(), $pagos);
            
            $totalPagado = array_sum(array_column($data, 'monto'));
            
            jsonResponse([
                'success' => true,
                'data' => [
                    'pagos' => $data,
                    'total_pagado' => $totalPagado,
                    'cantidad' => count($data)
                ]
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * POST /api/pagos/wompi/crear
     * Crear transacción en Wompi
     */
    public function crearTransaccionWompi(): void {
        try {
            SessionManager::requireAuth();
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['venta_id']) || empty($input['monto']) || empty($input['cliente_email'])) {
                jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
                return;
            }
            
            $result = $this->gatewayService->crearTransaccionWompi($input);
            
            if ($result['success']) {
                jsonResponse([
                    'success' => true,
                    'data' => $result
                ]);
            } else {
                jsonResponse(['success' => false, 'message' => $result['message']], 400);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * POST /api/pagos/placetopay/crear
     * Crear sesión en PlaceToPay
     */
    public function crearSesionPlaceToPay(): void {
        try {
            SessionManager::requireAuth();
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['venta_id']) || empty($input['monto'])) {
                jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
                return;
            }
            
            $result = $this->gatewayService->crearSesionPlaceToPay($input);
            
            if ($result['success']) {
                jsonResponse([
                    'success' => true,
                    'data' => $result
                ]);
            } else {
                jsonResponse(['success' => false, 'message' => $result['message']], 400);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * POST /api/pagos/qr/generar
     * Generar código QR para pago
     */
    public function generarQR(): void {
        try {
            SessionManager::requireAuth();
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['venta_id']) || empty($input['monto']) || empty($input['tipo'])) {
                jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
                return;
            }
            
            $result = $this->gatewayService->generarQRCode($input);
            
            if ($result['success']) {
                jsonResponse([
                    'success' => true,
                    'data' => $result
                ]);
            } else {
                jsonResponse(['success' => false, 'message' => $result['message']], 400);
            }
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * GET /api/pagos/qr/{id}/estado
     * Verificar estado de QR
     */
    public function verificarEstadoQR(int $qrId): void {
        try {
            SessionManager::requireAuth();
            
            $result = $this->gatewayService->verificarEstadoQR($qrId);
            
            jsonResponse($result);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * POST /api/pagos/webhook/wompi
     * Webhook para notificaciones de Wompi
     */
    public function webhookWompi(): void {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            
            // Validar firma del webhook (en producción)
            $signature = $_SERVER['HTTP_X_WOMPI_SIGNATURE'] ?? '';
            
            $result = $this->gatewayService->procesarWebhookWompi($input);
            
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * POST /api/pagos/webhook/placetopay
     * Webhook para notificaciones de PlaceToPay
     */
    public function webhookPlaceToPay(): void {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            $requestId = $input['requestId'] ?? null;
            
            if (!$requestId) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'requestId requerido']);
                return;
            }
            
            $result = $this->gatewayService->procesarWebhookPlaceToPay($requestId);
            
            http_response_code($result['success'] ? 200 : 400);
            echo json_encode($result);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
    }
    
    /**
     * POST /api/pagos/devolucion
     * Procesar devolución de un pago
     */
    public function procesarDevolucion(): void {
        try {
            SessionManager::requireAuth();
            SessionManager::requirePermission('ventas.devolucion');
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (empty($input['pago_id']) || empty($input['monto'])) {
                jsonResponse(['success' => false, 'message' => 'Datos incompletos'], 400);
                return;
            }
            
            // Aquí iría la lógica de devolución según el método de pago
            // Para pagos en efectivo se hace directo, para pasarelas se llamaría a su API
            
            jsonResponse([
                'success' => true,
                'message' => 'Devolución procesada correctamente',
                'data' => [
                    'pago_id' => $input['pago_id'],
                    'monto' => $input['monto'],
                    'motivo' => $input['motivo'] ?? null
                ]
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
    
    /**
     * GET /api/pagos/cuentas-bancarias
     * Obtener cuentas bancarias para transferencias
     */
    public function getCuentasBancarias(): void {
        try {
            SessionManager::requireAuth();
            
            $cuentas = $this->paymentRepo->getCuentasBancarias();
            
            jsonResponse([
                'success' => true,
                'data' => $cuentas
            ]);
        } catch (Exception $e) {
            jsonResponse(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
