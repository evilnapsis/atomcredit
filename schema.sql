-- =============================================================================
-- ATOMCREDIT 2 - Sistema de Préstamos y Microcréditos
-- Arquitectura: Legobox 5 (PHP 8.2+, MySQL 8.0+ / MariaDB, Twig 3, FastRoute)
-- Autor del Framework: Evilnapsis
-- Base de Datos Canónica (schema.sql)
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `atomcredit2` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `atomcredit2`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `audit`;
DROP TABLE IF EXISTS `collection`;
DROP TABLE IF EXISTS `payment`;
DROP TABLE IF EXISTS `payment_method`;
DROP TABLE IF EXISTS `loan_schedule`;
DROP TABLE IF EXISTS `loan`;
DROP TABLE IF EXISTS `loan_product`;
DROP TABLE IF EXISTS `client`;
DROP TABLE IF EXISTS `permission`;
DROP TABLE IF EXISTS `user`;
DROP TABLE IF EXISTS `rol`;
DROP TABLE IF EXISTS `setting`;
SET FOREIGN_KEY_CHECKS = 1;

-- -----------------------------------------------------------------------------
-- TABLA: setting
-- PROPÓSITO: Almacena parámetros globales de configuración de la empresa y del cálculo de créditos y mora.
-- RELACIONES: Entidad maestra sin dependencias foráneas.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `setting` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) NOT NULL UNIQUE,
    `setting_value` LONGTEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- TABLA: rol
-- PROPÓSITO: Define los roles de perfil y acceso para los usuarios del sistema.
-- RELACIONES:
--   - Vinculado a `user` (1:N)
--   - Vinculado a `permission` (1:N)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `rol` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- TABLA: user
-- PROPÓSITO: Registra el personal operativo, supervisores y administradores de la plataforma.
-- RELACIONES:
--   - rol_id -> Perfil de usuario (rol.id) [1:N]
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `user` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `name` VARCHAR(100) NOT NULL,
    `lastname` VARCHAR(100) NOT NULL,
    `email` VARCHAR(255) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `rol_id` INT UNSIGNED NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_user_rol` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- TABLA: permission
-- PROPÓSITO: Matriz de permisos detallados por vista/módulo y rol.
-- RELACIONES:
--   - rol_id -> Rol al que aplican los permisos (rol.id) [1:N]
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `permission` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `rol_id` INT UNSIGNED NOT NULL,
    `view_name` VARCHAR(100) NOT NULL,
    `can_view` TINYINT(1) NOT NULL DEFAULT 1,
    `can_add` TINYINT(1) NOT NULL DEFAULT 0,
    `can_edit` TINYINT(1) NOT NULL DEFAULT 0,
    `can_delete` TINYINT(1) NOT NULL DEFAULT 0,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_permission_rol` FOREIGN KEY (`rol_id`) REFERENCES `rol` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- TABLA: client
-- PROPÓSITO: Registro de prestatarios y solicitantes de créditos personales y comerciales.
-- RELACIONES:
--   - Vinculado a `loan` (1:N) como prestatario titular.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `client` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `lastname` VARCHAR(100) NOT NULL,
    `document` VARCHAR(50) NULL,
    `phone` VARCHAR(30) NULL,
    `email` VARCHAR(255) NULL,
    `address` TEXT NULL,
    `occupation` VARCHAR(100) NULL,
    `employer` VARCHAR(100) NULL,
    `monthly_income` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- TABLA: loan_product
-- PROPÓSITO: Define las líneas y modalidades de préstamo ofrecidas por la entidad.
-- RELACIONES:
--   - Vinculado a `loan` (1:N)
--
-- CAMPOS CLAVE:
--   - interest_period: 1=Mensual, 2=Anual
--   - frequency: 1=Diario, 2=Semanal, 3=Quincenal, 4=Mensual
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `loan_product` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `description` TEXT NULL,
    `interest_rate` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `interest_period` TINYINT UNSIGNED NOT NULL DEFAULT 1 COMMENT '1=Mensual, 2=Anual',
    `min_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `max_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `min_term` INT UNSIGNED NOT NULL DEFAULT 1,
    `max_term` INT UNSIGNED NOT NULL DEFAULT 12,
    `frequency` TINYINT UNSIGNED NOT NULL DEFAULT 4 COMMENT '1=Diario, 2=Semanal, 3=Quincenal, 4=Mensual',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- TABLA: loan
