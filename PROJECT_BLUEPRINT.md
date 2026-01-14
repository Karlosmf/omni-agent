# OMNI-AGENT (Technical Blueprint)

## 1. Arquitectura del Sistema
El sistema opera como un monolito modular construido sobre Laravel 12.

### Componentes Principales
*   **Backoffice (Filament v4):** Panel administrativo para gestión de `Leads`, `Bookings` (Expedientes) y `Transactions` (Caja).
    *   Ubicación: `app/Filament/Admin`
*   **Frontend Público (Livewire Volt):** Chatbot reactivo para captura de leads.
    *   Ubicación: `resources/views/livewire/public/chat-assistant.blade.php`
*   **Motor de IA (Gemini 2.0 Flash):** Procesa lenguaje natural para estructurar intenciones de compra.
    *   Servicio: `App\Services\AiConciergeService`
    *   Job Asíncrono: `App\Jobs\ProcessAiResponse`

## 2. Esquema de Datos (Actual)

### Core
*   **Users:** Administradores del sistema.
*   **Leads:** Clientes potenciales capturados por el chat. Contienen el JSON crudo de la IA (`ai_data`) y el historial del chat.
*   **Bookings:** Expedientes de viaje.
    *   Campos clave: `file_number`, `total_sell_usd`, `profit_usd`.
    *   Relación: Pertenece a un `Lead`.
*   **BookingItems:** Servicios individuales dentro de una reserva (Vuelo, Hotel).
    *   Calculan `cost_usd` vs `sell_usd`.
*   **Transactions:** Movimientos de caja (Cobros/Pagos).
    *   Manejo bimonetario: Guarda `amount` original (ARS/USD) y `amount_usd_fixed` (normalizado).

### Futuro (Fases 6-7)
*   **Customers:** Entidad centralizada de clientes (abstraída de Leads/Bookings).
*   **Suppliers:** Proveedores de servicios turísticos.

## 3. Flujos Críticos

### Flujo de Captura de Lead (Chat)
1.  Usuario envía mensaje en `/chat`.
2.  `Lead` se crea inmediatamente en BD (Sync).
3.  `ProcessAiResponse` (Job) se despacha a la cola.
4.  Gemini API analiza el texto. Si falla (Quota/Error), se usa Mock Data.
5.  `Lead` se actualiza con `temperature` y `ai_summary`.

### Flujo Financiero
1.  Se crea `Booking`.
2.  Se agregan `BookingItems` (Costo vs Venta).
3.  Se registran `Transactions` (Cobros al cliente).
4.  El sistema calcula `profit_usd` en tiempo real.
5.  Se genera PDF de Recibo o Voucher mediante `dompdf`.

## 4. Stack Tecnológico
*   **Framework:** Laravel 12.x
*   **Admin:** FilamentPHP v4 (Schemas/Tables structure).
*   **Frontend:** Livewire v3 + Volt + TailwindCSS v4.
*   **Testing:** PestPHP v3.
*   **PDF:** Barryvdh DomPDF.
*   **AI:** Google Gemini API (v1beta/v1 via HTTP Client).
