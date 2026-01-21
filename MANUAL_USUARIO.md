# Manual de Usuario: Omni-Agent (Luopan Viajes)

Bienvenido al sistema de gestión inteligente de Luopan Viajes. Esta guía te ayudará a utilizar las funciones principales del panel y el asistente virtual.

## 1. Acceso al Sistema
*   **URL del Panel:** `/admin`
*   **Credenciales:** Usa tu correo corporativo y contraseña asignada.

---

## 2. El Dashboard (Tablero Principal)
Al ingresar, verás el centro de comando con información vital:
*   **Accesos Rápidos:** Botones grandes para "Nuevo Lead", "Nuevo Expediente" y "Registrar Pago". Úsalos para ahorrar tiempo.
*   **Próximas Salidas:** Una lista de los viajes confirmados más cercanos en el tiempo. Ideal para hacer seguimiento de documentación.
*   **Movimientos de Caja:** Los últimos 5 ingresos o egresos de dinero.
*   **Métricas:** Indicadores de "Leads Calientes" (urgentes) y ventas del mes.

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

## 4. Gestión de Expedientes (Bookings)
Aquí es donde se arma el viaje.

### Crear un Expediente
1.  Menú **Bookings** -> **Nuevo Booking**.
2.  **General:** Asocia un Lead (o déjalo vacío si es venta directa), ingresa el Nro de Expediente y Titular.
3.  **Servicios (Items):** Agrega Vuelos, Hoteles o Traslados.
    *   **Moneda:** Selecciona la moneda origianl del servicio (ej: BRL para un Hotel en Brasil).
    *   **Cotización:** Indica el tipo de cambio de esa moneda.
    *   **Costo y Venta:** Ingresa los montos en la moneda seleccionada.
    *   El sistema mostrará al final dos resúmenes: uno en **Pesos (ARS)** y otro en **Dólares (USD/Moneda de Reporte)**.
4.  **Guardar.**

### Descargar Documentación
En la lista de Bookings o dentro de la edición de uno, verás un botón **PDF** (icono verde). Haz clic para descargar el "Comprobante de Expediente" listo para enviar al cliente.

---

## 5. Caja y Finanzas
Para registrar pagos de clientes o pagos a proveedores.

1.  Menú **Transactions**.
2.  **Nueva Transacción**:
    *   Elige el Expediente.
    *   Selecciona **Cobro** (Entra dinero) o **Pago** (Sale dinero).
    *   Elige Moneda (ARS/USD).
    *   Select "Efectivo", "Transferencia", etc.
    *   **Cotizar (Switch):** Actívalo si necesitas convertir el monto a USD referencial. Si lo dejas apagado, el sistema tomará el monto tal cual como referencia.
3.  **Recibos:** En la lista de transacciones, haz clic en el icono "Recibo" para descargar el PDF del comprobante de pago.

---

## 6. Chat de IA
La dirección `/chat` es pública. Puedes enviársela a tus clientes.
*   El asistente virtual ("Omni-Agent") tomará los datos básicos (destino, fechas, pax).
*   Si la API de IA está saturada, el sistema guardará el mensaje de todos modos para que no pierdas el contacto.
