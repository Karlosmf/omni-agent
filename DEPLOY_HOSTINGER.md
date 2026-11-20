# Guía de Despliegue en Hostinger (PHP 8.3)

## Problema Común: Versión de PHP
Hostinger suele tener una versión de CLI (Terminal) diferente a la versión Web.
Si recibes el error: `Your Composer dependencies require a PHP version ">= 8.3.0". You are running 8.2.x`, sigue estos pasos.

### Paso 1: Configurar PHP en el Panel
1.  Ingresa a **hPanel** (Panel de Control de Hostinger).
2.  Ve a **Avanzado** -> **Configuración de PHP**.
3.  Selecciona **PHP 8.3** (o la versión más reciente disponible compatible) y guarda.

### Paso 2: Configurar la Terminal (SSH)
Aunque cambies la versión web, la terminal puede seguir usando una vieja.
1.  Conéctate por SSH a tu hosting.
2.  Verifica la versión: `php -v`.
3.  Si sigue diciendo 8.2, debes usar la ruta completa al binario.
    *   Prueba: `/usr/bin/php83 -v`.
    *   Usa esa ruta para composer: `/usr/bin/php83 /usr/local/bin/composer install`.

### Paso 3: Despliegue Paso a Paso

1.  **Subir Archivos:**
    *   Usa **Git** (recomendado):
        ```bash
        cd domains/tudominio.com/public_html
        git clone https://github.com/tu-usuario/omni-agent.git .
        ```
    *   O sube el **ZIP** del proyecto (excluyendo `vendor` y `node_modules`).

2.  **Instalar Dependencias (PHP):**
    ```bash
    # Si php regular falla, usa la ruta específica encontrada en Paso 2
    /usr/bin/php83 /usr/local/bin/composer install --no-dev --optimize-autoloader
    ```

3.  **Configurar Entorno (.env):**
    ```bash
    cp .env.example .env
    nano .env
    ```
    *   Configura `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`.
    *   Pon `APP_ENV=production` y `APP_DEBUG=false`.
    *   **IMPORTANTE:** Configura `GEMINI_API_KEY`.

4.  **Base de Datos y Clave:**
    ```bash
    /usr/bin/php83 artisan key:generate
    /usr/bin/php83 artisan migrate --force
    /usr/bin/php83 artisan storage:link
    ```

5.  **Frontend (Build):**
    Hostinger no suele correr Node.js en planes compartidos básicos.
    *   **Opción A (Recomendada):** Compila localmente (`npm run build`) y sube la carpeta `public/build` al servidor vía FTP/File Manager.

6.  **Permisos:**
    Asegura que `storage` y `bootstrap/cache` tengan permisos de escritura (775).

## Solución Rápida (Workaround)
Si Hostinger NO soporta PHP 8.3 todavía en tu plan, puedes relajar la restricción en `composer.json`, pero **NO ES RECOMENDABLE** si usas features de PHP 8.3 (como las de Pest 4).

1.  Edita `composer.json`:
    ```json
    "config": {
        "platform-check": false
    }
    ```
2.  Corre `composer dump-autoload`.

## Cron y cola (obligatorio para el bot recepcionista)

Sin cron los mensajes entrantes quedan en la cola y el bot no responde. No hace falta supervisor: el scheduler ya vacía la cola cada minuto (`queue:work --stop-when-empty`).

1. En hPanel, **Avanzado → Trabajos Cron**, creá un trabajo que corra cada minuto (ajustá la ruta y el binario de PHP):
   ```
   * * * * * cd /home/USUARIO/domains/tudominio.com/public_html && /opt/alt/php84/usr/bin/php artisan schedule:run >> /dev/null 2>&1
   ```
2. En el `.env`, `QUEUE_CONNECTION=database`.
3. Verificá con `php artisan omni:check-config`.

## Activar el bot recepcionista

1. `php artisan migrate --force` (solo agrega columnas y la tabla de avisos; el bot queda apagado).
2. En el `.env`: `WHATSAPP_PROVIDER=zernio`, `ZERNIO_API_KEY`, `ZERNIO_WHATSAPP_ACCOUNT_ID`, `ZERNIO_WEBHOOK_SECRET`. Para probar con un solo número, `ZERNIO_TEST_PHONES=+549...`; en producción dejalo vacío.
3. En Zernio, registrá el webhook `https://tudominio.com/webhooks/zernio` con el mismo secreto.
4. En **Chat → Bot recepcionista**: cargá instrucciones, horario y proveedor de IA, usá **Probar bot** y recién después encendelo.
5. Los nombres que el bot le da al cliente salen de **Marca Blanca → Contacto y Redes** (los enlaces llamados "WhatsApp Belén", "WhatsApp Nela").
