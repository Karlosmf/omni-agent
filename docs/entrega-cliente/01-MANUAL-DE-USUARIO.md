# Manual de usuario

Referencia completa del sistema, pantalla por pantalla. Si preferís aprender con ejemplos, usá los **Tutoriales** (documento 02).

**Índice**

1. Cómo está organizado el sistema
2. Ingresar y roles del equipo
3. El Escritorio (pantalla de inicio)
4. Consultas (leads)
5. Clientes
6. Presupuestos y expedientes (files)
7. Moneda y cotización
8. Plan de pagos y cuotas
9. Caja: cobros y pagos
10. Proveedores, cuentas y categorías
11. Seguimiento automático y tareas
12. Bandeja de WhatsApp
13. Ideas de Viaje y el Importador
14. La vidriera web y sus formularios
15. Portal del cliente
16. Configuración de la agencia
17. Reportes
18. Glosario y preguntas frecuentes

---

## 1. Cómo está organizado el sistema

El sistema tiene dos partes:

- **El sitio público**, que ven tus clientes: catálogo de ideas de viaje, chat con asistente virtual, formulario de consulta, cotizador y la página privada de cada presupuesto.
- **El panel de administración** (el que usás vos y tu equipo), con el menú a la izquierda.

El recorrido típico de un viaje es:

> **Consulta** → **Presupuesto** (se envía al cliente) → **Señado** (el cliente paga la seña) → **Cuotas y cobros** → **Emitido** (viaje listo) → cierre y reportes

Todo lo que pasa queda registrado: quién hizo qué, cuándo se envió un presupuesto, cuántas veces lo abrió el cliente, cada cobro y cada pago.

![Menú lateral del panel con todos sus grupos](imagenes/04-menu-lateral.png)


## 2. Ingresar y roles del equipo

Entrá desde la dirección del panel de tu agencia (termina en `/admin`) con tu correo y contraseña. Si la olvidaste, pedile a un administrador que la cambie desde *Sistema → Usuarios*.

![Pantalla de ingreso al panel](imagenes/01-ingreso.png)


| Rol | Qué puede hacer |
|---|---|
| **Administrador** | Todo: usuarios, configuración, finanzas, sistema. |
| **Ventas** | Trabaja consultas y presupuestos. **Ve sus propios expedientes** y los que todavía no tienen vendedor. No ve los reportes financieros. |
| **Finanzas** | Ve **todos** los expedientes, la Caja y los **Reportes Financieros**. |
| **Staff / Usuario** | Acceso básico. El administrador puede darle permisos puntuales desde *Sistema → Usuarios*. |
| **Cliente** | Solo accede al portal del cliente (sección 15). |

> **Importante:** el rol de una persona solo lo puede cambiar un administrador desde *Sistema → Usuarios*.

![Sistema → Usuarios](imagenes/25-usuarios.png)


## 3. El Escritorio (pantalla de inicio)

Al entrar ves el **Escritorio**, con lo importante del momento (algunas tarjetas aparecen según tu rol):

| Qué ves | Para qué sirve |
|---|---|
| **Accesos rápidos** (Nueva Consulta, Buscar Cliente, Nuevo File, Registrar Pago) | Los cuatro atajos más usados. |
| **Leads Capturados · Cotizaciones Enviadas · Viajes Vendidos** | El recorrido comercial con sus porcentajes de conversión. |
| **Orígenes de Consultas / Fuentes de Leads** | De dónde llegan tus consultas (chat, formulario, cotizador, WhatsApp). |
| **Top Paquetes Vendidos** | Las ideas de viaje que más se venden. |
| **Ventas Mes Actual · Gross Profit · Tasa de Conversión** | Ventas del mes y ganancia, en USD. |
| **Mis Tareas Pendientes** | Tu agenda del día (incluye las tareas automáticas). |
| **Velocidad de respuesta** | Cuánto tardás en promedio en contactar una consulta nueva y qué porcentaje de presupuestos enviados abrió el cliente. |
| **Funnel de Conversión** | Consultas → presupuestos → reservas confirmadas. |
| **Atención Necesaria · Nuevas Consultas** | Cuántas consultas esperan a una persona y cuántas todavía no se atendieron. |
| **Cobranzas** | Cuotas vencidas y cuotas que vencen en los próximos 7 días, con el monto pendiente en USD. |
| **Ingresos · Egresos · Balance Neto (Mes Actual)** | Cobros y pagos del mes, en USD. |
| **Próximas Salidas Confirmadas** | Los viajes que salen pronto. |
| **Resultados Financieros (Año Actual)** | Gráfico mensual de ingresos y egresos. |
| **Cotizaciones** | El dólar del día (Oficial, MEP, Tarjeta), con un botón para actualizar. |
| **Aviso "El cron no está corriendo"** | Solo para administradores y solo si hay un problema: los envíos automáticos están frenados (ver Guía de administración). |


