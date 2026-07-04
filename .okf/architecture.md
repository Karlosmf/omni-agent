---
type: architecture
title: "Arquitectura de Omni-Agent"
description: "Estructura del monolito modular y distribución de componentes."
tags: ["architecture", "laravel", "filament", "livewire"]
---

# Arquitectura del Sistema

Omni-Agent está estructurado como un monolito modular utilizando Laravel 12 y tecnologías modernas del ecosistema PHP/Laravel.

## Componentes y Capas

### 1. Panel de Administración (Backoffice)
- **Tecnología:** [Filament v4](https://filamentphp.com)
- **Ubicación:** `app/Filament/Admin/`
- **Responsabilidad:** Gestión interna de expedientes (`Bookings`), potenciales clientes (`Leads`), y contabilidad/caja (`Transactions`).

### 2. Portal Público y Chatbot
- **Tecnología:** [Livewire Volt v1](https://livewire.laravel.com/docs/volt) (Single File Components)
- **Ubicación:** `resources/views/livewire/public/chat-assistant.blade.php`
- **Responsabilidad:** Interfaz reactiva para interactuar con los usuarios finales y captar información de interés.

### 3. Motor de Inteligencia Artificial
- **Tecnología:** Google Gemini 2.0 Flash API (v1beta/v1 HTTP Client)
- **Ubicación de Servicios:**
  - Servicio: `App\Services\AiConciergeService`
  - Job en cola: `App\Jobs\ProcessAiResponse`
- **Responsabilidad:** Extracción y estructuración en JSON de datos de viaje (intención, presupuesto, destino, etc.) a partir del chat.
