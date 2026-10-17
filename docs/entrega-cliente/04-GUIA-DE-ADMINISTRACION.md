# Guía de administración

Para la persona que deja el sistema instalado y funcionando (o que habla con el hosting). Usa lenguaje simple, pero algunas tareas se hacen en el panel del hosting (cPanel o hPanel), no en el sistema.

**Índice**

1. Requisitos del hosting
2. El "cron": lo más importante para que todo funcione solo
3. Revisar que todo esté bien (`omni:doctor` y Server Admin)
4. Correo saliente
5. Inteligencia artificial
6. Fotos para el importador
7. WhatsApp Business (Cloud API)
8. Datos que ya existían: recálculo de totales
9. Copias de seguridad
10. Actualizaciones
11. Seguridad

---

## 1. Requisitos del hosting

- **PHP 8.4** (se elige en el panel del hosting) con las extensiones habituales: mbstring, gd, zip, curl, pdo_mysql, fileinfo.
- **MySQL/MariaDB**.
- Poder crear **una tarea programada (cron)**. Está disponible en cPanel (*Avanzado → Trabajos de Cron*) y hPanel (*Avanzado → Trabajos Cron*).
- Un **certificado SSL** (https). Es indispensable para WhatsApp.
- No hace falta Redis ni procesos permanentes: el sistema funciona en hosting compartido.

## 2. El "cron": lo más importante para que todo funcione solo

Los **mails, recordatorios de cuotas, seguimientos automáticos, respuestas de IA y la importación de ideas** se procesan en segundo plano. Para eso el hosting tiene que "despertar" al sistema **cada minuto**.

**Cómo crearlo (una sola vez):**

1. En el panel del hosting, entrá a *Trabajos de Cron* y creá una tarea **cada minuto** (`* * * * *`).
2. En el comando poné:
   ```
   cd /RUTA/AL/PROYECTO && /RUTA/A/PHP8.4 artisan schedule:run >> /dev/null 2>&1
   ```
   Ejemplos de la ruta de PHP según hosting: `/usr/local/bin/php` o `/opt/cpanel/ea-php84/root/usr/bin/php` (cPanel) y `/opt/alt/php84/usr/bin/php` (hPanel). Si no estás seguro, preguntale al soporte del hosting "¿cuál es la ruta del PHP 8.4 para cron?".
3. Esperá 2 o 3 minutos.

**Cómo saber que funciona:** entrá al panel de administración. Si aparece el aviso rojo **"El cron no está corriendo"** (con el comando exacto para copiar), todavía no está andando. Si no aparece, está bien.

**Importante:** con este cron, lo que sale "en segundo plano" tarda **hasta 1 minuto** (por ejemplo, una respuesta automática de WhatsApp). Es normal.

## 3. Revisar que todo esté bien

### Desde el sistema (sin terminal)

*Menú **Sistema → Server Admin*** ofrece botones para:

- **Create Missing Directories** (crea carpetas que falten),
- **Fix Permissions** (permisos de carpetas),
- **Run Migrations** (aplica actualizaciones de la base de datos; hacé un backup antes),
- **Run Storage Link** (publica las imágenes subidas).

![Sistema → Server Admin](imagenes/27-server-admin.png)


### Con terminal (SSH), si el hosting lo permite

```
php artisan omni:doctor
```

Revisa: versión de PHP, extensiones, permisos, base de datos, enlace de imágenes, cola, **cron activo**, trabajos fallidos y si hay cotización del dólar. Cada punto con problema trae la solución.

Si tu hosting **no tiene terminal**, podés correr un comando creando un cron temporal de una sola corrida (y borrándolo después).

## 4. Correo saliente

