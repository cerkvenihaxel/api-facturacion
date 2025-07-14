-- Migración preparatoria para cumplimiento ARCA 2025
-- Resolución General N° 5616
-- Fecha: 2024-12-27

-- ============================================================================
-- 1. TABLA DE CONDICIONES IVA
-- ============================================================================

CREATE TABLE IF NOT EXISTS condiciones_iva (
    id INT PRIMARY KEY AUTO_INCREMENT,
    codigo INT UNIQUE NOT NULL,
    descripcion VARCHAR(100) NOT NULL,
    descripcion_corta VARCHAR(50) NOT NULL,
    activa BOOLEAN DEFAULT TRUE,
    obligatoria_2025 BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_codigo (codigo),
    INDEX idx_activa (activa),
    INDEX idx_obligatoria_2025 (obligatoria_2025)
);

-- Insertar códigos de condiciones IVA oficiales
INSERT INTO condiciones_iva (codigo, descripcion, descripcion_corta, obligatoria_2025) VALUES
(1, 'IVA Responsable Inscripto', 'Resp. Inscripto', TRUE),
(2, 'IVA Responsable no Inscripto', 'Resp. No Inscripto', TRUE),
(3, 'IVA no Responsable', 'No Responsable', TRUE),
(4, 'IVA Sujeto Exento', 'Exento', TRUE),
(5, 'Consumidor Final', 'Consumidor Final', TRUE),
(6, 'Responsable Monotributo', 'Monotributista', TRUE),
(7, 'Sujeto no Categorizado', 'No Categorizado', TRUE),
(8, 'Proveedor del Exterior', 'Proveedor Exterior', TRUE),
(9, 'Cliente del Exterior', 'Cliente Exterior', TRUE),
(10, 'IVA Liberado – Ley Nº 19.640', 'Liberado Ley 19640', TRUE),
(11, 'IVA Responsable Inscripto – Agente de Percepción', 'Resp. Agente Perc.', TRUE),
(12, 'Pequeño Contribuyente Eventual', 'Pequeño Contrib.', TRUE),
(13, 'Monotributista Social', 'Monotrib. Social', TRUE),
(14, 'Pequeño Contribuyente Eventual Social', 'Pequeño Contrib. Soc.', TRUE)
ON DUPLICATE KEY UPDATE 
    descripcion = VALUES(descripcion),
    descripcion_corta = VALUES(descripcion_corta),
    obligatoria_2025 = VALUES(obligatoria_2025);

-- ============================================================================
-- 2. ACTUALIZACIÓN TABLA INVOICES
-- ============================================================================

-- Agregar campos para compliance ARCA 2025
ALTER TABLE invoices 
ADD COLUMN IF NOT EXISTS receptor_condicion_iva_codigo INT NULL,
ADD COLUMN IF NOT EXISTS receptor_condicion_iva_descripcion VARCHAR(100) NULL,
ADD COLUMN IF NOT EXISTS padron_validacion_realizada BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS padron_validacion_fecha TIMESTAMP NULL,
ADD COLUMN IF NOT EXISTS padron_validacion_resultado VARCHAR(50) NULL,
ADD COLUMN IF NOT EXISTS moneda_cotizacion_fecha DATE NULL,
ADD COLUMN IF NOT EXISTS moneda_cotizacion_fuente VARCHAR(50) NULL,
ADD COLUMN IF NOT EXISTS arca_2025_compliant BOOLEAN DEFAULT FALSE,
ADD COLUMN IF NOT EXISTS arca_2025_migration_date TIMESTAMP NULL;

-- Agregar índices para optimizar consultas
ALTER TABLE invoices 
ADD INDEX IF NOT EXISTS idx_receptor_condicion_iva (receptor_condicion_iva_codigo),
ADD INDEX IF NOT EXISTS idx_padron_validacion (padron_validacion_realizada),
ADD INDEX IF NOT EXISTS idx_arca_2025_compliant (arca_2025_compliant),
ADD INDEX IF NOT EXISTS idx_moneda_cotizacion_fecha (moneda_cotizacion_fecha);

