# Guía rápida

## Tu rutina diaria (10 minutos)

1. **Escritorio:** mirá el panel. ¿Hay consultas *calientes*? ¿Cuotas vencidas?
2. **Tareas:** abrí *Gestión → Tareas*. El sistema te deja tareas automáticas (llamar a un cliente, cerrar una venta).
3. **Bandeja de WhatsApp** *(si la usan)*: respondé los mensajes con número verde.
4. **Consultas:** atendé primero las de **atención requerida**.
5. **Caja:** cargá los cobros y pagos del día.

## Dónde está cada cosa (menú de la izquierda)

![Menú lateral](imagenes/04-menu-lateral.png)

| Grupo | Contiene |
|---|---|
| **Reportes** | Analytics & Funnel |
| **Ventas** | Consultas · Clientes · Presupuestos / Files |
| **Tesorería** | Caja · Reportes Financieros |
| **Clientes** | Bandeja de WhatsApp |
| **Gestión** | Tareas |
| **Configuración** | Marca Blanca (todos los ajustes de la agencia) · Sliders (JSON) |
| **Catálogo** | Ideas de Viaje · Importar Ideas de Viaje · Sliders |
| **Sistema** | Server Admin · Seguimiento automático · Tipos de Servicio · Plantillas de WhatsApp · Usuarios · Categorías · Proveedores · Cuentas |

(Cada persona ve solo lo que su rol permite.)

## Los estados que tenés que conocer

**Consulta:** Nuevo → Contactado → Cerrado
**Temperatura:** 🔵 Frío · 🟡 Tibio · 🔴 Caliente
**Expediente:** Borrador → Presupuesto → **Señado** (pagó la seña) → Emitido → (Cancelado)
**Cuota:** Pendiente · Pago parcial · Pagada · Vencida

## Tres reglas de oro del dinero

1. **Todo expediente se lleva en dólares (USD).** Si algo se cobra o se compra en pesos, el sistema lo convierte con la cotización que elijas.
2. **La cotización se elige en cada operación:** Oficial, MEP, Tarjeta o Personalizada. Queda guardada, así los números de hoy no cambian cuando el dólar se mueve.
3. **El saldo del cliente = total del viaje en USD − lo cobrado en USD.** Un cobro en pesos suma lo que valían esos pesos el día que se pagaron.

## Atajos útiles

| Quiero… | Hago… |
|---|---|
| Armar un presupuesto desde una consulta | *Consultas* → ⋯ → **Crear Expediente** |
| Mandarle el presupuesto al cliente | Dentro del expediente: **Enviar por Email** / **Enviar por WhatsApp** / **Ver Link Público** |
| Armar cuotas | Expediente → pestaña **Plan de Pagos** → **Generar plan** |
| Cobrar una cuota | **Plan de Pagos** → **Registrar pago** en la fila de la cuota |
| Renovar un presupuesto vencido | Botón **Renovar validez** en el expediente |
| Pasar de la IA a una persona en WhatsApp | Responder desde la bandeja (la IA se pausa sola) |
| Cargar una idea desde la web de un operador | *Catálogo → Importar Ideas de Viaje* |

## Si algo no anda

- Aviso rojo **"El cron no está corriendo"** → avisá a quien administra el hosting (Guía de administración, sección 2).
- Los mails o respuestas automáticas tardan hasta **1 minuto**: es normal.
- No te deja guardar un servicio en pesos → falta la cotización: elegí una del selector o escribila.
