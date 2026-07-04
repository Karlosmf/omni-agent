---
type: flow
title: "Flujos Críticos"
description: "Detalle de los flujos de captura de leads por IA y flujos financieros."
tags: ["flows", "jobs", "queues", "finance"]
---

# Flujos Críticos de Negocio

## 1. Flujo de Captura de Lead (Chatbot)
1. **Interacción:** El usuario final ingresa al chat público en `/chat` y envía un mensaje.
2. **Persistencia Inicial:** Se crea un registro de `Lead` en la base de datos de manera síncrona.
3. **Procesamiento en Cola:** Se despacha el Job asíncrono `ProcessAiResponse`.
4. **Consulta a Gemini:** El Job interactúa con la API de Gemini para analizar los mensajes, extraer información estructurada (JSON) y formular la siguiente respuesta.
5. **Manejo de Fallos:** Si la API de Gemini falla (límites de cuota, error de red), el sistema utiliza respuestas por defecto / Mock Data para evitar interrumpir la experiencia.
6. **Actualización:** El `Lead` se actualiza con los nuevos datos (`temperature`, `ai_summary` e historial).

## 2. Flujo Financiero y de Expedientes
1. **Creación:** Se genera un `Booking` asociado a un `Lead` cualificado.
2. **Carga de Items:** Se añaden `BookingItems` detallando costo (`cost_usd`) y venta (`sell_usd`).
3. **Cálculo de Margen:** El sistema recalcula la ganancia neta (`profit_usd`) del expediente.
4. **Registro de Pagos/Cobros:** Se asocian `Transactions` (entradas o salidas de dinero).
5. **Documentación:** Se emite el recibo o voucher en PDF utilizando `dompdf`.