-- Agregar foreign key para condiciones IVA
ALTER TABLE invoices 
ADD CONSTRAINT fk_invoices_condicion_iva 
FOREIGN KEY (receptor_condicion_iva_codigo) 
REFERENCES condiciones_iva(codigo) 
ON UPDATE CASCADE ON DELETE RESTRICT;

-- ============================================================================
-- 3. TABLA DE AUDITORÍA COMPLIANCE ARCA 2025
-- ============================================================================

CREATE TABLE IF NOT EXISTS arca_2025_audit (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    client_id VARCHAR(50) NOT NULL,
    invoice_id BIGINT NULL,
    action VARCHAR(100) NOT NULL,
    description TEXT NULL,
    old_values JSON NULL,
    new_values JSON NULL,
    compliance_status VARCHAR(50) NOT NULL,
    validation_errors JSON NULL,
    user_agent VARCHAR(255) NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_client_id (client_id),
    INDEX idx_invoice_id (invoice_id),
    INDEX idx_action (action),
    INDEX idx_compliance_status (compliance_status),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE SET NULL
);

-- ============================================================================
-- 4. TABLA DE CONFIGURACIONES POR CLIENTE ARCA 2025
-- ============================================================================

CREATE TABLE IF NOT EXISTS client_arca_2025_config (
    id INT PRIMARY KEY AUTO_INCREMENT,
    client_id VARCHAR(50) NOT NULL UNIQUE,
    migration_status ENUM('preparacion', 'testing', 'produccion') DEFAULT 'preparacion',
    validacion_iva_enabled BOOLEAN DEFAULT FALSE,
    validacion_padron_enabled BOOLEAN DEFAULT FALSE,
    moneda_extranjera_strict BOOLEAN DEFAULT FALSE,
    webservice_version VARCHAR(20) DEFAULT '1.0',
    auto_migrate_enabled BOOLEAN DEFAULT FALSE,
    compliance_deadline DATE NULL,
    notificaciones_enabled BOOLEAN DEFAULT TRUE,
    config_json JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_client_id (client_id),
    INDEX idx_migration_status (migration_status),
    INDEX idx_compliance_deadline (compliance_deadline),
    
    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE CASCADE
);

-- ============================================================================
-- 5. TABLA DE CACHE PADRÓN ARCA
-- ============================================================================

CREATE TABLE IF NOT EXISTS padron_arca_cache (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    cuit VARCHAR(11) NOT NULL,
    condicion_iva_codigo INT NULL,
    condicion_iva_descripcion VARCHAR(100) NULL,
    razon_social VARCHAR(255) NULL,
    estado VARCHAR(50) NULL,
    response_data JSON NULL,
    cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    
    UNIQUE KEY unique_cuit (cuit),
    INDEX idx_expires_at (expires_at),
    INDEX idx_condicion_iva (condicion_iva_codigo),
    
    FOREIGN KEY (condicion_iva_codigo) REFERENCES condiciones_iva(codigo) ON DELETE SET NULL
);

-- ============================================================================
-- 6. TABLA DE NOTIFICACIONES COMPLIANCE
-- ============================================================================

CREATE TABLE IF NOT EXISTS compliance_notifications (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    client_id VARCHAR(50) NOT NULL,
    type ENUM('warning', 'error', 'info', 'regulatory_update') NOT NULL,
    title VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    data JSON NULL,
    read_at TIMESTAMP NULL,
    sent_at TIMESTAMP NULL,
    expires_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_client_id (client_id),
    INDEX idx_type (type),
    INDEX idx_read_at (read_at),
    INDEX idx_expires_at (expires_at),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE CASCADE
);

-- ============================================================================
-- 7. TABLA DE MIGRACIONES ARCA 2025
-- ============================================================================

