# OMNI-AGENT (Luopan Travel Edition)

## 1. Visión del Producto
Sistema CRM + ERP + AI Concierge diseñado exclusivamente para la agencia "Luopan Viajes y Turismo".
**Objetivo:** Automatizar la captura de leads vía WhatsApp mediante un Agente IA y gestionar las operaciones bimonetarias de la agencia en un panel centralizado.

## 2. Stack Tecnológico (Strict Rules)
* **Backend:** Laravel 11.x
* **Admin Panel:** FilamentPHP v3 (Resources, Widgets, Actions).
* **Frontend Público (Chat):** Livewire + Volt + Tailwind CSS (Estilo WhatsApp nativo).
* **AI Engine:** Gemini 2.5 Flash (Google AI Studio).
* **Database:** SQLite (MVP/Dev).
* **Testing:** PestPHP.

## 3. Arquitectura del Sistema
### A. Backoffice (FilamentPHP)
Es el centro de comando para las dueñas (Single Tenant).
* **LeadResource:** Kanban board o Tabla para gestionar los leads que entran por IA.
* **BookingResource:** Gestión de Expedientes (Files) con cálculo de ganancia en tiempo real.
* **TransactionResource:** Libro de caja (Ingresos/Egresos) con conversión automática ARS->USD.
* **Dashboard:** Widgets con métricas clave (Ventas del mes, Leads calientes en espera).

### B. Frontend Público (El Agente)
* **Tech:** Livewire Volt + Tailwind (Sin componentes externos pesados).
* **Ubicación:** `resources/views/livewire/public/chat-assistant.blade.php`.
* **Diseño:** Mobile-first, replica estética de WhatsApp.

### C. Capa de Servicios
* **AiConciergeService:** Conecta con Gemini API para procesar texto/audio y devolver JSON.
* **FinanceService:** Lógica de conversión de monedas y cálculo de rentabilidad "Opción A".

## 4. Reglas de Negocio Críticas
1.  **Moneda Dual (Anti-inflación):**
    * El sistema opera base USD.
    * Los pagos en ARS se congelan a USD según la tasa del día (`amount_usd_fixed`).
2.  **Pricing "All-inclusive":**
    * Ganancia = `Precio Venta` - `Costo Proveedor`.
3.  **Agente IA ("AsistenteBot"):**
    * **Misión:** Recolectar (Destino, Fechas, Pax, Presupuesto, Pago).
    * **Clasificación:** `cool` vs `hot`.
    * **Límite:** NO da precios. Escala a humanos.