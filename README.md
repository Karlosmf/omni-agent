# Omni-Agent (Luopan Travel Edition)

Sistema CRM + ERP + AI Concierge diseñado exclusivamente para la agencia "Luopan Viajes y Turismo".

## Características Principales

- **Captura de Leads vía IA:** Chat estilo WhatsApp para recolectar información de clientes automáticamente.
- **Panel Administrativo:** Gestión de expedientes, servicios y finanzas en FilamentPHP v4.
- **Operaciones Bimonetarias:** Soporte para ARS/USD con conversión automática y congelamiento de tasa.
- **Dashboard:** Métricas clave en tiempo real.

## Requisitos

- PHP 8.4+
- Node.js & NPM
- SQLite (para desarrollo)

## Instalación

1.  Clonar el repositorio.
2.  Instalar dependencias PHP:
    ```bash
    composer install
    ```
3.  Instalar dependencias JS y compilar assets:
    ```bash
    npm install
    npm run build
    ```
4.  Configurar el archivo `.env`:
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
5.  Ejecutar migraciones y seeders:
    ```bash
    php artisan migrate:fresh --seed
    ```
6.  Iniciar el servidor de desarrollo:
    ```bash
    php artisan serve
    ```

## Acceso al Panel Admin

- **URL:** `/admin`
- **Usuario:** `admin@luopan.com`
- **Password:** `password`

## Chat Público

- **URL:** `/chat`

## IA Concierge

La integración con Gemini API está activa. El sistema intentará conectar con el modelo `gemini-2.0-flash`.

1.  Asegúrate de tener una API Key válida en `.env`:
    ```env
    GEMINI_API_KEY=tu_api_key_real
    ```
2.  Si la API falla (por cuota excedida o error de red), el sistema usará datos de prueba (mock) automáticamente para no interrumpir el flujo.
3.  Revisa `storage/logs/laravel.log` si notas respuestas genéricas.

## Tests

Ejecutar la suite de pruebas con Pest:
```bash
php artisan test
```