CREATE TABLE IF NOT EXISTS arca_2025_migrations (
    id BIGINT PRIMARY KEY AUTO_INCREMENT,
    client_id VARCHAR(50) NOT NULL,
    migration_type VARCHAR(100) NOT NULL,
    status ENUM('pending', 'in_progress', 'completed', 'failed', 'rolled_back') DEFAULT 'pending',
    total_records INT DEFAULT 0,
    processed_records INT DEFAULT 0,
    failed_records INT DEFAULT 0,
    start_time TIMESTAMP NULL,
    end_time TIMESTAMP NULL,
    error_details JSON NULL,
    backup_file VARCHAR(255) NULL,
    rollback_available BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_client_id (client_id),
    INDEX idx_migration_type (migration_type),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    
    FOREIGN KEY (client_id) REFERENCES clients(client_id) ON DELETE CASCADE
);

-- ============================================================================
-- 8. VISTA PARA COMPLIANCE DASHBOARD
-- ============================================================================

CREATE OR REPLACE VIEW arca_2025_compliance_status AS
SELECT 
    c.client_id,
    c.business_name,
    cc.migration_status,
    cc.compliance_deadline,
    COALESCE(total_invoices.total, 0) as total_invoices,
    COALESCE(compliant_invoices.compliant, 0) as compliant_invoices,
    CASE 
        WHEN COALESCE(total_invoices.total, 0) = 0 THEN 100
        ELSE ROUND((COALESCE(compliant_invoices.compliant, 0) * 100.0) / total_invoices.total, 2)
    END as compliance_percentage,
    cc.validacion_iva_enabled,
    cc.validacion_padron_enabled,
    cc.moneda_extranjera_strict,
    cc.webservice_version,
    cc.updated_at as config_updated_at
FROM clients c
LEFT JOIN client_arca_2025_config cc ON c.client_id = cc.client_id
LEFT JOIN (
    SELECT client_id, COUNT(*) as total 
    FROM invoices 
    WHERE created_at >= '2025-01-01' 
    GROUP BY client_id
) total_invoices ON c.client_id = total_invoices.client_id
LEFT JOIN (
    SELECT client_id, COUNT(*) as compliant 
    FROM invoices 
    WHERE created_at >= '2025-01-01' 
    AND arca_2025_compliant = TRUE
    GROUP BY client_id
) compliant_invoices ON c.client_id = compliant_invoices.client_id;

-- ============================================================================
-- 9. PROCEDIMIENTOS ALMACENADOS
-- ============================================================================

DELIMITER //

-- Procedimiento para validar compliance de una factura
CREATE OR REPLACE PROCEDURE ValidateArca2025Compliance(
    IN p_invoice_id BIGINT,
    OUT p_is_compliant BOOLEAN,
    OUT p_validation_errors JSON
)
BEGIN
    DECLARE v_receptor_condicion INT DEFAULT NULL;
    DECLARE v_moneda VARCHAR(3) DEFAULT NULL;
    DECLARE v_cotizacion_fecha DATE DEFAULT NULL;
    DECLARE v_errors JSON DEFAULT JSON_ARRAY();
    
    -- Obtener datos de la factura
    SELECT 
        receptor_condicion_iva_codigo,
        currency,
        moneda_cotizacion_fecha
    INTO v_receptor_condicion, v_moneda, v_cotizacion_fecha
    FROM invoices 
    WHERE id = p_invoice_id;
    
    -- Validar condición IVA del receptor
    IF v_receptor_condicion IS NULL THEN
        SET v_errors = JSON_ARRAY_APPEND(v_errors, '$', 'Falta condición IVA del receptor');
    END IF;
    
    -- Validar moneda extranjera
    IF v_moneda != 'ARS' AND v_cotizacion_fecha IS NULL THEN
        SET v_errors = JSON_ARRAY_APPEND(v_errors, '$', 'Falta fecha de cotización para moneda extranjera');
    END IF;
    
    -- Determinar compliance
    SET p_is_compliant = (JSON_LENGTH(v_errors) = 0);
    SET p_validation_errors = v_errors;
    
    -- Actualizar estado en la factura
    UPDATE invoices 
    SET arca_2025_compliant = p_is_compliant,
        arca_2025_migration_date = CURRENT_TIMESTAMP
    WHERE id = p_invoice_id;
    
END //