![El Escritorio](imagenes/02-inicio.png)

## 4. Consultas (leads)

*Menú: **Ventas → Consultas***

Una **consulta** es una persona interesada en viajar. Entran solas por varios caminos y el sistema anota el **origen**:

- el **chat** del sitio (con asistente virtual) ⚙️ *la IA requiere configuración*,
- el **formulario de consulta**,
- el **cotizador** (la persona completa destino, fechas y pasajeros),
- **WhatsApp** (sección 12),
- una conexión externa (API), si la agencia la usa.

El mismo cliente que vuelve a escribir no se duplica: se reconoce por su email o teléfono. Quien no deja ningún dato de contacto se guarda como una consulta separada (no se mezcla con otras).

### Estados y temperatura

| Estado | Significado |
|---|---|
| **Nuevo** | Nadie la atendió todavía. |
| **Contactado** | Ya se habló con la persona (pasa solo al cambiar el estado o al enviarle un presupuesto). |
| **Cerrado** | Terminó (compró o se descartó). |

| Temperatura | Significado |
|---|---|
| 🔴 **Caliente** | Quiere comprar ya o pidió hablar con una persona: llamala primero. |
| 🟡 **Tibio** | Interesado, compara. Hacele seguimiento. |
| 🔵 **Frío** | Solo pregunta. |

La columna **Atención** marca las que necesitan a una persona. La columna **Próximo seguimiento** muestra cuándo toca volver a hablar (se pone en rojo si ya pasó) y el filtro **Seguimiento atrasado** las lista juntas.

![Listado de consultas agrupado por estado](imagenes/03-consultas.png)


### Lo que completa la inteligencia artificial

Cuando hay IA configurada, lee la conversación y completa: **destino, presupuesto, pasajeros, fecha o quincena, noches, ciudad de salida y nombre**, y arma un resumen. Todo es editable: la IA ayuda, no decide.

### Acciones sobre una consulta

Menú ⋯ de cada fila:

- **WhatsApp:** abre un chat con un mensaje de presentación listo.
- **Editar:** cambiar datos, estado, vendedor asignado.
- **Escalar a humano:** marca la consulta como urgente.
- **Crear Expediente:** arma el presupuesto copiando destino, pasajeros y datos de la consulta.
- **Ver Cliente:** abre la ficha del cliente.

> Si la agencia activó la **asignación automática**, cada consulta nueva se reparte sola entre los vendedores (al que menos pendientes tiene). Si no, las consultas sin vendedor las ve todo el equipo.

## 5. Clientes

*Menú: **Ventas → Clientes***

La agenda de personas que viajan. Cada ficha guarda nombre, teléfono, email y el historial de consultas y viajes. Podés crear clientes nuevos desde acá o "al vuelo" mientras armás un expediente.

## 6. Presupuestos y expedientes (files)

*Menú: **Ventas → Presupuestos / Files***

Un mismo registro cambia de estado a medida que avanza:

| Estado | Qué significa |
|---|---|
| **Borrador** | Lo estás armando. |
| **Presupuesto** | Listo para el cliente. |
| **Señado** | El cliente pagó la seña. **Pasa solo** cuando se cobra la primera cuota del plan de pagos. |
| **Emitido** | Viaje confirmado y documentación emitida. |
| **Cancelado** | Se cayó. |

Cada expediente tiene un **número automático** (por ejemplo `LPN-2026-00001`). El prefijo se cambia en *Configuración → Finanzas*.

![Listado de presupuestos y expedientes](imagenes/05-expedientes.png)


### Armar un expediente

