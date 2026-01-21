# Plan de Desarrollo: Omni-Agent (Luopan Travel Edition)

## 1. Visión del Proyecto
Sistema CRM + ERP + AI Concierge para "Luopan Viajes y Turismo".
**Objetivo:** Centralizar la operación financiera (multimoneda), gestionar expedientes de viajes y automatizar la captura de leads vía WhatsApp/Web con IA.
**Estado:** MVP Completado (v1.0). Fases de refinamiento CRM, IA y QA en progreso.

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
- Entidad `Customer` **NOTA: Actualmente se usa User con rol Customer; requiere separación si se desea fidelización histórica independiente**.
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

### 🧠 FASE 9: Motor de Inteligencia Artificial (COMPLETO)
Objetivo: Conectar Laravel con Gemini de forma robusta.

*   **Tarea 9.1: Servicio de IA (AiConciergeService)** (Done)
    *   Implementado con Gemini Flash 1.5. Resúmenes automáticos y respuestas en chat.
*   **Tarea 9.2: Integración con Leads** (Done)
    *   Campos `ai_summary` y resúmenes automáticos activos en el CRM.

### 💬 FASE 10: Frontend Público (Chatbot & Navbar) (COMPLETO)
Objetivo: Interfaz visual premium y captura de leads.

*   **Tarea 10.1: Componente Visual (Volt)** (Done)
    *   Chat tipo WhatsApp integrado.
*   **Tarea 10.2: Lógica Reactiva** (Done)
    *   Conexión con Gemini y persistencia de leads.
*   **Tarea 10.3: Refactor Navbar** (Done)
    -   Modal de Consultas, Dropdown WhatsApp, Botón Instagram y Soporte Dark Mode.

### 🏗️ FASE 11: Refinamiento CRM y Relacional (Alta Prioridad)
Objetivo: Separar Customer de User para fidelización histórica y datos relacionales.

*   **Tarea 11.1: Crear Modelo Customer**
    *   [ ] Generar `app/Models/Customer.php` con campos: name, email, phone, history_json.
    *   [ ] Relaciones: hasMany Booking, hasMany Lead.

*   **Tarea 11.2: Migración de Datos**
    *   [ ] Script para copiar User::Customer a Customer, actualizar foreign keys en bookings/leads.

*   **Tarea 11.3: Actualizar CustomerResource**
    *   [ ] Añadir RelationManager para ver historial de bookings/transacciones.

Beneficio: Mejor seguimiento de leads a clientes recurrentes.

### 🧠 FASE 12: Mejoras en IA y Persistencia (Media Prioridad)
Objetivo: Hacer IA más robusta y conversacional.

*   **Tarea 12.1: Modelo Message para Historial**
    *   [ ] Crear `Message` (belongsTo Lead, campos: content, role (user/ai), timestamp).

*   **Tarea 12.2: Actualizar AiConciergeService**
    *   [ ] Pasar historial de mensajes a Gemini para contexto (system prompt + últimas 10 msgs).
    *   [ ] Mejorar error handling: Log detallado de fallos API, fallback a respuestas predefinidas.

*   **Nueva Idea: Temperatura Dinámica**
    *   [ ] Ajustar "temperatura" basada en lead_data (ej. presupuesto alto → respuestas más agresivas).

### 🧪 FASE 13: Expansión de Tests, QA y UX (Alta Prioridad)
Objetivo: Aumentar confiabilidad y usabilidad.

*   **Tarea 13.1: Añadir Tests Pest**
    *   [ ] Feature para TransactionResource (form reactivo, condicional).
    *   [ ] Unit para AiConciergeService (mock Gemini).
    *   [ ] Browser para chat-assistant (enviar msg, verificar respuesta).

*   **Tarea 13.2: Ejecutar QA Post-Fase**
    *   [ ] `php artisan test --compact`, `php artisan model:show [Modelo]`.
    *   [ ] Pruebas manuales (ej. crear pago → verificar deuda proveedor).
    *   [ ] `vendor/bin/pint --dirty` para estilo de código.

*   **Tarea 13.3: Mejoras UX**
    *   [ ] Widget dashboard "AI Insights" con métricas de leads (temperatura promedio, conversiones).
    *   [ ] Volt componente para "Formulario de Consulta Inicial" (antes del chat), con validación en tiempo real.

## 5. Protocolo de Control de Calidad (QA)
Al finalizar cada Fase, ejecutar:
*   `php artisan test --compact` (Cobertura mínima 70% en Pest, incluyendo browser tests).
*   `php artisan model:show [Modelo]` para verificar relaciones.
*   Prueba manual de flujo crítico (ej: Crear Pago -> Ver descuento en deuda Proveedor).
*   `vendor/bin/pint --dirty` para estilo de código.
*   Verificar integraciones (ej. Gemini API key, multi-moneda en bookings).

## 6. Nuevas Ideas Generales
*   **API para Integraciones:** Endpoint REST para bookings (con Sanctum), permitiendo apps móviles futuras.
*   **Reportes Avanzados:** PDFs con charts (usando Laravel Charts) para finanzas mensuales.
*   **Multi-Tenant Futuro:** Prep para tenants (ej. añadir tenant_id en modelos clave).
*   **Seguridad Extra:** Rate limiting en chat IA (via Middleware), encriptación de ai_data sensibles.
*   **Monitoreo:** Logs de IA en Laravel Telescope para debugging.
