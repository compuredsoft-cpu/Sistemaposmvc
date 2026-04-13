-- ============================================
-- MIGRACIÓN: Sistema de Pagos Modernos
-- Fecha: 2026-04-12
-- ============================================

-- ============================================
-- 1. TABLA DE MÉTODOS DE PAGO
-- ============================================
CREATE TABLE IF NOT EXISTS metodos_pago (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    tipo ENUM('EFECTIVO', 'TARJETA', 'QR', 'TRANSFERENCIA', 'WALLETS', 'PASARELA', 'CHEQUE', 'CREDITO', 'MIXTO') NOT NULL,
    descripcion TEXT,
    imagen VARCHAR(255),
    requiere_autorizacion TINYINT DEFAULT 0,
    requiere_referencia TINYINT DEFAULT 0,
    permite_devolucion TINYINT DEFAULT 1,
    comision_porcentaje DECIMAL(5,2) DEFAULT 0,
    comision_fija DECIMAL(10,2) DEFAULT 0,
    configuracion JSON,
    orden INT DEFAULT 0,
    estado TINYINT DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar métodos de pagos modernos (IGNORAR si ya existen)
INSERT IGNORE INTO metodos_pago (codigo, nombre, tipo, descripcion, imagen, requiere_autorizacion, requiere_referencia, permite_devolucion, comision_porcentaje, comision_fija, configuracion, orden, estado) VALUES
('EFECTIVO', 'Efectivo', 'EFECTIVO', 'Pago en efectivo', 'cash.svg', 0, 0, 1, 0, 0, '{"color": "#22c55e", "icon": "bi-cash"}', 1, 1),
('TARJETA_DEBITO', 'Tarjeta Débito', 'TARJETA', 'Tarjeta débito (datáfono)', 'card.svg', 1, 1, 1, 1.5, 0, '{"color": "#3b82f6", "icon": "bi-credit-card", "tipo": "debito"}', 2, 1),
('TARJETA_CREDITO', 'Tarjeta Crédito', 'TARJETA', 'Tarjeta crédito (datáfono)', 'card.svg', 1, 1, 1, 2.5, 0, '{"color": "#6366f1", "icon": "bi-credit-card-2-front", "tipo": "credito"}', 3, 1),
('QR_BANCOLOMBIA', 'QR Bancolombia', 'QR', 'Pago QR Bancolombia', 'qr.svg', 1, 1, 1, 0, 0, '{"color": "#00447c", "icon": "bi-qr-code", "banco": "bancolombia"}', 4, 1),
('QR_NEQUI', 'QR Nequi', 'QR', 'Pago QR Nequi', 'qr.svg', 1, 1, 1, 0, 0, '{"color": "#9c27b0", "icon": "bi-qr-code", "banco": "nequi"}', 5, 1),
('QR_DAVIPLATA', 'QR Daviplata', 'QR', 'Pago QR Daviplata', 'qr.svg', 1, 1, 1, 0, 0, '{"color": "#e91e63", "icon": "bi-qr-code", "banco": "daviplata"}', 6, 1),
('QR_PSE', 'QR PSE', 'QR', 'Pago QR Bancario (PSE)', 'qr.svg', 1, 1, 1, 0, 0, '{"color": "#00447c", "icon": "bi-bank", "banco": "pse"}', 7, 1),
('TRANSFERENCIA_BANCOLOMBIA', 'Transferencia Bancolombia', 'TRANSFERENCIA', 'Transferencia bancaria Bancolombia', 'transfer.svg', 0, 1, 1, 0, 0, '{"color": "#00447c", "icon": "bi-bank", "banco": "bancolombia"}', 8, 1),
('TRANSFERENCIA_NEQUI', 'Transferencia Nequi', 'TRANSFERENCIA', 'Transferencia Nequi', 'transfer.svg', 0, 1, 1, 0, 0, '{"color": "#9c27b0", "icon": "bi-phone", "banco": "nequi"}', 9, 1),
('TRANSFERENCIA_DAVIPLATA', 'Transferencia Daviplata', 'TRANSFERENCIA', 'Transferencia Daviplata', 'transfer.svg', 0, 1, 1, 0, 0, '{"color": "#e91e63", "icon": "bi-phone", "banco": "daviplata"}', 10, 1),
('WOMPI', 'Wompi', 'PASARELA', 'Pasarela de pagos Wompi', 'wompi.svg', 1, 1, 1, 2.9, 800, '{"color": "#00d4aa", "icon": "bi-credit-card", "pasarela": "wompi"}', 11, 1),
('PLACETOPAY', 'PlaceToPay', 'PASARELA', 'Pasarela PlaceToPay', 'ptp.svg', 1, 1, 1, 2.5, 0, '{"color": "#1d4ed8", "icon": "bi-credit-card", "pasarela": "placetopay"}', 12, 1),
('STRIPE', 'Stripe', 'PASARELA', 'Pasarela Stripe', 'stripe.svg', 1, 1, 1, 2.9, 0, '{"color": "#635bff", "icon": "bi-credit-card", "pasarela": "stripe"}', 13, 1),
('CHEQUE', 'Cheque', 'CHEQUE', 'Pago con cheque', 'cheque.svg', 0, 1, 1, 0, 0, '{"color": "#f59e0b", "icon": "bi-journal-text"}', 14, 1),
('CREDITO', 'Crédito/Fiado', 'CREDITO', 'Venta a crédito', 'credit.svg', 0, 0, 0, 0, 0, '{"color": "#dc2626", "icon": "bi-calendar-check"}', 15, 1);

-- ============================================
-- 2. TABLA DE PAGOS DE VENTAS (SPLIT PAYMENTS)
-- ============================================
CREATE TABLE IF NOT EXISTS venta_pagos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venta_id INT NOT NULL,
    metodo_pago_id INT NOT NULL,
    monto DECIMAL(15,2) NOT NULL,
    monto_recibido DECIMAL(15,2) DEFAULT 0,
    cambio DECIMAL(15,2) DEFAULT 0,
    referencia VARCHAR(100),
    autorizacion VARCHAR(100),
    ultimos_digitos VARCHAR(4),
    tipo_tarjeta VARCHAR(20),
    banco_origen VARCHAR(100),
    numero_cuenta VARCHAR(50),
    titular_cuenta VARCHAR(100),
    numero_transaccion VARCHAR(100),
    estado ENUM('PENDIENTE', 'APROBADO', 'RECHAZADO', 'DEVUELTO', 'CANCELADO') DEFAULT 'PENDIENTE',
    codigo_respuesta VARCHAR(50),
    mensaje_respuesta TEXT,
    datos_adicionales JSON,
    fecha_pago TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_confirmacion TIMESTAMP NULL,
    procesado_por INT,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (metodo_pago_id) REFERENCES metodos_pago(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (procesado_por) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 3. TABLA DE TRANSACCIONES DE PASARELAS
-- ============================================
CREATE TABLE IF NOT EXISTS transacciones_pasarela (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venta_id INT NOT NULL,
    venta_pago_id INT,
    pasarela ENUM('WOMPI', 'PLACETOPAY', 'STRIPE', 'MERCADOPAGO') NOT NULL,
    referencia_interna VARCHAR(100) NOT NULL,
    referencia_externa VARCHAR(100),
    request_id VARCHAR(100),
    estado ENUM('CREADA', 'PENDIENTE', 'APROBADA', 'RECHAZADA', 'CANCELADA', 'ERROR') DEFAULT 'CREADA',
    monto DECIMAL(15,2) NOT NULL,
    moneda VARCHAR(3) DEFAULT 'COP',
    cliente_email VARCHAR(100),
    cliente_nombre VARCHAR(100),
    cliente_documento VARCHAR(20),
    cliente_telefono VARCHAR(20),
    url_checkout TEXT,
    token_tarjeta VARCHAR(255),
    datos_respuesta JSON,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (venta_pago_id) REFERENCES venta_pagos(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 4. TABLA DE CÓDIGOS QR GENERADOS
-- ============================================
CREATE TABLE IF NOT EXISTS codigos_qr (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venta_id INT NOT NULL,
    venta_pago_id INT,
    tipo ENUM('BANCOLOMBIA', 'NEQUI', 'DAVIPLATA', 'PSE') NOT NULL,
    monto DECIMAL(15,2) NOT NULL,
    qr_data TEXT NOT NULL,
    qr_imagen TEXT,
    referencia VARCHAR(100),
    estado ENUM('PENDIENTE', 'ESCANEADO', 'PAGADO', 'EXPIRADO', 'CANCELADO') DEFAULT 'PENDIENTE',
    fecha_generacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion TIMESTAMP NULL,
    fecha_pago TIMESTAMP NULL,
    intentos_consulta INT DEFAULT 0,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (venta_pago_id) REFERENCES venta_pagos(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. TABLA DE CONFIGURACIÓN DE PASARELAS
-- ============================================
CREATE TABLE IF NOT EXISTS configuracion_pasarelas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    pasarela VARCHAR(50) NOT NULL UNIQUE,
    entorno ENUM('PRUEBA', 'PRODUCCION') DEFAULT 'PRUEBA',
    public_key TEXT,
    private_key TEXT,
    api_key TEXT,
    api_secret TEXT,
    merchant_id VARCHAR(100),
    account_id VARCHAR(100),
    endpoint_base VARCHAR(255),
    webhook_secret VARCHAR(255),
    configuracion_adicional JSON,
    activo TINYINT DEFAULT 1,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración por defecto (vacía)
INSERT INTO configuracion_pasarelas (pasarela, entorno, endpoint_base) VALUES
('WOMPI', 'PRUEBA', 'https://sandbox.wompi.co/v1'),
('PLACETOPAY', 'PRUEBA', 'https://checkout-co.placetopay.dev'),
('STRIPE', 'PRUEBA', 'https://api.stripe.com/v1');

-- ============================================
-- 6. TABLA DE CUENTAS BANCARIAS PARA TRANSFERENCIAS
-- ============================================
CREATE TABLE IF NOT EXISTS cuentas_bancarias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    banco VARCHAR(100) NOT NULL,
    tipo_cuenta ENUM('AHORROS', 'CORRIENTE') NOT NULL,
    numero_cuenta VARCHAR(50) NOT NULL,
    titular VARCHAR(100) NOT NULL,
    documento_titular VARCHAR(20),
    descripcion TEXT,
    es_predeterminada TINYINT DEFAULT 0,
    qr_imagen VARCHAR(255),
    estado TINYINT DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. ÍNDICES PARA OPTIMIZACIÓN
-- ============================================
CREATE INDEX idx_venta_pagos_venta_id ON venta_pagos(venta_id);
CREATE INDEX idx_venta_pagos_metodo ON venta_pagos(metodo_pago_id);
CREATE INDEX idx_venta_pagos_estado ON venta_pagos(estado);
CREATE INDEX idx_transacciones_pasarela_referencia ON transacciones_pasarela(referencia_interna);
CREATE INDEX idx_transacciones_pasarela_estado ON transacciones_pasarela(estado);
CREATE INDEX idx_codigos_qr_venta ON codigos_qr(venta_id);
CREATE INDEX idx_codigos_qr_estado ON codigos_qr(estado);

-- ============================================
-- 8. MODIFICAR TABLA VENTAS EXISTENTE
-- ============================================
ALTER TABLE ventas 
ADD COLUMN IF NOT EXISTS total_pagado DECIMAL(15,2) DEFAULT 0 AFTER total,
ADD COLUMN IF NOT EXISTS total_pendiente DECIMAL(15,2) DEFAULT 0 AFTER total_pagado,
ADD COLUMN IF NOT EXISTS es_pago_mixto TINYINT DEFAULT 0 AFTER es_credito,
ADD COLUMN IF NOT EXISTS pago_confirmado TINYINT DEFAULT 0 AFTER es_pago_mixto;

-- ============================================
-- 9. TABLA DE DEVOLUCIONES/REMBOLSOS
-- ============================================
CREATE TABLE IF NOT EXISTS devoluciones_pagos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venta_pago_id INT NOT NULL,
    monto DECIMAL(15,2) NOT NULL,
    motivo TEXT,
    tipo_devolucion ENUM('PARCIAL', 'TOTAL') DEFAULT 'PARCIAL',
    estado ENUM('PENDIENTE', 'PROCESADA', 'RECHAZADA') DEFAULT 'PENDIENTE',
    referencia_devolucion VARCHAR(100),
    procesado_por INT,
    fecha_solicitud TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_procesamiento TIMESTAMP NULL,
    FOREIGN KEY (venta_pago_id) REFERENCES venta_pagos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (procesado_por) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. TRIGGER PARA ACTUALIZAR TOTALES DE VENTA
-- ============================================
DELIMITER //

CREATE TRIGGER IF NOT EXISTS trg_actualizar_totales_venta
AFTER INSERT ON venta_pagos
FOR EACH ROW
BEGIN
    DECLARE total_pagado DECIMAL(15,2);
    DECLARE total_venta DECIMAL(15,2);
    
    SELECT SUM(monto) INTO total_pagado 
    FROM venta_pagos 
    WHERE venta_id = NEW.venta_id AND estado = 'APROBADO';
    
    SELECT total INTO total_venta FROM ventas WHERE id = NEW.venta_id;
    
    UPDATE ventas 
    SET total_pagado = COALESCE(total_pagado, 0),
        total_pendiente = total_venta - COALESCE(total_pagado, 0)
    WHERE id = NEW.venta_id;
END//

CREATE TRIGGER IF NOT EXISTS trg_actualizar_totales_venta_update
AFTER UPDATE ON venta_pagos
FOR EACH ROW
BEGIN
    DECLARE total_pagado DECIMAL(15,2);
    DECLARE total_venta DECIMAL(15,2);
    
    SELECT SUM(monto) INTO total_pagado 
    FROM venta_pagos 
    WHERE venta_id = NEW.venta_id AND estado = 'APROBADO';
    
    SELECT total INTO total_venta FROM ventas WHERE id = NEW.venta_id;
    
    UPDATE ventas 
    SET total_pagado = COALESCE(total_pagado, 0),
        total_pendiente = total_venta - COALESCE(total_pagado, 0)
    WHERE id = NEW.venta_id;
END//

DELIMITER ;

-- ============================================
-- NOTA: Ejecutar este script en tu base de datos MySQL
-- mysql -u root -p sistema_pos < 001_add_modern_payments.sql
-- ============================================