1. **Información general:** cliente, vendedor, consulta de origen (copia destino, pasajeros, noches, fecha), titular, fechas y vencimiento del presupuesto.
2. **Pasajeros:** nombre, documento, nacimiento, nacionalidad y foto del documento de cada viajero.
3. **Detalle de servicios:** cada línea es un servicio (vuelo, hotel, traslado…):
   - tipo de servicio y descripción,
   - **proveedor**,
   - **moneda** (USD, ARS, EUR, BRL),
   - si no es USD: la **cotización** (sección 7),
   - **costo neto** (lo que pagás) y **precio de venta** (lo que paga el cliente).
4. **Liquidación:** el sistema calcula solo **costo total, precio total y rentabilidad en USD**. Si cargaste servicios en pesos, también ves el subtotal en pesos como referencia.
5. **Información adicional:** notas para el pasajero y documentos (vouchers) que el cliente podrá descargar.
6. **Itinerario:** día por día.

> **Observaciones internas** nunca se ven en el presupuesto ni en el link público.

![Detalle de servicios del expediente](imagenes/07-expediente-servicios.png)


### Qué podés hacer con un expediente

Botones arriba a la derecha:

| Botón | Qué hace |
|---|---|
| **Enviar por Email** | Manda el presupuesto en PDF al correo del cliente. ⚙️ *Requiere correo saliente configurado.* |
| **Enviar por WhatsApp** | Abre WhatsApp con el mensaje y el link listos. |
| **Presupuesto (PDF)** | Descarga el PDF (con el plan de pagos si existe). |
| **Acuerdo (PDF)** | Genera el contrato con los datos del cliente. |
| **Ver Link Público** | Muestra el link privado del presupuesto para copiar y compartir. |
| **Renovar validez** | Aparece si el presupuesto venció: lo deja vigente 7 días más. |

En la parte de abajo del expediente hay pestañas: **Plan de Pagos**, **Movimientos de Caja**, **Itinerario del Viaje** y **Historial de Actividad** (el historial: creación, cambios de estado, envíos y *"el cliente abrió el presupuesto"*).

![Un expediente: botones de acción, saldo del cliente y datos generales](imagenes/06-expediente-arriba.png)


### El link público del presupuesto

Cada presupuesto tiene una página privada (que solo abre quien tiene el link) con el detalle del viaje, el precio total, el botón para escribirte por WhatsApp, la descarga del PDF y —si hay plan de pagos— **"Tu plan de pagos"** con las cuotas, su estado, el saldo y cómo pagar.

**No muestra costos, rentabilidad ni observaciones internas.** El sistema cuenta cuántas veces lo abre el cliente (no cuentan tus visitas ni las de robots), y eso alimenta el seguimiento automático.

![Página pública del presupuesto, con el plan de pagos del cliente](imagenes/29-link-publico.png)


## 7. Moneda y cotización

**Regla general: los expedientes se llevan siempre en dólares (USD).** Eso hace que los saldos y la rentabilidad sean claros aunque el peso se mueva.

Cuando un servicio, un cobro o un pago es en otra moneda, el sistema pide la **cotización**:

1. Elegí la moneda (por ejemplo, Pesos Argentinos).
2. En **Cotización** aparece un selector con el valor del día de cada dólar:
   - **Dólar Oficial**, **Dólar MEP**, **Dólar Tarjeta**,
   - **Personalizado**: escribís vos el número.
3. Al elegir una, se completa el campo "**ARS por 1 USD**". También lo podés editar a mano (y pasa a *Personalizado*).
4. El sistema muestra a cuánto equivale en USD.

Detalles útiles:

- La cotización queda **guardada en cada servicio y en cada cobro**: el valor de un cobro de hace dos meses no cambia.
- El dólar que viene marcado por defecto se elige en *Configuración → Finanzas*.
- Si el sistema no puede obtener la cotización (sin internet o sin datos), **no deja guardar** hasta que la escribas: nunca convierte pesos como si fueran dólares.
- Euro y real se convierten pasando por el dólar del día.

![Servicio en pesos: selector de cotización y conversión a USD](imagenes/08b-servicio-en-pesos-cotizacion.png)


## 8. Plan de pagos y cuotas

*Dentro de un expediente → pestaña **Plan de Pagos***

### Generar el plan

Botón **Generar plan**. Completás:

- **Seña (%)** (por ejemplo 30),
- **Cantidad de cuotas** del saldo,
- **Primer vencimiento** de cuota,
- **Frecuencia** (semanal, quincenal, mensual).

El sistema crea la **Seña** (vence hoy) y las cuotas en USD. Los centavos que sobran del redondeo van a la última cuota.

**Regla de seguridad:** la última cuota debe vencer al menos **N días antes del viaje** (30 por defecto; se cambia en *Configuración → Finanzas*). Si el plan no cumple, el sistema te explica la fecha límite.

![Generar el plan de pagos](imagenes/09c-generar-plan.png)


Mientras no haya cobros, podés volver a generar el plan; con cobros registrados ya no (para no perder el rastro).

![Plan de pagos con cuotas pagadas, parciales y pendientes](imagenes/09-plan-de-pagos.png)


### Registrar un pago

En la fila de la cuota: **Registrar pago**. Elegís moneda (USD o pesos), monto, cotización si es en pesos, método y fecha. Se crea el cobro en la Caja y la cuota se actualiza sola.

También podés cargar el cobro directamente desde *Caja* y elegir **Imputar a cuota**. Si no elegís ninguna, el cobro se aplica a la **cuota más antigua pendiente**.

![Registrar el pago de una cuota](imagenes/10-registrar-pago.png)


### Estados de la cuota

| Estado | Cuándo |
|---|---|
| **Pendiente** | Todavía no se cobró nada. |
| **Pago parcial** | Se cobró una parte. |
| **Pagada** | Cubierta por completo. |
| **Vencida** | Pasó la fecha y falta cobrar. |

Al **cobrar la seña**, el expediente pasa solo a **Señado**. Si borrás o corregís un cobro, las cuotas se recalculan.

### Recordatorios automáticos

Todos los días a las 9:00 el sistema avisa:

- **3 días antes** del vencimiento,
- **el día** del vencimiento,
- **2 días después** si sigue impaga.

El cliente recibe un **email** (⚙️ requiere correo configurado) y, si la agencia conectó WhatsApp y cargó la plantilla de recordatorio, también un **WhatsApp**. El vendedor recibe una **notificación** en el panel. Cada aviso se manda una sola vez. Los textos se personalizan en *Configuración → Finanzas → Plan de pagos y recordatorios*.

## 9. Caja: cobros y pagos

*Menú: **Tesorería → Caja***

Cada movimiento de dinero es una **transacción**:

- **Cobro (ingreso):** el cliente te paga.
- **Pago (egreso):** vos pagás a un proveedor, un gasto, un sueldo.

![La Caja: listado de movimientos](imagenes/12-caja.png)


### Cargar un movimiento

1. **Tipo**, **método de pago** (efectivo, transferencia, tarjeta, Mercado Pago, USDT, cheque) y **cuenta financiera**.
2. **Categoría** (Pago de Pasajero, Pago a Proveedor, Gastos Administrativos, Publicidad…; podés crear otras).
3. **Expediente** (para cobros y pagos de un viaje) y **proveedor** (para pagos).
4. **Moneda y monto**. Si es en pesos, elegí la **cotización del día** (sección 7): ves en vivo *"Equivale a USD …"*.
5. **Fecha** del movimiento, notas y comprobante (foto o PDF).
6. Guardar.

![Formulario de nuevo movimiento](imagenes/13-caja-nuevo.png)


La **calculadora de neto e impuestos** (arriba, plegada) sirve cuando el medio de pago descuenta comisiones: cargás el monto bruto y los porcentajes (banco, IIBB, plataforma) y calcula lo que realmente recibís.

### Recibo

Desde un cobro podés descargar el **recibo en PDF** (muestra el método, la cotización y a qué cuota se imputó).

### Saldo del cliente

En cada expediente, arriba, ves **Total del viaje · Total pagado · Saldo adeudado**, todo en USD. Los cobros en pesos suman lo que valían el día que se pagaron.

## 10. Proveedores, cuentas y categorías

*Menú: **Sistema***

- **Proveedores:** hoteles, aerolíneas, mayoristas. Cada uno muestra el **saldo en USD** (lo que le debés: costos de los servicios que le cargaste menos lo que ya le pagaste). Guardan datos bancarios.
- **Cuentas:** tus "cajas" (caja fuerte, banco, Mercado Pago…), cada una con su moneda.
- **Categorías:** para ordenar ingresos y egresos.
- **Tipos de Servicio:** la lista de servicios que aparece al armar un expediente (vuelo, hotel, traslado…).

