-- =============================================================================
-- ATOMCREDIT 2 - Datos Demo Realistas (demo_data.sql)
-- Cumplimiento de AGENTS.md (Sección 4.5)
-- Fechas relativas en los últimos 30 días para alimentar KPIs y gráficos
-- =============================================================================

USE `atomcredit2`;

-- -----------------------------------------------------------------------------
-- 1. Catálogo de Clientes (Prestatarios)
-- -----------------------------------------------------------------------------
INSERT INTO `client` (`id`, `name`, `lastname`, `document`, `phone`, `email`, `address`, `occupation`, `employer`, `monthly_income`, `is_active`, `created_at`) VALUES
(1, 'Carlos', 'Gómez Morales', '45829103', '5551-2301', 'carlos.gomez@gmail.com', 'Av. Las Palmas 12-45 Zona 1', 'Comerciante', 'Abarrotes Don Carlos', 6500.00, 1, DATE_SUB(NOW(), INTERVAL 45 DAY)),
(2, 'María Elena', 'López Rivera', '23940182', '5552-4412', 'maria.lopez@yahoo.com', 'Calle Central 5-10 Zona 4', 'Contadora', 'Servicios Integrales S.A.', 9200.00, 1, DATE_SUB(NOW(), INTERVAL 40 DAY)),
(3, 'Juan Pablo', 'Martínez Solís', '19283746', '5553-9081', 'juan.martinez@outlook.com', 'Boulevard Principal 8-22 Zona 10', 'Ingeniero Civil', 'Constructora El Bosque', 14500.00, 1, DATE_SUB(NOW(), INTERVAL 35 DAY)),
(4, 'Ana Lucía', 'Torres Estrada', '58473920', '5554-1290', 'ana.torres@gmail.com', 'Colonia Los Álamos Calle 3', 'Docente', 'Colegio Cervantes', 7200.00, 1, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(5, 'Roberto', 'Hernández Paz', '38492019', '5555-8734', 'roberto.hdez@hotmail.com', 'Barrio El Rosario Casa 14', 'Mecánico', 'Taller Automotriz Paz', 5800.00, 1, DATE_SUB(NOW(), INTERVAL 28 DAY)),
(6, 'Sofía', 'Ramírez Castro', '67192834', '5556-3456', 'sofia.ramirez@gmail.com', 'Residenciales Las Rosas Lote 19', 'Enfermera', 'Hospital Metropolitano', 8400.00, 1, DATE_SUB(NOW(), INTERVAL 25 DAY)),
(7, 'Fernando', 'Castillo Villeda', '82910384', '5557-6543', 'fernando.castillo@gmail.com', 'Av. Bolívar 23-89 Zona 3', 'Transportista', 'Transportes del Sur', 11000.00, 1, DATE_SUB(NOW(), INTERVAL 22 DAY)),
(8, 'Patricia', 'Morales Méndez', '93847261', '5558-7654', 'patricia.morales@outlook.com', 'Calle Las Margaritas 4-15', 'Chef Repostera', 'Pastelería Delicias', 6100.00, 1, DATE_SUB(NOW(), INTERVAL 20 DAY)),
(9, 'Diego', 'Aguilar Santos', '56473829', '5559-8765', 'diego.aguilar@gmail.com', 'Condominio La Floresta 12-B', 'Diseñador Gráfico', 'Agencia Creativa Zenith', 7800.00, 1, DATE_SUB(NOW(), INTERVAL 18 DAY)),
(10, 'Valeria', 'Cruz Orellana', '74839201', '5550-9876', 'valeria.cruz@hotmail.com', 'Colonia Primavera Calle Real 8', 'Asesora de Ventas', 'Distribuidora Mayorista', 6900.00, 1, DATE_SUB(NOW(), INTERVAL 15 DAY));

-- -----------------------------------------------------------------------------
-- 2. Productos de Préstamo Adicionales
-- -----------------------------------------------------------------------------
INSERT INTO `loan_product` (`id`, `name`, `description`, `interest_rate`, `interest_period`, `min_amount`, `max_amount`, `min_term`, `max_term`, `frequency`, `is_active`, `created_at`) VALUES
(4, 'Préstamo Quincenal PYME', 'Crédito productivo para abastecimiento de inventarios', 3.50, 1, 2000.00, 25000.00, 2, 12, 3, 1, DATE_SUB(NOW(), INTERVAL 30 DAY)),
(5, 'Microcrédito Diario Express', 'Financiamiento directo para comerciantes de mercado', 5.00, 1, 300.00, 1500.00, 10, 30, 1, 1, DATE_SUB(NOW(), INTERVAL 30 DAY));

-- -----------------------------------------------------------------------------
-- 3. Préstamos Otorgados (Transaccionales en los últimos 30 días)
-- -----------------------------------------------------------------------------
-- Préstamo 1: Activo mensual (Carlos Gómez)
INSERT INTO `loan` (`id`, `client_id`, `loan_product_id`, `user_id`, `amount`, `interest_rate`, `term`, `frequency`, `installment_amount`, `total_interest`, `total_amount`, `disbursement_date`, `start_date`, `end_date`, `status`, `notes`, `created_at`) VALUES
(1, 1, 1, 1, 5000.00, 5.00, 6, 4, 1083.33, 1500.00, 6500.00, DATE(DATE_SUB(NOW(), INTERVAL 28 DAY)), DATE(DATE_SUB(NOW(), INTERVAL 28 DAY)), DATE(DATE_ADD(NOW(), INTERVAL 152 DAY)), 'active', 'Compra de mercadería para tienda', DATE_SUB(NOW(), INTERVAL 28 DAY)),

-- Préstamo 2: Activo mensual (María Elena López)
(2, 2, 2, 1, 15000.00, 4.00, 12, 4, 1750.00, 7200.00, 22200.00, DATE(DATE_SUB(NOW(), INTERVAL 25 DAY)), DATE(DATE_SUB(NOW(), INTERVAL 25 DAY)), DATE(DATE_ADD(NOW(), INTERVAL 335 DAY)), 'active', 'Expansión de oficina contable', DATE_SUB(NOW(), INTERVAL 25 DAY)),

-- Préstamo 3: Pagado (Juan Pablo Martínez)
(3, 3, 3, 1, 1500.00, 8.00, 4, 2, 495.00, 480.00, 1980.00, DATE(DATE_SUB(NOW(), INTERVAL 29 DAY)), DATE(DATE_SUB(NOW(), INTERVAL 29 DAY)), DATE(DATE_SUB(NOW(), INTERVAL 1 DAY)), 'paid', 'Compra de herramienta menor', DATE_SUB(NOW(), INTERVAL 29 DAY)),

-- Préstamo 4: En Mora (Roberto Hernández)
(4, 5, 1, 1, 3000.00, 5.00, 4, 4, 900.00, 600.00, 3600.00, DATE(DATE_SUB(NOW(), INTERVAL 45 DAY)), DATE(DATE_SUB(NOW(), INTERVAL 45 DAY)), DATE(DATE_ADD(NOW(), INTERVAL 75 DAY)), 'overdue', 'Reparación de elevador hidráulico', DATE_SUB(NOW(), INTERVAL 45 DAY)),

-- Préstamo 5: Activo quincenal (Ana Lucía Torres)
(5, 4, 4, 1, 4000.00, 3.50, 6, 3, 803.33, 840.00, 4840.00, DATE(DATE_SUB(NOW(), INTERVAL 18 DAY)), DATE(DATE_SUB(NOW(), INTERVAL 18 DAY)), DATE(DATE_ADD(NOW(), INTERVAL 72 DAY)), 'active', 'Cursos de especialización y posgrado', DATE_SUB(NOW(), INTERVAL 18 DAY)),

-- Préstamo 6: Activo mensual (Sofía Ramírez)
(6, 6, 1, 1, 6000.00, 5.00, 6, 4, 1300.00, 1800.00, 7800.00, DATE(DATE_SUB(NOW(), INTERVAL 14 DAY)), DATE(DATE_SUB(NOW(), INTERVAL 14 DAY)), DATE(DATE_ADD(NOW(), INTERVAL 166 DAY)), 'active', 'Gastos médicos familiares', DATE_SUB(NOW(), INTERVAL 14 DAY)),

-- Préstamo 7: Activo comercial (Fernando Castillo)
(7, 7, 2, 1, 20000.00, 4.00, 10, 4, 2800.00, 8000.00, 28000.00, DATE(DATE_SUB(NOW(), INTERVAL 10 DAY)), DATE(DATE_SUB(NOW(), INTERVAL 10 DAY)), DATE(DATE_ADD(NOW(), INTERVAL 290 DAY)), 'active', 'Reparación mayor de motor diésel', DATE_SUB(NOW(), INTERVAL 10 DAY)),

-- Préstamo 8: Activo rápido (Patricia Morales)
(8, 8, 3, 1, 2000.00, 8.00, 4, 2, 660.00, 640.00, 2640.00, DATE(DATE_SUB(NOW(), INTERVAL 7 DAY)), DATE(DATE_SUB(NOW(), INTERVAL 7 DAY)), DATE(DATE_ADD(NOW(), INTERVAL 21 DAY)), 'active', 'Insumos para temporada alta', DATE_SUB(NOW(), INTERVAL 7 DAY)),

-- Préstamo 9: En Mora (Diego Aguilar)
(9, 9, 1, 1, 4500.00, 5.00, 4, 4, 1350.00, 900.00, 5400.00, DATE(DATE_SUB(NOW(), INTERVAL 38 DAY)), DATE(DATE_SUB(NOW(), INTERVAL 38 DAY)), DATE(DATE_ADD(NOW(), INTERVAL 82 DAY)), 'overdue', 'Renovación de equipo informático', DATE_SUB(NOW(), INTERVAL 38 DAY)),

-- Préstamo 10: Activo reciente (Valeria Cruz)
(10, 10, 1, 1, 3500.00, 5.00, 5, 4, 875.00, 875.00, 4375.00, DATE(DATE_SUB(NOW(), INTERVAL 2 DAY)), DATE(DATE_SUB(NOW(), INTERVAL 2 DAY)), DATE(DATE_ADD(NOW(), INTERVAL 148 DAY)), 'active', 'Imprevistos personales', DATE_SUB(NOW(), INTERVAL 2 DAY));

-- -----------------------------------------------------------------------------
-- 4. Tablas de Amortización (Cuotas Programadas)
-- -----------------------------------------------------------------------------
-- Cuotas Préstamo 1 (Carlos Gómez, 6 cuotas)
INSERT INTO `loan_schedule` (`loan_id`, `installment_number`, `due_date`, `principal`, `interest`, `amount`, `paid_amount`, `balance`, `status`, `created_at`) VALUES
(1, 1, DATE(DATE_SUB(NOW(), INTERVAL 2 DAY)), 833.33, 250.00, 1083.33, 1083.33, 0.00, 'paid', DATE_SUB(NOW(), INTERVAL 28 DAY)),
(1, 2, DATE(DATE_ADD(NOW(), INTERVAL 28 DAY)), 833.33, 250.00, 1083.33, 0.00, 1083.33, 'pending', DATE_SUB(NOW(), INTERVAL 28 DAY)),
(1, 3, DATE(DATE_ADD(NOW(), INTERVAL 58 DAY)), 833.33, 250.00, 1083.33, 0.00, 1083.33, 'pending', DATE_SUB(NOW(), INTERVAL 28 DAY)),
(1, 4, DATE(DATE_ADD(NOW(), INTERVAL 88 DAY)), 833.33, 250.00, 1083.33, 0.00, 1083.33, 'pending', DATE_SUB(NOW(), INTERVAL 28 DAY)),
(1, 5, DATE(DATE_ADD(NOW(), INTERVAL 118 DAY)), 833.33, 250.00, 1083.33, 0.00, 1083.33, 'pending', DATE_SUB(NOW(), INTERVAL 28 DAY)),
(1, 6, DATE(DATE_ADD(NOW(), INTERVAL 148 DAY)), 833.35, 250.00, 1083.35, 0.00, 1083.35, 'pending', DATE_SUB(NOW(), INTERVAL 28 DAY));

-- Cuotas Préstamo 3 (Juan Pablo Martínez, 4 cuotas semanales liquidadas)
INSERT INTO `loan_schedule` (`loan_id`, `installment_number`, `due_date`, `principal`, `interest`, `amount`, `paid_amount`, `balance`, `status`, `created_at`) VALUES
(3, 1, DATE(DATE_SUB(NOW(), INTERVAL 22 DAY)), 375.00, 120.00, 495.00, 495.00, 0.00, 'paid', DATE_SUB(NOW(), INTERVAL 29 DAY)),
(3, 2, DATE(DATE_SUB(NOW(), INTERVAL 15 DAY)), 375.00, 120.00, 495.00, 495.00, 0.00, 'paid', DATE_SUB(NOW(), INTERVAL 29 DAY)),
(3, 3, DATE(DATE_SUB(NOW(), INTERVAL 8 DAY)), 375.00, 120.00, 495.00, 495.00, 0.00, 'paid', DATE_SUB(NOW(), INTERVAL 29 DAY)),
(3, 4, DATE(DATE_SUB(NOW(), INTERVAL 1 DAY)), 375.00, 120.00, 495.00, 495.00, 0.00, 'paid', DATE_SUB(NOW(), INTERVAL 29 DAY));

-- Cuotas Préstamo 4 (Roberto Hernández, En Mora)
INSERT INTO `loan_schedule` (`loan_id`, `installment_number`, `due_date`, `principal`, `interest`, `amount`, `paid_amount`, `balance`, `status`, `created_at`) VALUES
(4, 1, DATE(DATE_SUB(NOW(), INTERVAL 15 DAY)), 750.00, 150.00, 900.00, 400.00, 500.00, 'overdue', DATE_SUB(NOW(), INTERVAL 45 DAY)),
(4, 2, DATE(DATE_ADD(NOW(), INTERVAL 15 DAY)), 750.00, 150.00, 900.00, 0.00, 900.00, 'pending', DATE_SUB(NOW(), INTERVAL 45 DAY)),
(4, 3, DATE(DATE_ADD(NOW(), INTERVAL 45 DAY)), 750.00, 150.00, 900.00, 0.00, 900.00, 'pending', DATE_SUB(NOW(), INTERVAL 45 DAY)),
(4, 4, DATE(DATE_ADD(NOW(), INTERVAL 75 DAY)), 750.00, 150.00, 900.00, 0.00, 900.00, 'pending', DATE_SUB(NOW(), INTERVAL 45 DAY));

-- Cuotas Préstamo 9 (Diego Aguilar, En Mora)
INSERT INTO `loan_schedule` (`loan_id`, `installment_number`, `due_date`, `principal`, `interest`, `amount`, `paid_amount`, `balance`, `status`, `created_at`) VALUES
(9, 1, DATE(DATE_SUB(NOW(), INTERVAL 8 DAY)), 1125.00, 225.00, 1350.00, 0.00, 1350.00, 'overdue', DATE_SUB(NOW(), INTERVAL 38 DAY)),
(9, 2, DATE(DATE_ADD(NOW(), INTERVAL 22 DAY)), 1125.00, 225.00, 1350.00, 0.00, 1350.00, 'pending', DATE_SUB(NOW(), INTERVAL 38 DAY)),
(9, 3, DATE(DATE_ADD(NOW(), INTERVAL 52 DAY)), 1125.00, 225.00, 1350.00, 0.00, 1350.00, 'pending', DATE_SUB(NOW(), INTERVAL 38 DAY)),
(9, 4, DATE(DATE_ADD(NOW(), INTERVAL 82 DAY)), 1125.00, 225.00, 1350.00, 0.00, 1350.00, 'pending', DATE_SUB(NOW(), INTERVAL 38 DAY));

-- -----------------------------------------------------------------------------
-- 5. Transacciones de Pago (Últimos 30 días)
-- -----------------------------------------------------------------------------
INSERT INTO `payment` (`loan_id`, `loan_schedule_id`, `user_id`, `payment_method_id`, `amount`, `late_fee`, `notes`, `created_at`) VALUES
(3, 7, 1, 1, 495.00, 0.00, 'Abono cuota 1 en efectivo en ventanilla', DATE_SUB(NOW(), INTERVAL 22 DAY)),
(3, 8, 1, 1, 495.00, 0.00, 'Abono cuota 2 en efectivo en ventanilla', DATE_SUB(NOW(), INTERVAL 15 DAY)),
(4, 11, 1, 2, 400.00, 0.00, 'Abono parcial cuota 1 vía transferencia bancaria', DATE_SUB(NOW(), INTERVAL 12 DAY)),
(3, 9, 1, 1, 495.00, 0.00, 'Abono cuota 3 en efectivo', DATE_SUB(NOW(), INTERVAL 8 DAY)),
(1, 1, 1, 2, 1083.33, 0.00, 'Pago cuota 1 mediante depósito bancario', DATE_SUB(NOW(), INTERVAL 2 DAY)),
(3, 10, 1, 1, 495.00, 0.00, 'Cancelación total cuota 4 - Préstamo liquidado', DATE_SUB(NOW(), INTERVAL 1 DAY));

-- -----------------------------------------------------------------------------
-- 6. Gestiones de Cobranza (Seguimiento de Mora)
-- -----------------------------------------------------------------------------
INSERT INTO `collection` (`loan_id`, `loan_schedule_id`, `user_id`, `action_type`, `result`, `next_action_date`, `notes`, `created_at`) VALUES
(4, 11, 1, 'call', 'Cliente promete liquidar el saldo restante de la cuota 1 el fin de semana', DATE(DATE_ADD(NOW(), INTERVAL 3 DAY)), 'Se contactó vía telefónica, contestó titular', DATE_SUB(NOW(), INTERVAL 5 DAY)),
(9, 15, 1, 'call', 'No contestó llamada, se envió recordatorio por mensajería', DATE(DATE_ADD(NOW(), INTERVAL 2 DAY)), 'Primer aviso de vencimiento enviado', DATE_SUB(NOW(), INTERVAL 3 DAY));
