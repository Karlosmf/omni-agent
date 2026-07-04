---
type: database
title: "Esquema de Base de Datos"
description: "Modelos principales, relaciones y flujos de datos en Omni-Agent."
tags: ["database", "eloquent", "models", "migrations"]
---

# Esquema de Datos

Omni-Agent cuenta con un modelo relacional para rastrear los leads desde la captura hasta la conversión en expedientes facturables.

## Entidades Principales

### 1. Users
- Representa a los operadores y administradores del sistema.

### 2. Leads
- Clientes potenciales capturados por el chat reactivo.
- Campos clave:
  - `ai_data`: JSON que contiene la información estructurada por Gemini (destino, fechas, presupuesto, pasajeros).
  - `chat_history`: Historial de la conversación con el usuario.

### 3. Bookings
- Expedientes de viaje confirmados o cotizaciones activas.
- Relación: Pertenece a un `Lead`.
- Campos clave:
  - `file_number`: Número de legajo/expediente único.
  - `total_sell_usd`: Total cobrado al cliente.
  - `profit_usd`: Ganancia calculada en tiempo real.

### 4. BookingItems
- Servicios individuales incluidos dentro de un expediente (ej. Vuelos, Hoteles, Excursiones).
- Permiten calcular el margen de ganancia: `sell_usd` (precio de venta) vs `cost_usd` (costo del proveedor).

### 5. Transactions
- Movimientos de caja (cobros a clientes y pagos a proveedores).
- Soporte bimonetario: Almacena `amount` en moneda original (USD/ARS) y `amount_usd_fixed` para análisis consolidado.
