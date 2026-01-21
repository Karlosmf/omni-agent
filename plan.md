# Plan de Desarrollo: Omni-Agent (Luopan Travel Edition)

## 1. Visión del Proyecto
Sistema CRM + ERP + AI Concierge para "Luopan Viajes y Turismo".
**Objetivo:** Centralizar la operación financiera (multimoneda), gestionar expedientes de viajes y automatizar la captura de leads vía WhatsApp/Web con IA.
**Estado:** MVP Completado (v1.0). Iniciando fase de reestructuración financiera y expansión.

## 2. Stack Tecnológico (Strict Rules)
*   **Framework:** Laravel 12.x
*   **Admin:** FilamentPHP v4 (Resources, Widgets).
*   **Frontend:** Livewire Volt + Tailwind CSS v4 (Estilo nativo, SIN librerías de componentes).
*   **Database:** SQLite (Migrable a MySQL).
*   **AI:** Google Gemini 1.5 Flash (via google-gemini-php).
*   **Infraestructura:** Single Tenant.

## 3. Historial de Fases (Completadas)

- **Fase 1 a 5 (Core):** Modelos base, Backoffice, IA Concierge, Chat Público, Seguridad y Tests.
- **Fase Extra (UX/Reportes):** PDFs de Vouchers y Recibos, Dashboard con métricas, Traducción total.

### Fase 6: CRM y Fidelización (COMPLETO)
Transformar el manejo de datos planos en entidades relacionales para seguimiento histórico.
- Entidad `Customer` implementada.
- Migración de datos históricos (Leads/Bookings -> Customers) ejecutada.
- Resource `Customer` activo y formularios actualizados.

---

## 4. Hoja de Ruta de Ejecución (Nueva Estructura)

A partir de aquí se define la nueva hoja de ruta para la refactorización financiera y la integración profunda de IA.

### 🛑 FASE 7 (Ex Fase 1): Refactorización del Core Financiero (COMPLETO)
Objetivo: Establecer la estructura de base de datos correcta para soportar Tesorería y Cuentas Corrientes.

*   **Tarea 7.1: Tablas de Estructura Financiera** (Done)
*   **Tarea 7.2: Actualización de Tabla Transactions** (Done)
*   **Tarea 7.3: Lógica de Negocio (Modelos)** (Done)

### ⚙️ FASE 8 (Ex Fase 2): Backoffice & Tesorería (COMPLETO)
Objetivo: Que Belén y Nela puedan cargar gastos, pagos y ver saldos reales.

*   **Tarea 8.1: ABMs Financieros** (Done)
*   **Tarea 8.2: TransactionResource** (Done)
    *   Formulario reactivo, lógica condicional, upload y manejo de Cotización opcional.
*   **Tarea 8.3: Gestión de Proveedores** (Done)

### 🌍 FASE EXTRA: Mejoras de Usabilidad y Locale (COMPLETO)
*   **Localización:** Configuración de idioma 'es'.
*   **Widget Tesorería:** Saldos de cajas/bancos en Dashboard.
*   **Multi-moneda en Expedientes:** 
    *   Soporte para items en distintas monedas (BRL, USD, ARS).
    *   Resumen financiero desglosado (Ganancia USD vs ARS).

### ⚙️ FASE 8 (Ex Fase 2): Backoffice & Tesorería (Filament)
Objetivo: Que Belén y Nela puedan cargar gastos, pagos y ver saldos reales.

*   **Tarea 8.1: ABMs Financieros**
    *   [ ] Generar `FinancialAccountResource`: CRUD simple.
    *   [ ] Generar `TransactionCategoryResource`: CRUD simple con filtro por tipo.

*   **Tarea 8.2: TransactionResource (El Cerebro Contable)**
    *   [ ] Implementar formulario reactivo (`live()`).
    *   [ ] Lógica Condicional:
        *   Si `type` == 'cobro' → Mostrar Select `booking_id`.
        *   Si `type` == 'pago' → Mostrar Select `supplier_id` (o payable).
    *   [ ] Filtrar categorías según el tipo seleccionado.
    *   [ ] Upload de archivos en `attachment_path`.
    *   [ ] Manejo de Moneda: Si es ARS, pedir Cotización y calcular `amount_usd_fixed`.

*   **Tarea 8.3: Gestión de Proveedores**
    *   [ ] Actualizar `SupplierResource`.
    *   [ ] Agregar `RelationManager` para ver historial de Pagos y Compras.
    *   [ ] Mostrar el `balance_usd` (Deuda actual) en la tabla principal.

### 🧠 FASE 9 (Ex Fase 3): Motor de Inteligencia Artificial
Objetivo: Conectar Laravel con Gemini de forma robusta.

*   **Tarea 9.1: Servicio de IA (AiConciergeService)**
    *   [ ] Implementar método `processMessage` real.
    *   [ ] Configurar cliente Gemini con API Key del `.env`.
    *   [ ] System Prompt: Definir personalidad "Asistente Luopan" y estructura de salida JSON obligatoria.
    *   [ ] Manejo de Errores: Try/Catch para evitar pantallas rojas si Google falla.

*   **Tarea 9.2: Integración con Leads**
    *   [ ] Asegurar que el JSON devuelto por la IA actualice los campos `ai_data`, `ai_summary` y `temperature` en la tabla `leads`.

### 💬 FASE 10 (Ex Fase 4): Frontend Público (Chatbot)
Objetivo: Interfaz visual para el cliente final.

*   **Tarea 10.1: Componente Visual (Volt)**
    *   [ ] Crear `resources/views/livewire/public/chat-assistant.blade.php`.
    *   [ ] Estilos: Usar Tailwind CSS puro para replicar estética WhatsApp (Fondo beige, burbujas verdes/blancas).
    *   [ ] NO usar librerías externas (MaryUI eliminada).

*   **Tarea 10.2: Lógica Reactiva**
    *   [ ] Conectar Input usuario -> AiConciergeService -> Respuesta en pantalla.
    *   [ ] Implementar indicador de carga "Escribiendo...".
    *   [ ] Persistencia: Guardar cada mensaje en el historial del Lead.

## 5. Protocolo de Control de Calidad (QA)
Al finalizar cada Fase, ejecutar:
*   `php artisan test` (Si hay tests creados).
*   `php artisan model:show [Modelo]` para verificar relaciones.
*   Prueba manual de flujo crítico (ej: Crear Pago -> Ver descuento en deuda Proveedor).
