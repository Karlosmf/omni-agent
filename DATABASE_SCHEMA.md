# ESQUEMA DE BASE DE DATOS (Single Tenant / SQLite)

## 1. Acceso y Seguridad (Tabla `users`)
Usuarios del panel administrativo (Filament).
* **name**: string.
* **email**: string (Login).
* **password**: string.
* **is_admin**: boolean (Aunque sea single-tenant, por seguridad).

## 2. CRM & AI (Tabla `leads`)
El punto de entrada de los clientes potenciales. Gestionado en `LeadResource`.
* **source**: string ('whatsapp', 'instagram').
* **temperature**: enum ('cool', 'warm', 'hot').
* **status**: string ('new', 'contacted', 'closed').
* **customer_name**: string.
* **customer_phone**: string.
* **raw_message**: text (Chat completo).
* **ai_data**: json (Datos estructurados).
* **ai_summary**: text (Resumen humano).
* **needs_human_attention**: boolean (Flag para alertas en Dashboard).

## 3. ERP Operativo (Tabla `bookings`)
El "Expediente". Gestionado en `BookingResource`.
* **file_number**: string (Único, ej: "LP-2026-001").
* **holder_name**: string.
* **total_cost_usd**: decimal (Suma costos proveedores).
* **total_sell_usd**: decimal (Suma venta cliente).
* **profit_usd**: decimal (Rentabilidad neta).
* **status**: string ('presupuesto', 'senado', 'emitido').
* **travel_date**: date.

## 4. Detalle de Servicios (Tabla `booking_items`)
Relación `HasMany` en `BookingResource`.
* **booking_id**: FK.
* **type**: string ('flight', 'hotel', 'transfer').
* **description**: string.
* **supplier_name**: string.
* **cost_usd**: decimal (Costo real).
* **sell_usd**: decimal (Precio venta).

## 5. Caja y Finanzas (Tabla `transactions`)
Gestionado en `TransactionResource`.
* **booking_id**: FK.
* **type**: enum ('cobro', 'pago').
* **currency**: string ('ARS', 'USD').
* **amount**: decimal.
* **exchange_rate**: decimal.
* **amount_usd_fixed**: decimal (Valor real dolarizado).
* **method**: string.