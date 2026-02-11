# Manual de Usuario: Omni-Agent (Luopan Viajes)

Bienvenido al sistema de gestión inteligente de Luopan Viajes. Esta guía te ayudará a utilizar las funciones principales del panel y el asistente virtual.

## 1. Acceso al Sistema
*   **URL del Panel:** `/admin`
*   **Usuarios:**
    -   **Belén:** `belenzorzon@luopanviajes.tur.ar` (Admin)
    -   **Nela:** `nelaflama@luopanviajes.tur.ar` (Staff)
*   **Credenciales:** Contraseña compartida asignada recientemente. Usar el botón "Ingresar" con icono de usuario en la web principal.

---

## 2. El Dashboard (Tablero Principal)
Al ingresar, verás el centro de comando con información vital:
*   **Métricas de Leads:** Total de leads, leads calientes (urgentes) y tasa de conversión.
*   **Accesos Rápidos:** Botones para "Nueva Consulta" (crear lead) y "Buscar Cliente".
*   **Próximas Salidas:** Viajes confirmados en los próximos 30 días con semáforo de urgencia (rojo ≤7 días, amarillo ≤15, gris +15).
*   **Deudas Proveedores:** Resumen de deuda total USD y proveedores con saldo pendiente (al final del dashboard).

---

## 3. Gestión de Leads (Clientes Potenciales)
El sistema captura automáticamente consultas desde el Chat Público (`/chat`).

1.  Ve al menú **Leads**.
2.  Verás una lista de interesados.
    *   **Temperatura:**
        *   🔴 **Caliente:** Presupuesto alto o intención clara de compra. ¡Contactar ya!
        *   🟡 **Tibio:** Interesado, pero comparando precios.
        *   🔵 **Frío:** Consultas generales.
3.  **Acción "Escalar a Humano":** Si un lead requiere atención especial, haz clic en este botón para marcarlo con una bandera amarilla de alerta.

---

---

## 4. Gestión de Presupuestos (Cotizaciones)
Antes de confirmar un viaje, puedes armar una propuesta profesional.

1.  Ve al menú **Ventas** -> **Presupuestos**.
2.  El listado se organiza en **pestañas** para facilitar el seguimiento:
    *   🕐 **Pendientes:** Presupuestos en estado Borrador o Enviados (los que estás gestionando).
    *   ✅ **Aprobados (Files):** Presupuestos que ya fueron aceptados y convertidos a File.
    *   ❌ **Rechazados / Expirados:** Propuestas que no prosperaron.
    *   📋 **Todos:** Historial completo.
3.  **Nuevo Presupuesto**:
    *   Ingresa Cliente (puedes crearlo ahí mismo), Destino y Fechas.
    *   Carga los servicios (Vuelos, Hoteles) con sus costos y precios de venta.
    *   El sistema calculará automáticamente el total y tu ganancia estimada.
4.  **PDF:** Descarga el presupuesto con diseño profesional para enviar por WhatsApp.
5.  **Duplicar:** Crea una copia rápida del presupuesto para otro cliente o variante.
6.  **Convertir a File:** Si el cliente acepta, usa el botón "Aprobar / Convertir a File" para transformar el presupuesto en una reserva confirmada automáticamente. El presupuesto pasará a la pestaña "Aprobados".

---

## 5. Gestión de Files (Expedientes)
Aquí es donde se arma el viaje confirmado.

### Crear un File
1.  Menú **Ventas** -> **Files** -> **Nuevo File**.
2.  **General:** Asocia un Lead (o déjalo vacío si es venta directa). El **Nro de File** se genera automáticamente (ej: LP-2026-001) al guardar. Ingresa el Titular.
3.  **Servicios (Items):** Agrega items al viaje usando el desplegable de tipos (Vuelo, Hotel, Hotel y Traslado, Terrestre, Asistencia, Bus, Crucero, Otro).
    *   **Moneda:** Selecciona la moneda original del servicio (ej: BRL para un Hotel en Brasil).
    *   **Cotización:** Indica el tipo de cambio de esa moneda.
    *   **Costo y Venta:** Ingresa los montos en la moneda seleccionada.
    *   El sistema mostrará dos resúmenes: uno en **Pesos (ARS)** y otro en **Dólares (USD)**.
4.  **Guardar.**

### Descargar Documentación
En la lista de Files o dentro de la edición, usa el botón **PDF** (icono verde) para descargar el "Comprobante de File".

---

## 6. Panel de Tesorería y Proveedores
Gestión avanzada para el control de deudas y pagos.

1.  **Proveedores:** (Menú Tesorería -> Proveedores)
    *   Ficha completa con datos de contacto.
    *   **Historial de Transacciones:** Pestaña para ver todos los pagos realizados a ese proveedor.
    *   **Deuda:** Columna "Deuda (USD)" en la tabla principal para ver saldos pendientes rápidamente.
2.  **Widget de Deudas:** En el Dashboard, verás un resumen de "Deuda Total Proveedores" y alertas de saldos pendientes.

---

## 7. Caja y Finanzas (Transactions)
Registra pagos de clientes o pagos a proveedores.

1.  Menú **Tesorería** -> **Movimientos de Caja**.
2.  **Nueva Transacción**:
    *   Elige el File o Proveedor.
    *   **Nro Correlativo:** Cada movimiento tiene un número único (#ID) para mejor control.
    *   **Calculadora Financiera (Novedad):**
        *   Despliega la sección "Calculadora de Neto / Impuestos".
        *   Ingresa el monto bruto (ej. lo que paga el cliente por MercadoPago).
        *   Ajusta los porcentajes de Impuestos (Banco, IIBB, Plataforma).
        *   El sistema calculará el **Neto Real** que entra a caja automáticamente.
    *   Completa el resto de datos (Método, Moneda, etc.).
3.  **Recibos:** Descarga el PDF del comprobante de pago desde la lista.

---

## 8. Simulador Financiero (Dashboard)
En el panel principal encontrarás el widget **"Simulador Financiero"**.
*   Úsalo para hacer cuentas rápidas sin guardar nada.
*   Ejemplo: "¿Cuánto le tengo que cobrar al cliente para que me queden $1000 limpios?" (Jugando con los montos).

---

---

## 9. Captura de Consultas (Web)
La página principal cuenta con tres puntos de contacto inteligentes:
1.  **Formulario de Consultas (Modal):** El botón "Consultas" abre un formulario limpio. Al enviarlo, la IA genera automáticamente un resumen para que no tengas que leer todo el mensaje largo en el CRM.
2.  **WhatsApp Directo:** El menú desplegable permite al cliente elegir contactar a **Belén** o **Nela** directamente.
3.  **Chatbot Inteligente (Brisa):** El botón flotante abre el chat. Antes de chatear, el visitante completa su **nombre** y **destino de interés**. Así el lead se crea con datos reales (no más "Web Guest"). El chat abre con un saludo personalizado y la IA recaba información clave del viaje.

---

## 10. Redes Sociales y Links
En el pie de página encontrás acceso directo a Facebook e Instagram oficiales de Luopan Viajes.