![Proveedores con su saldo en USD](imagenes/26-proveedores.png)


## 11. Seguimiento automático y tareas

*Menú: **Sistema → Seguimiento automático** (reglas) y **Gestión → Tareas***

Para que ninguna consulta ni presupuesto quede sin respuesta, el sistema revisa **cada hora** y actúa según **reglas** que vos podés prender, apagar y ajustar.

Las reglas que vienen listas:

| Cuándo | Espera | Qué hace |
|---|---|---|
| Consulta nueva **sin vendedor** | 2 h | Avisa a los administradores. |
| Consulta **sin contactar** | 24 h | Crea una tarea para el vendedor. |
| La persona **pide hablar con alguien** | enseguida | Crea una tarea urgente. |
| Presupuesto enviado y **no abierto** | 48 h | Tarea "Llamar a…". |
| Presupuesto **abierto 3 veces o más** y sin seña | 24 h | Tarea "Cerrar la venta con…". |
| Presupuesto **vencido** | enseguida | Avisa al vendedor (ahí podés **Renovar validez**). |

Cada regla se dispara **una sola vez** por consulta o presupuesto, así que no vas a recibir avisos repetidos. En la pantalla de reglas podés cambiar las horas, elegir si crea una tarea o solo avisa, y apagarlas.

![Reglas de seguimiento automático](imagenes/15-seguimiento-reglas.png)


**Tareas:** tu agenda. Tienen título, descripción, fecha y hora, y se asocian a la consulta o al expediente. Las que crea el sistema aparecen ahí con vencimiento de hoy; marcalas como completadas al terminar.

Además, todos los días a las 9:00 recibís avisos de tareas vencidas y de presupuestos por vencer.

![Listado de tareas](imagenes/16-tareas.png)


## 12. Bandeja de WhatsApp

*Menú: **Clientes → Bandeja de WhatsApp*** ⚙️ *Requiere conectar WhatsApp Business (Cloud API de Meta)*

Los mensajes que tus clientes mandan al número de la agencia entran al sistema como consultas y se ven en esta pantalla, con las conversaciones a la izquierda y el chat a la derecha.

**Cómo funciona**

1. Llega un mensaje: el sistema reconoce o crea al cliente (por su teléfono, en cualquier formato) y abre una consulta con origen *whatsapp*.
2. Si todavía no lo atiende nadie, el **asistente de IA responde** y hace las preguntas clave (destino, fechas, ciudad de salida, pasajeros, presupuesto).
3. Cuando la IA termina de calificar, **o el cliente pide hablar con una persona**, la IA **deja de responder**, la consulta pasa a "atención requerida" y se avisa al vendedor (o a los administradores).
4. Desde la bandeja respondés vos. **Al responder, la IA se pausa sola** en esa conversación y la consulta pasa a *Contactado*.

![Bandeja de WhatsApp: conversaciones a la izquierda, chat a la derecha](imagenes/17-bandeja-whatsapp.png)


**En la pantalla**

- Filtros: **Todas · No leídas · Mías · Urgentes**. El menú muestra un globito con las no leídas y la pantalla se actualiza sola cada pocos segundos.
- **Pausar IA / Reanudar IA** y **Ver lead**.
- Etiquetas de cada mensaje: enviado, entregado, leído o fallido.

**La regla de las 24 horas (de WhatsApp, no nuestra)**

Podés escribir libremente solo hasta **24 horas después del último mensaje del cliente**. Pasado ese tiempo, WhatsApp solo deja enviar **plantillas aprobadas**: la pantalla te muestra un selector de plantilla (por ejemplo "seguimiento de viaje") y los campos para completarla.

Las plantillas se cargan en *Sistema → Plantillas de WhatsApp* con el nombre exacto que aprobó Meta.

![Plantillas de WhatsApp aprobadas por Meta](imagenes/19-plantillas-whatsapp.png)


## 13. Ideas de Viaje y el Importador

### Ideas de Viaje (el catálogo)

*Menú: **Catálogo → Ideas de Viaje***

Son los paquetes que se muestran en la web. Cada idea tiene pestañas:

