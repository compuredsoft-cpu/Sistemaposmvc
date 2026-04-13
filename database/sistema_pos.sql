-- ============================================
-- SISTEMA POS - BASE DE DATOS COMPLETA
-- Arquitectura MVC - PHP 8.2+
-- Colombia - IVA 19%
-- ============================================

SET FOREIGN_KEY_CHECKS = 0;
DROP DATABASE IF EXISTS sistema_pos;
CREATE DATABASE sistema_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sistema_pos;

-- ============================================
-- 1. TABLA DE CONFIGURACIÓN DEL SISTEMA
-- ============================================
CREATE TABLE configuracion (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre_empresa VARCHAR(200) NOT NULL,
    razon_social VARCHAR(200),
    nit VARCHAR(50),
    telefono VARCHAR(50),
    email VARCHAR(100),
    direccion TEXT,
    ciudad VARCHAR(100),
    pais VARCHAR(100) DEFAULT 'Colombia',
    logo VARCHAR(255),
    moneda VARCHAR(10) DEFAULT 'COP',
    impuesto_porcentaje DECIMAL(5,2) DEFAULT 19.00,
    prefijo_factura VARCHAR(10) DEFAULT 'FAC-',
    numero_factura_inicial INT DEFAULT 1,
    prefijo_cotizacion VARCHAR(10) DEFAULT 'COT-',
    numero_cotizacion_inicial INT DEFAULT 1,
    decimales INT DEFAULT 0,
    separador_decimales VARCHAR(1) DEFAULT ',',
    separador_miles VARCHAR(1) DEFAULT '.',
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    usuario_creador INT,
    usuario_actualizador INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insertar configuración por defecto
INSERT INTO configuracion (nombre_empresa, razon_social, ciudad) VALUES 
('Mi Empresa POS', 'Mi Empresa POS S.A.S.', 'Bogotá');

-- ============================================
-- 2. TABLA DE ROLES
-- ============================================
CREATE TABLE roles (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion TEXT,
    permisos JSON,
    estado TINYINT DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO roles (nombre, descripcion, permisos) VALUES
('Administrador', 'Acceso total al sistema', '["*"]'),
('Cajero', 'Gestión de ventas y caja', '["ventas","clientes","caja","cotizaciones"]'),
('Vendedor', 'Gestión de ventas y clientes', '["ventas","clientes","cotizaciones"]'),
('Almacenista', 'Gestión de inventario y productos', '["almacen","categorias","compras","proveedores","kardex"]'),
('Contador', 'Acceso a reportes y finanzas', '["gastos","cuentasxcobrar","reportes","kardex"]');

-- ============================================
-- 3. TABLA DE USUARIOS
-- ============================================
CREATE TABLE usuarios (
    id INT PRIMARY KEY AUTO_INCREMENT,
    rol_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    telefono VARCHAR(20),
    direccion TEXT,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255),
    ultimo_acceso DATETIME,
    token_recuperacion VARCHAR(255),
    token_expira DATETIME,
    estado TINYINT DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Usuario administrador por defecto (password: admin123)
-- Hash generado: password_hash('admin123', PASSWORD_BCRYPT)
INSERT INTO usuarios (rol_id, nombre, apellido, email, username, password) VALUES
(1, 'Administrador', 'Sistema', 'admin@sistemapos.com', 'admin', '$2y$10$vTtF.FEHzJNDzTaMgB1ZVuZlCzvFpzcm2MlSxqvSNf9OYF2oQYgDS');

-- ============================================
-- 4. TABLA DE CATEGORÍAS
-- ============================================
CREATE TABLE categorias (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    estado TINYINT DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    usuario_creador INT,
    usuario_actualizador INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 5. TABLA DE PROVEEDORES
-- ============================================
CREATE TABLE proveedores (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo_documento ENUM('NIT','CC','CE','PASAPORTE') DEFAULT 'NIT',
    documento VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(200) NOT NULL,
    contacto VARCHAR(100),
    telefono VARCHAR(50),
    email VARCHAR(100),
    direccion TEXT,
    ciudad VARCHAR(100),
    observaciones TEXT,
    estado TINYINT DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    usuario_creador INT,
    usuario_actualizador INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 6. TABLA DE PRODUCTOS (ALMACÉN)
-- ============================================
CREATE TABLE productos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    codigo_barras VARCHAR(100),
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT,
    categoria_id INT,
    proveedor_id INT,
    unidad_medida VARCHAR(50) DEFAULT 'UNIDAD',
    precio_costo DECIMAL(15,2) DEFAULT 0,
    precio_venta DECIMAL(15,2) DEFAULT 0,
    precio_mayorista DECIMAL(15,2) DEFAULT 0,
    stock_minimo INT DEFAULT 5,
    stock_maximo INT DEFAULT 100,
    stock_actual INT DEFAULT 0,
    ubicacion VARCHAR(100),
    imagen VARCHAR(255),
    estado TINYINT DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    usuario_creador INT,
    usuario_actualizador INT,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE SET NULL ON UPDATE CASCADE,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 7. TABLA DE CLIENTES
-- ============================================
CREATE TABLE clientes (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo_documento ENUM('CC','NIT','CE','PASAPORTE') DEFAULT 'CC',
    documento VARCHAR(50) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100),
    razon_social VARCHAR(200),
    telefono VARCHAR(50),
    email VARCHAR(100),
    direccion TEXT,
    ciudad VARCHAR(100),
    fecha_nacimiento DATE,
    limite_credito DECIMAL(15,2) DEFAULT 0,
    saldo_pendiente DECIMAL(15,2) DEFAULT 0,
    observaciones TEXT,
    estado TINYINT DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    usuario_creador INT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cliente genérico para ventas de contado
INSERT INTO clientes (tipo_documento, documento, nombre, apellido, razon_social) VALUES
('CC', '22222222', 'Cliente', 'Genérico', 'Cliente Genérico');

-- ============================================
-- 8. TABLA DE COMPRAS (CABECERA)
-- ============================================
CREATE TABLE compras (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    proveedor_id INT NOT NULL,
    fecha DATE NOT NULL,
    fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(15,2) DEFAULT 0,
    impuesto DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) DEFAULT 0,
    metodo_pago ENUM('EFECTIVO','TRANSFERENCIA','CHEQUE','CREDITO') DEFAULT 'EFECTIVO',
    estado ENUM('PENDIENTE','COMPLETADA','CANCELADA') DEFAULT 'PENDIENTE',
    observaciones TEXT,
    usuario_creador INT,
    FOREIGN KEY (proveedor_id) REFERENCES proveedores(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 9. TABLA DE DETALLE DE COMPRAS
-- ============================================
CREATE TABLE compras_detalle (
    id INT PRIMARY KEY AUTO_INCREMENT,
    compra_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(15,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (compra_id) REFERENCES compras(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 10. TABLA DE VENTAS (CABECERA)
-- ============================================
CREATE TABLE ventas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    cliente_id INT NOT NULL,
    usuario_id INT NOT NULL,
    caja_id INT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    subtotal DECIMAL(15,2) DEFAULT 0,
    impuesto_porcentaje DECIMAL(5,2) DEFAULT 19,
    impuesto DECIMAL(15,2) DEFAULT 0,
    descuento DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) DEFAULT 0,
    metodo_pago ENUM('EFECTIVO','TARJETA','TRANSFERENCIA','CHEQUE','CREDITO','MIXTO') DEFAULT 'EFECTIVO',
    estado ENUM('PENDIENTE','COMPLETADA','CANCELADA') DEFAULT 'PENDIENTE',
    observaciones TEXT,
    es_credito TINYINT DEFAULT 0,
    cuotas INT DEFAULT 1,
    valor_cuota DECIMAL(15,2) DEFAULT 0,
    saldo_pendiente DECIMAL(15,2) DEFAULT 0,
    fecha_vencimiento DATE,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 11. TABLA DE DETALLE DE VENTAS
-- ============================================
CREATE TABLE ventas_detalle (
    id INT PRIMARY KEY AUTO_INCREMENT,
    venta_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(15,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 12. TABLA DE APERTURA Y CIERRE DE CAJA
-- ============================================
CREATE TABLE cajas (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT NOT NULL,
    fecha_apertura TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_cierre TIMESTAMP NULL,
    monto_apertura DECIMAL(15,2) DEFAULT 0,
    total_ventas DECIMAL(15,2) DEFAULT 0,
    total_compras DECIMAL(15,2) DEFAULT 0,
    total_ingresos DECIMAL(15,2) DEFAULT 0,
    total_egresos DECIMAL(15,2) DEFAULT 0,
    total_efectivo DECIMAL(15,2) DEFAULT 0,
    total_tarjeta DECIMAL(15,2) DEFAULT 0,
    total_transferencia DECIMAL(15,2) DEFAULT 0,
    total_cheque DECIMAL(15,2) DEFAULT 0,
    total_credito DECIMAL(15,2) DEFAULT 0,
    monto_cierre DECIMAL(15,2) DEFAULT 0,
    diferencia DECIMAL(15,2) DEFAULT 0,
    observaciones_apertura TEXT,
    observaciones_cierre TEXT,
    estado ENUM('ABIERTA','CERRADA') DEFAULT 'ABIERTA',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Agregar foreign key a ventas después de crear cajas
ALTER TABLE ventas ADD FOREIGN KEY (caja_id) REFERENCES cajas(id) ON DELETE SET NULL ON UPDATE CASCADE;

-- ============================================
-- 13. TABLA DE CUENTAS POR COBRAR
-- ============================================
CREATE TABLE cuentas_por_cobrar (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cliente_id INT NOT NULL,
    venta_id INT,
    documento VARCHAR(50),
    monto_total DECIMAL(15,2) NOT NULL,
    monto_pagado DECIMAL(15,2) DEFAULT 0,
    monto_pendiente DECIMAL(15,2) NOT NULL,
    fecha_emision DATE NOT NULL,
    fecha_vencimiento DATE,
    plazo_dias INT DEFAULT 30,
    estado ENUM('PENDIENTE','PARCIAL','PAGADA','VENCIDA','ANULADA') DEFAULT 'PENDIENTE',
    observaciones TEXT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    usuario_creador INT,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 14. TABLA DE PAGOS A CUENTAS POR COBRAR
-- ============================================
CREATE TABLE pagos_cxc (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cuenta_cobrar_id INT NOT NULL,
    fecha_pago TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    monto DECIMAL(15,2) NOT NULL,
    metodo_pago ENUM('EFECTIVO','TARJETA','TRANSFERENCIA','CHEQUE','DEPOSITO') DEFAULT 'EFECTIVO',
    referencia VARCHAR(100),
    observaciones TEXT,
    usuario_creador INT,
    FOREIGN KEY (cuenta_cobrar_id) REFERENCES cuentas_por_cobrar(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 15. TABLA DE GASTOS Y GANANCIAS
-- ============================================
CREATE TABLE tipos_gasto (
    id INT PRIMARY KEY AUTO_INCREMENT,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    tipo ENUM('GASTO','INGRESO') DEFAULT 'GASTO',
    estado TINYINT DEFAULT 1,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO tipos_gasto (nombre, descripcion, tipo) VALUES
('Compra de Mercancía', 'Compra de productos para inventario', 'GASTO'),
('Servicios Públicos', 'Pago de servicios públicos', 'GASTO'),
('Arriendo', 'Pago de arriendo local', 'GASTO'),
('Nómina', 'Pago de sueldos y salarios', 'GASTO'),
('Transporte', 'Gastos de transporte y domicilios', 'GASTO'),
('Marketing', 'Gastos publicitarios', 'GASTO'),
('Mantenimiento', 'Reparaciones y mantenimiento', 'GASTO'),
('Otros Ingresos', 'Ingresos diversos', 'INGRESO'),
('Devoluciones', 'Devoluciones de compras', 'INGRESO');

CREATE TABLE gastos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    tipo_gasto_id INT NOT NULL,
    caja_id INT,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    concepto VARCHAR(200) NOT NULL,
    monto DECIMAL(15,2) NOT NULL,
    metodo_pago ENUM('EFECTIVO','TARJETA','TRANSFERENCIA','CHEQUE') DEFAULT 'EFECTIVO',
    referencia VARCHAR(100),
    proveedor VARCHAR(200),
    descripcion TEXT,
    tipo ENUM('GASTO','INGRESO') DEFAULT 'GASTO',
    estado TINYINT DEFAULT 1,
    usuario_creador INT,
    FOREIGN KEY (tipo_gasto_id) REFERENCES tipos_gasto(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (caja_id) REFERENCES cajas(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 16. TABLA DE KARDEX (MOVIMIENTOS DE INVENTARIO)
-- ============================================
CREATE TABLE kardex (
    id INT PRIMARY KEY AUTO_INCREMENT,
    producto_id INT NOT NULL,
    tipo_movimiento ENUM('ENTRADA','SALIDA','AJUSTE','DEVOLUCION','COMPRA','VENTA') NOT NULL,
    documento_tipo ENUM('COMPRA','VENTA','AJUSTE','INVENTARIO_INICIAL') NOT NULL,
    documento_id INT,
    documento_codigo VARCHAR(50),
    cantidad INT NOT NULL,
    stock_anterior INT NOT NULL,
    stock_nuevo INT NOT NULL,
    costo_unitario DECIMAL(15,2),
    costo_total DECIMAL(15,2),
    observaciones TEXT,
    fecha_movimiento TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    usuario_creador INT,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 17. TABLA DE COTIZACIONES (CABECERA)
-- ============================================
CREATE TABLE cotizaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo VARCHAR(50) NOT NULL UNIQUE,
    cliente_id INT NOT NULL,
    usuario_id INT NOT NULL,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_vencimiento DATE,
    subtotal DECIMAL(15,2) DEFAULT 0,
    impuesto_porcentaje DECIMAL(5,2) DEFAULT 19,
    impuesto DECIMAL(15,2) DEFAULT 0,
    descuento DECIMAL(15,2) DEFAULT 0,
    total DECIMAL(15,2) DEFAULT 0,
    estado ENUM('PENDIENTE','APROBADA','RECHAZADA','CONVERTIDA') DEFAULT 'PENDIENTE',
    observaciones TEXT,
    condiciones TEXT,
    tiempo_entrega VARCHAR(100),
    forma_pago VARCHAR(100),
    venta_id INT,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id) REFERENCES clientes(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    FOREIGN KEY (venta_id) REFERENCES ventas(id) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 18. TABLA DE DETALLE DE COTIZACIONES
-- ============================================
CREATE TABLE cotizaciones_detalle (
    id INT PRIMARY KEY AUTO_INCREMENT,
    cotizacion_id INT NOT NULL,
    producto_id INT NOT NULL,
    cantidad INT NOT NULL,
    precio_unitario DECIMAL(15,2) NOT NULL,
    subtotal DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (cotizacion_id) REFERENCES cotizaciones(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 19. TABLA DE HISTORIAL DE ACCIONES (AUDITORÍA)
-- ============================================
CREATE TABLE historial_acciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    accion VARCHAR(100) NOT NULL,
    modulo VARCHAR(50) NOT NULL,
    descripcion TEXT,
    ip_address VARCHAR(50),
    user_agent TEXT,
    datos_anteriores JSON,
    datos_nuevos JSON,
    fecha TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- 20. TABLA DE NOTIFICACIONES
-- ============================================
CREATE TABLE notificaciones (
    id INT PRIMARY KEY AUTO_INCREMENT,
    usuario_id INT,
    tipo VARCHAR(50) NOT NULL,
    titulo VARCHAR(200) NOT NULL,
    mensaje TEXT,
    referencia_id INT,
    referencia_tipo VARCHAR(50),
    leida TINYINT DEFAULT 0,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    fecha_lectura TIMESTAMP NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- ÍNDICES ADICIONALES PARA OPTIMIZACIÓN
-- ============================================
CREATE INDEX idx_productos_codigo ON productos(codigo);
CREATE INDEX idx_productos_nombre ON productos(nombre);
CREATE INDEX idx_ventas_fecha ON ventas(fecha);
CREATE INDEX idx_ventas_cliente ON ventas(cliente_id);
CREATE INDEX idx_compras_fecha ON compras(fecha);
CREATE INDEX idx_cxc_cliente ON cuentas_por_cobrar(cliente_id);
CREATE INDEX idx_cxc_estado ON cuentas_por_cobrar(estado);
CREATE INDEX idx_kardex_producto ON kardex(producto_id);
CREATE INDEX idx_gastos_fecha ON gastos(fecha);

SET FOREIGN_KEY_CHECKS = 1;
