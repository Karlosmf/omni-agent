# Guía de Despliegue en Hostinger (PHP 8.4)

## Problema Común: Versión de PHP
Hostinger suele tener una versión de CLI (Terminal) diferente a la versión Web.
Si recibes el error: `Your Composer dependencies require a PHP version ">= 8.4.0". You are running 8.2.29`, sigue estos pasos.

### Paso 1: Configurar PHP en el Panel
1.  Ingresa a **hPanel** (Panel de Control de Hostinger).
2.  Ve a **Avanzado** -> **Configuración de PHP**.
3.  Selecciona **PHP 8.4** (o la versión más reciente disponible compatible) y guarda.

### Paso 2: Configurar la Terminal (SSH)
Aunque cambies la versión web, la terminal puede seguir usando una vieja.
1.  Conéctate por SSH a tu hosting.
2.  Verifica la versión: `php -v`.
3.  Si sigue diciendo 8.2, debes usar la ruta completa al binario o crear un alias.
    *   Prueba: `/usr/bin/php83 -v` o `/usr/bin/php84 -v`.
    *   Usa esa ruta para composer: `/usr/bin/php84 /usr/local/bin/composer install`.

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
    /usr/bin/php84 /usr/local/bin/composer install --no-dev --optimize-autoloader
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
    /usr/bin/php84 artisan key:generate
    /usr/bin/php84 artisan migrate --force
    /usr/bin/php84 artisan storage:link
    ```

5.  **Frontend (Build):**
    Hostinger no suele correr Node.js en planes compartidos básicos.
    *   **Opción A (Recomendada):** Compila localmente (`npm run build`) y sube la carpeta `public/build` al servidor vía FTP/File Manager.

6.  **Permisos:**
    Asegura que `storage` y `bootstrap/cache` tengan permisos de escritura (775).

## Solución Rápida (Workaround)
Si Hostinger NO soporta PHP 8.4 todavía en tu plan, puedes relajar la restricción en `composer.json`, pero **NO ES RECOMENDABLE** si usas features nuevas de PHP 8.4.

1.  Edita `composer.json`:
    ```json
    "config": {
        "platform-check": false
    }
    ```
2.  Corre `composer dump-autoload`.