| Pestaña | Para qué |
|---|---|
| **Idea Rápida** | Título, destino, noches, precio desde, moneda, base de precio, etiquetas, resumen, imagen de portada (obligatoria) y el interruptor **Activa** (publicada o no). |
| **Políticas y Precios** | Suplemento single, reducción en triple, políticas de niños, temporadas con precio. |
| **Descripción Extendida** | Texto largo. |
| **Itinerario** | Día por día (podés pedirle un borrador a la IA). |
| **Costos y Servicios** | Servicios con proveedor, costo y venta, para armar presupuestos rápido. |
| **Imágenes** | Galería. |
| **Incluye / No Incluye** | Una línea por ítem. |

Desde la lista de ideas podés **generar un presupuesto** para un cliente a partir de la idea.

![Listado de Ideas de Viaje](imagenes/20-ideas-de-viaje.png)


### Importar una idea desde un link, texto o PDF

*Menú: **Catálogo → Importar Ideas de Viaje*** ⚙️ *Requiere la IA configurada*

Sirve para no tipear: le pasás la oferta de un operador y arma un **borrador** que vos revisás.

1. **Nueva importación** y elegí la fuente: **un link**, **texto pegado** o **un PDF** (hasta 10 MB; los PDF escaneados también sirven).

![Nueva importación: elegir la fuente](imagenes/21-importar-nueva.png)

2. Si es un link, podés tildar *"Tengo derecho a usar las imágenes de esta fuente"*. Si no lo tildás, las fotos de la página original no se usan.
3. Apretá **Importar**. En menos de un minuto (puede tardar un poco más si es largo) pasa a *Listo para revisar*.
4. En la pantalla de revisión ves:
   - los **avisos** (por ejemplo "la fuente no indicaba un precio" o "ya existe una idea parecida"),
   - los datos que la IA **no tenía claros** (hay que revisarlos con atención),
   - el **borrador** (título, destino, noches, precio, resumen, cantidad de días de itinerario),
   - el **texto original** para comparar,
   - las **fotos sugeridas**.
5. **Fotos:** son **reales, con licencia libre**, de Unsplash, Pexels y Wikimedia Commons (nunca generadas por IA ni sacadas de Google). Marcás las que querés, elegís la **portada** y apretás *Usar las imágenes elegidas*. Se guarda el **crédito del autor** y se muestra en la página del paquete.
6. **Editar borrador** para corregir lo que haga falta (es la misma pantalla de Ideas de Viaje).
7. **Publicar** (pide confirmación). Para publicar tiene que tener **portada, precio y resumen**.
8. Si no sirve: **Descartar** (elimina el borrador).

![Pantalla de revisión de una idea importada](imagenes/23-importar-revision.png)


**Qué hace la IA y qué no**

- **No inventa datos:** lo que no figura en la fuente queda vacío.
- Reescribe los textos con la voz de tu agencia ("vos", tono cálido).
- **Quita el nombre, teléfono y datos del operador de origen.**
- No completa costos ni proveedores: eso lo cargás vos.

> Siempre revisá antes de publicar: la IA puede equivocarse, y vos sos quien firma la propuesta.

## 14. La vidriera web y sus formularios

*Menú: **Catálogo → Sliders** (banners) y **Configuración → Marca Blanca** (todo lo visual)*

Lo que ve el público:

- **Inicio** con banners (sliders) y formulario de consulta.

![Inicio del sitio público](imagenes/31-inicio-publico.png)

- **Ideas de Viaje** (el catálogo), con el detalle de cada paquete y su formulario.
- **Chat** con el asistente virtual ⚙️.
- **Cotizador** (/cotizar): la persona elige destino, fechas, pasajeros y deja sus datos en 3 pasos.
- **Página de presupuesto** (la del link privado).
- Páginas de quiénes somos, privacidad y cookies.

Cualquier consulta que llega por estos medios aparece en **Consultas**. Para evitar abusos, el chat limita la cantidad de mensajes por minuto y los formularios tienen una trampa invisible contra robots.

![El cotizador público](imagenes/30-cotizador.png)


## 15. Portal del cliente

Los clientes pueden entrar a su propio espacio (dirección `/portal`) para ver sus viajes y consultas. Como las cuentas se crean sin contraseña conocida, la primera vez deben usar **"¿Olvidaste tu contraseña?"** para crear la suya por email. ⚙️ *Requiere correo saliente.*