-- PROPÓSITO: Registro central de cada operación de crédito otorgada.
-- RELACIONES:
--   - client_id       -> Prestatario titular (client.id) [1:N]
--   - loan_product_id -> Línea de crédito aplicada (loan_product.id) [1:N]
--   - user_id         -> Agente/Oficial que originó el crédito (user.id) [1:N]
--
-- status: Estado del ciclo operativo del crédito:
--   * 'pending'   : Solicitud ingresada, pendiente de revisión o desembolso.
--   * 'active'    : Préstamo desembolsado y vigente en plan de pagos.
--   * 'paid'      : Préstamo liquidado en su totalidad.
--   * 'overdue'   : Préstamo con al menos una cuota en mora vencida.
--   * 'cancelled' : Crédito anulado o cancelado antes del cobro.
--
-- frequency: Frecuencia de pago pactada:
--   * 1 = Diario
--   * 2 = Semanal
--   * 3 = Quincenal
--   * 4 = Mensual
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `loan` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `client_id` INT UNSIGNED NOT NULL,
    `loan_product_id` INT UNSIGNED NOT NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `interest_rate` DECIMAL(5,2) NOT NULL,
    `term` INT UNSIGNED NOT NULL,
    `frequency` TINYINT UNSIGNED NOT NULL DEFAULT 4 COMMENT '1=Diario, 2=Semanal, 3=Quincenal, 4=Mensual',
    `installment_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_interest` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `total_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `disbursement_date` DATE NOT NULL,
    `start_date` DATE NOT NULL,
    `end_date` DATE NOT NULL,
    `status` ENUM('pending', 'active', 'paid', 'overdue', 'cancelled') NOT NULL DEFAULT 'active',
    `notes` TEXT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_loan_client` FOREIGN KEY (`client_id`) REFERENCES `client` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `fk_loan_product` FOREIGN KEY (`loan_product_id`) REFERENCES `loan_product` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `fk_loan_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- TABLA: loan_schedule
-- PROPÓSITO: Tabla de amortización detallada (cuotas programadas por cada préstamo).
-- RELACIONES:
--   - loan_id -> Crédito al que pertenece la cuota (loan.id) [1:N]
--
-- status: Estado de la cuota individual:
--   * 'pending' : Cuota pendiente de vencimiento futuro sin pagos.
--   * 'partial' : Cuota con abono parcial pero saldo pendiente.
--   * 'paid'    : Cuota liquidada en su totalidad.
--   * 'overdue' : Cuota cuya fecha de vencimiento ya expiró y sigue sin cubrirse.
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `loan_schedule` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `loan_id` INT UNSIGNED NOT NULL,
    `installment_number` INT UNSIGNED NOT NULL,
    `due_date` DATE NOT NULL,
    `principal` DECIMAL(12,2) NOT NULL,
    `interest` DECIMAL(12,2) NOT NULL,
    `amount` DECIMAL(12,2) NOT NULL,
    `paid_amount` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `balance` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `status` ENUM('pending', 'partial', 'paid', 'overdue') NOT NULL DEFAULT 'pending',
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT `fk_schedule_loan` FOREIGN KEY (`loan_id`) REFERENCES `loan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- TABLA: payment_method
-- PROPÓSITO: Formas y medios de pago recibidos en ventanilla o transferencia.
-- RELACIONES:
--   - Vinculado a `payment` (1:N)
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment_method` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- TABLA: payment
-- PROPÓSITO: Transacciones de pago y abonos recibidos aplicados a un crédito.
-- RELACIONES:
--   - loan_id           -> Préstamo amortizado (loan.id) [1:N]
--   - loan_schedule_id  -> Cuota específica abonada o NULL (loan_schedule.id) [0..1:N]
--   - user_id           -> Oficial de cobranza / cajero receptor (user.id) [1:N]
--   - payment_method_id -> Medio de cobro utilizado (payment_method.id) [1:N]
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `payment` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `loan_id` INT UNSIGNED NOT NULL,
    `loan_schedule_id` INT UNSIGNED NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `payment_method_id` INT UNSIGNED NOT NULL DEFAULT 1,
    `amount` DECIMAL(12,2) NOT NULL,
    `late_fee` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_payment_loan` FOREIGN KEY (`loan_id`) REFERENCES `loan` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `fk_payment_schedule` FOREIGN KEY (`loan_schedule_id`) REFERENCES `loan_schedule` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_payment_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE,
    CONSTRAINT `fk_payment_method` FOREIGN KEY (`payment_method_id`) REFERENCES `payment_method` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- TABLA: collection