-- Procedimiento para migrar datos legacy
CREATE OR REPLACE PROCEDURE MigrateClientToArca2025(
    IN p_client_id VARCHAR(50),
    IN p_batch_size INT DEFAULT 1000
)
BEGIN
    DECLARE v_total_records INT DEFAULT 0;
    DECLARE v_processed INT DEFAULT 0;
    DECLARE v_migration_id BIGINT;
    
    -- Crear registro de migración
    INSERT INTO arca_2025_migrations (client_id, migration_type, status, total_records)
    SELECT p_client_id, 'legacy_data_migration', 'in_progress', COUNT(*)
    FROM invoices WHERE client_id = p_client_id AND arca_2025_compliant = FALSE;
    
    SET v_migration_id = LAST_INSERT_ID();
    
    -- Procesar facturas en lotes
    migration_loop: LOOP
        UPDATE invoices 
        SET receptor_condicion_iva_codigo = 5, -- Consumidor Final por defecto
            receptor_condicion_iva_descripcion = 'Consumidor Final',
            arca_2025_compliant = TRUE,
            arca_2025_migration_date = CURRENT_TIMESTAMP
        WHERE client_id = p_client_id 
        AND arca_2025_compliant = FALSE
        LIMIT p_batch_size;
        
        SET v_processed = v_processed + ROW_COUNT();
        
        -- Actualizar progreso
        UPDATE arca_2025_migrations 
        SET processed_records = v_processed,
            updated_at = CURRENT_TIMESTAMP
        WHERE id = v_migration_id;
        
        -- Salir si no hay más registros
        IF ROW_COUNT() = 0 THEN
            LEAVE migration_loop;
        END IF;
    END LOOP;
    
    -- Marcar migración como completada
    UPDATE arca_2025_migrations 
    SET status = 'completed',
        end_time = CURRENT_TIMESTAMP
    WHERE id = v_migration_id;
    
END //

DELIMITER ;

-- ============================================================================
-- 10. CONFIGURACIÓN INICIAL POR DEFECTO
-- ============================================================================

-- Insertar configuración por defecto para clientes existentes
INSERT INTO client_arca_2025_config (client_id, migration_status)
SELECT client_id, 'preparacion'
FROM clients 
WHERE client_id NOT IN (SELECT client_id FROM client_arca_2025_config);

-- ============================================================================
-- 11. TRIGGERS PARA AUDITORÍA AUTOMÁTICA
-- ============================================================================

DELIMITER //

CREATE OR REPLACE TRIGGER audit_invoice_arca_2025_changes
AFTER UPDATE ON invoices
FOR EACH ROW
BEGIN
    IF (OLD.receptor_condicion_iva_codigo != NEW.receptor_condicion_iva_codigo OR
        OLD.arca_2025_compliant != NEW.arca_2025_compliant) THEN
        
        INSERT INTO arca_2025_audit (
            client_id, 
            invoice_id, 
            action, 
            description,
            old_values,
            new_values,
            compliance_status
        ) VALUES (
            NEW.client_id,
            NEW.id,
            'invoice_compliance_update',
            'Actualización de compliance ARCA 2025',
            JSON_OBJECT(
                'receptor_condicion_iva_codigo', OLD.receptor_condicion_iva_codigo,
                'arca_2025_compliant', OLD.arca_2025_compliant
            ),
            JSON_OBJECT(
                'receptor_condicion_iva_codigo', NEW.receptor_condicion_iva_codigo,
                'arca_2025_compliant', NEW.arca_2025_compliant
            ),
            CASE WHEN NEW.arca_2025_compliant THEN 'compliant' ELSE 'non_compliant' END
        );
    END IF;
END //

DELIMITER ;

-- ============================================================================
-- COMENTARIOS FINALES
-- ============================================================================

-- Esta migración prepara la base de datos para los cambios de ARCA 2025
-- sin afectar la funcionalidad actual. Los campos están marcados como NULL
-- y las validaciones están deshabilitadas por defecto.
-- 
-- Para activar el cumplimiento ARCA 2025:
-- 1. Actualizar client_arca_2025_config para cada cliente
-- 2. Ejecutar migraciones de datos legacy
-- 3. Activar validaciones en la configuración de la aplicación
-- 4. Monitorear compliance através de la vista arca_2025_compliance_status 