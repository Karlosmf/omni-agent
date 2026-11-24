# Pendientes de Omni-Agent

Actualizado: 24/11/2026. Marcá cada ítem con `[x]` cuando esté hecho.

## Urgente (seguridad)

- [ ] **Cambiar las contraseñas de Hostinger** que se escribieron en el chat: la de SSH/hPanel y la del FTP. Desde hPanel → Avanzado → Acceso SSH y Archivos → Cuentas FTP. El acceso por clave SSH sigue funcionando después del cambio.
- [x] **Agregar `.env.dusk.local` al `.gitignore`**: tiene credenciales y hoy está sin seguimiento, a un `git add .` de subirse al repositorio.

## Código y repositorio

- [ ] **Abrir el PR de `feat/luopan-channels` a `main`** y mergearlo cuando esté revisado.
- [ ] **Correr la suite completa** (`php artisan test --compact`) antes del merge.
- [ ] **Decidir qué hacer con los archivos sin seguimiento**: los videos de `docs/entrega-cliente/videos` (82 MB, conviene subirlos a Drive o YouTube en vez de al repo), las carpetas de herramientas de IA (`.claude`, `.codex`, `.gemini/skills`, `.opencode`), `md2html.php` y `github-markdown.min.css`.
- [ ] **Motivo de las consultas perdidas**: se está agregando en la sesión de estadísticas (campo `lost_reason`). Probarlo en el panel cuando termine.
- [ ] **Definir qué cuenta como "Concretada"**: hoy es una consulta marcada "Reservada" o con una venta señada o emitida. "Viajes Vendidos" cuenta expedientes. Si preferís que coincidan, que "Concretadas" cuente solo las que tienen venta real.
- [ ] **Instagram por Zernio**: la app recibe Instagram solo con una cuenta propia de Meta. La cuenta @f_carlitos conectada en Zernio no llega al panel hasta que se agregue ese camino.

## Demo (luopan.innodesigntest.com)

- [ ] **Cargar una clave de IA** (Gemini, OpenAI, etc.) en Ajustes → IA: sin ella no responden el bot recepcionista ni la importación de paquetes.
- [ ] **Configurar el correo (SMTP)**: hoy `MAIL_MAILER=log`, no sale ningún email (avisos, presupuestos, recuperación de contraseña).
- [ ] **Decidir el modo prueba de WhatsApp**: solo atiende a `+5493482644107` y `+5493482518081`. Para atender a todos, vaciar `ZERNIO_TEST_PHONES` en el `.env` del servidor.
- [ ] **Cambiar el usuario demo** (`demo@luopan.innodesigntest.com`) por uno propio, o crear un usuario vendedor para mostrar la demo con permisos limitados.
- [ ] **Borrar el respaldo de la app anterior** cuando ya no haga falta: `~/backups/luopan-2026-11-20` y `luopan-2026-11-20.tar.gz` en el servidor.
- [ ] **El cron de Hostinger no se ejecuta.** Hay 3 trabajos cron creados en la cuenta y ninguno corrió, ni siquiera uno de prueba que solo escribe la fecha. Revisarlo con el soporte de Hostinger (hPanel → Avanzado → Trabajos Cron). Mientras tanto, la alternativa es un cron externo gratuito (cron-job.org) que abra cada minuto `https://luopan.innodesigntest.com/cron/run?token=…` con el `CRON_TOKEN` del `.env`.
- [ ] **Limpiar los crons duplicados** en hPanel: dejar solo el que escribe en `storage/logs/cron.log` y borrar el anterior (`… schedule:run >> /dev/null`) y el de prueba (`/bin/date >> ~/cron-test.log`).

## Instalaciones de clientes

- [ ] **DonWeb y HostRentable**: confirmar que tienen el cron de cada minuto (o el cron externo con `CRON_TOKEN`). Sin cron no corren seguimientos, recordatorios de cuotas ni la cola.
- [ ] **Actualizarlas a esta versión** después del merge, con un respaldo de la base antes, porque tienen datos reales.
- [ ] **Hostinger**: `omni:install` ya no se corta si el hosting no deja crear el enlace de storage, pero ese enlace hay que hacerlo a mano (el instalador muestra el comando).

## Para hablar con tu socio (nexia)

- [ ] `TextToSql` corre sobre mensajes de clientes y puede filtrar datos de `ordenes_compra`.
- [ ] El webhook acepta cualquier pedido cuando no hay secreto configurado.
- [ ] El reintento del job puede mandar la misma respuesta dos veces.
- [ ] `messages.external_message_id` no es único.
- [ ] Los drivers de Meta y Twilio solo envían (no reciben) y usan el id de conversación de Zernio como destinatario.

## Ideas que quedaron afuera (a decidir)

- [ ] Respuestas automáticas fuera de horario (nexia las tiene; acá el bot atiende las 24 horas).
- [ ] Marcar como leídos los mensajes de Instagram y Messenger cuando Meta avisa la lectura (hoy solo se registra "entregado").
- [x] Manual de uso en PDF por área, con capturas: `docs/manual/Manual-de-uso.pdf` (se regenera desde `docs/manual/manual-de-uso.html`).
- [ ] En la ficha de un expediente, los 6 botones de arriba aprietan el título ("Editar LP-1993-184" queda en 4 renglones). Agrupar los menos usados en un menú "Más".
- [ ] Datos de ejemplo de la demo: los destinos de las consultas sembradas son nombres de ciudades inventados ("Don Martín"); conviene sembrar destinos reales para las presentaciones.
