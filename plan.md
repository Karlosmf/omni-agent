# Plan de Desarrollo: Omni-Agent (Luopan Travel Edition) - EXPANSIÓN

## 1. Visión General
Sistema CRM + ERP + AI Concierge para "Luopan Viajes y Turismo".
**Estado:** MVP Completado (v1.0). Iniciando fase de expansión hacia CRM real y Contabilidad avanzada.

**Stack:** Laravel 12, FilamentPHP v4, Livewire v3, TailwindCSS v4, PestPHP v4.

## 2. Historial de Fases (Completadas)

- **Fase 1 a 5 (Core):** Modelos base, Backoffice, IA Concierge, Chat Público, Seguridad y Tests.
- **Fase Extra (UX/Reportes):** PDFs de Vouchers y Recibos, Dashboard con métricas, Traducción total.

## 3. Fases de Expansión (Hoja de Ruta)

### Fase 6: CRM y Fidelización (COMPLETO)
Transformar el manejo de datos planos en entidades relacionales para seguimiento histórico.
- Entidad `Customer` implementada.
- Migración de datos históricos (Leads/Bookings -> Customers) ejecutada.
- Resource `Customer` activo y formularios actualizados.

### Fase 7: Contabilidad Avanzada y Proveedores (EN PROGRESO)
Control total del flujo de dinero, no solo por venta, sino por operación global.

- **Subtarea 7.1: Entidad Supplier (COMPLETO)**
  - Modelo `Supplier` y `SupplierAccount` (Cuentas bancarias múltiples).
  - Categorías de proveedores y datos fiscales.
- **Subtarea 7.2: Cuentas Corrientes (EN PROGRESO)**
  - Vincular `BookingItem` (costo) a un `Supplier` (Hecho).
  - Registro de pagos con selección de cuenta bancaria y visualización de CBU (Hecho).
  - Sistema de "Cuentas por Pagar": Dashboard de deuda con proveedores (Pendiente).
- **Subtarea 7.3: Caja Diaria y Arqueo (Pendiente)**
  - Recurso para cerrar caja diaria.
  - Reporte de flujo de caja.

### Fase 8: Integraciones Externas (Futuro)
- API WhatsApp Business (Meta).
- Notificaciones automáticas de pago/reserva por email.