Los mails (confirmación de consulta, presupuesto, recordatorios de pago, recuperar contraseña) necesitan una cuenta de correo SMTP. Se configura en el archivo `.env` del servidor (líneas `MAIL_MAILER`, `MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, `MAIL_FROM_ADDRESS`).

Recomendación: usar una casilla de la propia agencia (por ejemplo `no-responder@tuagencia.com`) y pedirle al hosting que configure **SPF y DKIM** para que los mails no caigan en spam.

Prueba rápida: enviá un presupuesto por email a tu propia casilla.

## 5. Inteligencia artificial

La IA se usa para: el **chat del sitio**, la **calificación de consultas** (completar destino, fechas, presupuesto…), las **respuestas por WhatsApp**, el **borrador de itinerario** y el **importador de ideas**.

**Configuración:** *Configuración → Marca Blanca → Identidad y Frontend* → elegí el **proveedor** (Gemini u OpenAI), pegá la **clave de API** y ponele nombre al asistente.

![Configuración → Identidad y Frontend](imagenes/24a-configuracion-identidad.png)


Cómo conseguir la clave: en la consola del proveedor elegido (Google AI Studio para Gemini; platform.openai.com para OpenAI). El uso de la IA **tiene un costo** que cobra el proveedor según el consumo; el sistema limita la cantidad de mensajes por minuto para evitar abusos.

Sin clave, el chat sigue funcionando con preguntas guiadas simples, pero sin inteligencia, y el importador de ideas no funciona.

## 6. Fotos para el importador

*Configuración → Marca Blanca → Integraciones.*

- **Wikimedia Commons** no necesita clave.
- **Unsplash**: creá una cuenta de desarrollador gratuita (unsplash.com/developers), registrá una aplicación y pegá la *Access Key*.
- **Pexels**: pedí la clave gratuita en pexels.com/api y pegala.

Las fotos se descargan al sistema, y se guarda el crédito del autor.

![Configuración → Integraciones](imagenes/24c-configuracion-integraciones.png)


## 7. WhatsApp Business (Cloud API)

Es la conexión **oficial** con WhatsApp. Es la parte que más trámites tiene; conviene empezarla con anticipación.

**Qué necesitás**

- Una cuenta de **Meta Business** verificada.
- Una app de Meta con el producto **WhatsApp** y un **número de teléfono** dedicado (no puede estar usándose en la app de WhatsApp común).
- Plantillas de mensajes aprobadas por Meta (para escribir pasadas las 24 horas y para los recordatorios de pago).

**Pasos**

1. En el panel de Meta (developers.facebook.com) anotá: **Phone number ID**, un **token de acceso permanente**, y el **App Secret** de la app.
2. Inventá una frase secreta como **token de verificación** (cualquier texto largo).
3. En el `.env` del servidor cargá:
   ```
   WHATSAPP_PHONE_NUMBER_ID=...
   WHATSAPP_ACCESS_TOKEN=...
   WHATSAPP_APP_SECRET=...
   WHATSAPP_VERIFY_TOKEN=...   (la frase que inventaste)
   ```
4. En Meta, en *WhatsApp → Configuración → Webhook*, pegá la URL `https://TU-DOMINIO/webhooks/whatsapp` y el mismo token de verificación. Suscribite al campo **messages**.
5. En el sistema, *Sistema → Plantillas de WhatsApp*: cargá cada plantilla aprobada con su nombre **exacto**, idioma, texto y cantidad de variables. Para los recordatorios de cuotas, usá el uso *"Recordatorio de cuota"* con 4 variables, en este orden: **nombre, cuota, monto, vencimiento**.

![Plantillas de WhatsApp](imagenes/19-plantillas-whatsapp.png)

6. Probá: escribí al número desde tu celular. Deberías ver la conversación en *Clientes → Bandeja de WhatsApp*.

Los datos secretos (token, secret) **solo** van en el `.env`, nunca en pantallas del sistema.

## 8. Datos que ya existían: recálculo de totales

Si el sistema ya se usaba antes de esta versión, los expedientes viejos pueden tener servicios en pesos contados como si fueran dólares. Hay un procedimiento seguro para corregirlo (con simulación previa y backup), que conviene coordinar con quien instala el sistema. Resumen:

1. Backup de la base de datos.
2. **Simulación** (no cambia nada): `php artisan omni:recalculate-bookings` y revisar el archivo CSV que genera.
3. Para los servicios en pesos sin cotización, decidir si se carga la cotización histórica a mano o se usa la del día (`--estimate-rates=oficial`).
4. **Aplicar:** `php artisan omni:recalculate-bookings --apply`.
5. Si hay consultas anónimas que quedaron mezcladas en un mismo cliente: `php artisan omni:split-anonymous-customers` (simulación) y luego `--apply`.
6. Antes de dejar corriendo el seguimiento automático por primera vez: `php artisan omni:run-follow-ups --baseline` (marca lo viejo como "ya atendido" para no llenar al equipo de tareas por consultas antiguas).

## 9. Copias de seguridad

- **Base de datos:** exportación completa desde phpMyAdmin (o la herramienta de backup del hosting) **antes** de cada actualización y, como mínimo, una vez por semana.
- **Archivos subidos:** la carpeta `public/uploads` (imágenes) y `storage/app` (documentos, vouchers).
- Guardá las copias **fuera** del hosting (tu computadora o un servicio en la nube).

## 10. Actualizaciones

1. Hacer backup (sección 9).
2. Poner el sistema en **modo mantenimiento** (*Configuración → Mantenimiento*).
3. Subir el paquete nuevo y descomprimirlo encima, **sin tocar `.env`**.
4. Aplicar las migraciones (*Server Admin → Run Migrations*).
5. Si el panel se ve raro, borrar los archivos de `bootstrap/cache/`.
6. Desactivar el modo mantenimiento y revisar el aviso del cron.

## 11. Seguridad

- Cambiá las contraseñas iniciales y usá contraseñas largas y únicas.
- Dale a cada persona el **rol mínimo** que necesita (el dinero y la rentabilidad solo para Finanzas y administradores).
- Mantené `APP_DEBUG=false` en producción.
- No compartas el `.env` ni las claves de IA / WhatsApp por mail o chat.
- Los formularios públicos y el chat tienen protecciones contra robots y límites de uso; el webhook de WhatsApp rechaza todo lo que no venga firmado por Meta.
