# Instalación de Omni-Agent para un cliente nuevo

Una instalación por cliente: su hosting, su base de datos, su dominio y su propia app de Meta. No hay que tocar código.

## 1. Requisitos del hosting
- PHP 8.4 con las extensiones habituales de Laravel (`omni:doctor` las revisa).
- MySQL/MariaDB.
- Poder apuntar el dominio a la carpeta `public/`.
- Cron cada minuto **o** un servicio externo de cron (ver paso 6).

## 2. Subir los archivos
1. Generá el paquete con `php artisan omni:make-deploy-zip` (incluye `vendor/` y los assets ya compilados) o subí el proyecto completo.
2. Descomprimilo fuera de `public_html` si el hosting lo permite, y apuntá el dominio a la carpeta `public/`. Si no se puede, copiá el contenido de `public/` a `public_html` y ajustá las rutas de `index.php`.
3. Permisos de escritura en `storage/` y `bootstrap/cache/`.

## 3. Configurar el `.env`
1. Copiá `.env.example` como `.env` y completá cada sección (marca, base de datos, correo, Meta).
2. `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://dominio-del-cliente`.
3. `php artisan key:generate`.

## 4. Instalar
```bash
php artisan omni:install
php artisan omni:check-config
```
`omni:install` es repetible: corre las migraciones, carga los datos base sin pisar nada, crea el enlace de `storage` y el primer administrador (te pide email y contraseña). Para datos de prueba, solo en local: `php artisan db:seed --class=DemoSeeder`.

## 5. Marca
Los valores `BRAND_*` se cargan una sola vez. Después el cliente cambia nombre, logos, colores y redes desde el panel: **Ajustes de la agencia**.

## 6. Tareas automáticas (seguimientos, recordatorios, cola)
- **Con cron del hosting:** `* * * * * cd /ruta/del/proyecto && php artisan schedule:run >> /dev/null 2>&1`
- **Sin cron:** definí `CRON_TOKEN` en el `.env` y pedile a un servicio externo gratuito (por ejemplo cron-job.org) que abra cada minuto `https://dominio/cron/run?token=TU_TOKEN`. Sin token configurado esa dirección no existe.

## 7. Meta (WhatsApp, Instagram, Messenger)
Checklist por cliente nuevo:
1. Crear el *business portfolio* del cliente en Meta y verificar el negocio.
2. Crear la app de Meta (tipo Empresa) y agregar los productos WhatsApp, Messenger e Instagram.
3. WhatsApp: conectar el número en modo coexistencia (el dueño sigue usando la app del celular).
4. Vincular la página de Facebook y la cuenta profesional de Instagram.
5. Cargar las claves en el panel (**Sistema → Canales de mensajería**) o en el `.env` (`META_*`, `WHATSAPP_*`).
6. Registrar el webhook en Meta:
   - Instagram y Messenger: `https://dominio/webhooks/meta` (si definiste `META_WEBHOOK_TOKEN`, terminá la dirección con `/ese-valor`).
   - WhatsApp: `https://dominio/webhooks/whatsapp`.
   - Token de verificación: el mismo que cargaste en la cuenta.
   - Campos: WhatsApp `messages`; Messenger `messages`, `messaging_postbacks`, `message_deliveries`, `message_reads`; Instagram `messages`.
7. Permisos: `whatsapp_business_management`, `whatsapp_business_messaging`, `pages_messaging`, `pages_manage_metadata`, `instagram_basic`, `instagram_manage_messages`.
8. Correr `php artisan omni:check-config`: confirma con Meta que cada token funciona.
9. Mandar un mensaje de prueba a cada canal y ver que aparece en la bandeja.

## 8. Verificación final
`php artisan omni:doctor` revisa PHP, permisos, base de datos, cron y cola.
