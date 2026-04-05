# Omni-Agent (White Label Edition)

Sistema CRM + ERP + AI Concierge diseñado para agencias de viajes, totalmente personalizable como Marca Blanca.

## Características Principales

- **Configuración Marca Blanca:** Personaliza logos, colores (frontend y backend), nombre de la agencia y nombre de la asistente virtual desde el dashboard.
- **Captura de Leads vía IA:** Asistente virtual dinámico que utiliza los datos de contacto configurados.
- **Analítica Personalizada:** Inserción de scripts de Google Analytics, Meta Pixel y otros directamente desde el panel.
- **Sistema de Cotizaciones:** Creación de presupuestos profesionales en PDF dinámicos y conversión a expedientes.
- **Gestión de Expedientes (Files):** Nro de file autogenerado, servicios y control de costos/ventas.
- **Herramientas Financieras:** Calculadora de neto real (impuestos/comisiones) y simulación rápida en dashboard.
- **Panel Operativo:** Dashboard con métricas, vencimientos y accesos rápidos.

## Documentación y Manuales
Para una guía detallada sobre cómo operar el sistema y configurar la Marca Blanca, consulta el:
👉 [**Manual de Usuario**](./MANUAL_USUARIO.md)

## Requisitos

- PHP 8.3+
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