-- PROPÓSITO: Bitácora de seguimiento de gestiones de cobranza para cuentas en mora.
-- RELACIONES:
--   - loan_id          -> Préstamo gestionado (loan.id) [1:N]
--   - loan_schedule_id -> Cuota vencida involucrada (loan_schedule.id) [0..1:N]
--   - user_id          -> Agente gestor (user.id) [1:N]
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `collection` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `loan_id` INT UNSIGNED NOT NULL,
    `loan_schedule_id` INT UNSIGNED NULL,
    `user_id` INT UNSIGNED NOT NULL,
    `action_type` VARCHAR(50) NOT NULL COMMENT 'call, visit, notice, legal',
    `result` TEXT NULL,
    `next_action_date` DATE NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_collection_loan` FOREIGN KEY (`loan_id`) REFERENCES `loan` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_collection_schedule` FOREIGN KEY (`loan_schedule_id`) REFERENCES `loan_schedule` (`id`) ON DELETE SET NULL ON UPDATE CASCADE,
    CONSTRAINT `fk_collection_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- TABLA: audit
-- PROPÓSITO: Pista de auditoría para trazabilidad de eventos críticos en el sistema.
-- RELACIONES:
--   - user_id -> Usuario que efectuó la acción (user.id) [1:N]
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `audit` (
    `id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NULL,
    `action` VARCHAR(50) NOT NULL,
    `table_name` VARCHAR(100) NOT NULL,
    `record_id` INT UNSIGNED NULL,
    `description` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `user` (`id`) ON DELETE SET NULL ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- DATOS SEMILLA BÁSICOS (SEED DATA)
-- =============================================================================

-- 1. Roles del Sistema
INSERT INTO `rol` (`id`, `name`) VALUES
(1, 'Admin'),
(2, 'Supervisor'),
(3, 'Agente');

-- 2. Usuario Administrador Inicial (contraseña: admin)
-- Hash compatible con sha1(md5('admin')) = '90b9aa7e25f80cf4f64e990b78a9fc5ebd6cecad'
INSERT INTO `user` (`id`, `username`, `name`, `lastname`, `email`, `password`, `is_active`, `rol_id`, `created_at`) VALUES
(1, 'admin', 'Administrador', 'General', 'admin@atomcredit.com', '90b9aa7e25f80cf4f64e990b78a9fc5ebd6cecad', 1, 1, NOW());

-- 3. Métodos de Pago
INSERT INTO `payment_method` (`id`, `name`) VALUES
(1, 'Efectivo'),
(2, 'Transferencia'),
(3, 'Cheque');

-- 4. Parámetros de Configuración
INSERT INTO `setting` (`setting_key`, `setting_value`) VALUES
('company_name', 'AtomCredit'),
('company_email', 'contacto@atomcredit.com'),
('company_phone', '555-0192'),
('currency', 'Q'),
('late_fee_rate', '2.00'),
('late_fee_grace_days', '3');

-- 5. Productos de Préstamo por Defecto
INSERT INTO `loan_product` (`id`, `name`, `description`, `interest_rate`, `interest_period`, `min_amount`, `max_amount`, `min_term`, `max_term`, `frequency`, `is_active`) VALUES
(1, 'Préstamo Personal', 'Préstamo de consumo personal para gastos inmediatos', 5.00, 1, 500.00, 15000.00, 1, 12, 4, 1),
(2, 'Préstamo Comercial', 'Capital de trabajo para pequeños negocios y emprendimientos', 4.00, 1, 1000.00, 50000.00, 3, 24, 4, 1),
(3, 'Préstamo Rápido', 'Microcrédito de corto plazo con liquidación semanal', 8.00, 1, 100.00, 3000.00, 1, 8, 2, 1);