## 16. Configuración de la agencia

*Menú: **Configuración → Marca Blanca*** (solo administradores)

| Pestaña | Qué configurás |
|---|---|
| **Identidad y Frontend** | Nombre de la empresa, logo e isotipo, **nombre del asistente virtual**, **proveedor de IA y su clave**, colores del sitio público, SEO. |
| **Panel de Control (Backend)** | Colores del panel de administración. |
| **Contacto y Redes** | Email, teléfono, dirección, mapa, redes sociales (los links de WhatsApp también se usan en el chat). |
| **Analítica y Scripts** | Códigos externos (Google Analytics, píxeles). |
| **Legal y Acuerdos** | La plantilla del contrato. Variables automáticas: `[NOMBRE_CLIENTE]`, `[DNI_CLIENTE]`, `[EMAIL_CLIENTE]`, `[TELEFONO_CLIENTE]`, `[TOTAL_VIAJE]`, `[DESTINO]`, `[FECHA_VIAJE]`. |
| **Finanzas** | **Dólar por defecto**, **prefijo** de los números de expediente, **asignación automática** de consultas, días mínimos entre la última cuota y el viaje, **cómo pagar** (datos bancarios que ve el cliente) y los **textos de los recordatorios** (`[NOMBRE]`, `[CUOTA]`, `[MONTO]`, `[VENCIMIENTO]`, `[EXPEDIENTE]`). |
| **Integraciones** | Claves gratuitas de **Unsplash** y **Pexels** para las fotos del importador. |
| **Mantenimiento** | Modo mantenimiento (oculta el sitio al público; el panel sigue funcionando). |

![Configuración → Integraciones](imagenes/24c-configuracion-integraciones.png)


Recordá apretar **Guardar Cambios** (arriba a la derecha).

![Configuración → pestaña Finanzas](imagenes/24b-configuracion-finanzas.png)


## 17. Reportes

- **Tesorería → Reportes Financieros** *(Finanzas y administradores)*: balance anual mes a mes (ingresos, egresos, resultado) **en USD** y descarga en PDF. Se calcula con la **fecha real del movimiento**.

![Reportes financieros](imagenes/14-reportes-financieros.png)

- **Reportes → Analytics & Funnel:** embudo de consultas, origen de las consultas, productos más vendidos.

![Analytics & Funnel](imagenes/28-analytics.png)

- **Escritorio:** KPI, cobranzas y velocidad de respuesta (sección 3).

## 18. Glosario y preguntas frecuentes

**Lead / Consulta:** persona interesada que todavía no compró.
**Expediente / File:** el viaje de un cliente (desde presupuesto hasta emitido).
**Seña:** primer pago que "reserva" el viaje.
**Cuota:** cada pago del plan.
**Imputar:** decidir a qué cuota corresponde un cobro.
**Cotización:** cuántos pesos vale un dólar en el momento de la operación.
**Ventana de 24 horas:** plazo de WhatsApp para responder libremente.
**Plantilla de WhatsApp:** mensaje pre-aprobado por Meta para escribir fuera de la ventana.

**¿Por qué el total de un viaje en pesos cambió respecto de antes?**
Porque ahora cada servicio en pesos se convierte a dólares con su cotización. Antes un servicio en pesos podía sumarse como si fueran dólares.

**¿Puedo cargar un cobro en pesos si el viaje está en dólares?**
Sí. Elegí pesos y la cotización del día: el sistema calcula cuántos dólares cubre.

**¿Qué pasa si el cliente paga más que la cuota?**
El excedente se aplica a las siguientes cuotas.

**¿Por qué no me llegan los mails o las respuestas automáticas al instante?**
Salen en una cola que se procesa cada minuto. Si pasan varios minutos, revisá el aviso del cron en el Escritorio.

**La IA respondió algo incorrecto.**
Corregilo desde la bandeja o el chat de la consulta y pausá la IA en esa conversación. Podés ajustar el nombre y el proveedor en *Configuración*.

**¿Cómo evito que se mezclen clientes?**
El sistema identifica por email o teléfono. Si alguien escribe sin datos, queda como una consulta aparte que podés completar a mano.
