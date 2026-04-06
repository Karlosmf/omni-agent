<div align="center">
  <img src="https://img.shields.io/badge/Laravel-FF2D20?style=for-the-badge&logo=laravel&logoColor=white" alt="Laravel 12" />
  <img src="https://img.shields.io/badge/Filament-Gold?style=for-the-badge&logo=laravel&logoColor=white" alt="Filament v4" />
  <img src="https://img.shields.io/badge/PHP-777BB4?style=for-the-badge&logo=php&logoColor=white" alt="PHP 8.3+" />
  <img src="https://img.shields.io/badge/Tailwind_CSS-38B2AC?style=for-the-badge&logo=tailwind-css&logoColor=white" alt="TailwindCSS" />
  <img src="https://img.shields.io/badge/Open_Source-MIT-brightgreen?style=for-the-badge" alt="MIT License" />
</div>

<br />

# 🌍 Omni-Agent (White Label Edition)

**Omni-Agent** es un sistema ERP, CRM y motor de agencias de turismo desarrollado pensando en la personalización extrema. Funciona como una **Marca Blanca** (White Label) totalmente parametrizable, permitiendo que cualquier agencia de viajes o franquicia turística lo utilice bajo su propia identidad corporativa sin tocar una sola línea de código.

🚀 **Proyecto Creado y Arquitectado por Carlos M.F.** - Software libre para potenciar a las agencias de turismo de todo tipo.

---

## 🔥 Características Principales

### 🏷️ Marca Blanca 100% Configurable
Personaliza drásticamente el sistema desde el panel de administrador: logos, *favicons*, paleta de colores para clientes, colores de la plataforma administrativa, enlaces sociales e inserción dinámica en correos y recibos corporativos.

### 🤖 AI Concierge (IA Integrada)
Un asistente virtual alimentado mediante la API de Google Gemini (2.0-flash) que atiende a los prospectos en el portal, extrae *Leads* de manera estructurada (destinos y tiempos), y responde con la voz y el contexto de tu marca.

### 💰 Sistema Financiero Integral
Maneja las transacciones monetarias de las agencias de turismo calculando márgenes:
* Liquidaciones.
* Diferencias de tipo de cambio.
* Carga de servicios de proveedores con costos diferenciados a los de venta.

### 📝 Expedientes (Files) y Presupuestos PDF Automáticos
* **Cotizaciones en PDF (Presupuestos):** Plantillas autogenerables alimentadas por el motor de DOMPDF que consumen los colores y logotipos de tu *Marca Blanca*.
* **Conversión de Leads a Files:** Pasa con un clic a tus prospectos calificados hacia "Expedientes de viaje" confirmados y procesales pagos oficiales.

---

## 🛠️ Tecnologías Empleadas

El proyecto está diseñado bajo los últimos estándares web y PHP:

* **Framework Core:** Laravel 12.
* **Lenguaje Base:** PHP 8.3+.
* **Panel Administrador:** Filament v4.
* **Componentes Reactivos:** Livewire v3 & Volt.
* **Interfaz Clínica Receptiva:** TailwindCSS v4 + DaisyUI (Renderizado con paletas OKLCH inyectables).
* **Testing Automatizado:** Pest PHP v4.

---

## 💻 Instalación y Despliegue

La instalación es sencilla y se adapta fácilmente a un flujo de despliegue estándar Laravel. 

1. **Clonar el Repositorio:**
   ```bash
   git clone https://github.com/Karlosmf/omni-agent.git
   cd omni-agent
   ```

2. **Instalar Dependencias Backend & Frontend:**
   ```bash
   composer install
   npm install && npm run build
   ```

3. **Variables de Entorno y Claves:**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Migraciones y Base de Datos (SQLite incluido por defecto para dev):**
   ```bash
   php artisan migrate:fresh --seed
   ```

5. **Servir la Aplicación LOCAL:**
   ```bash
   php artisan serve
   # Navegue a localhost:8000 o su Herd/Valet Host
   ```

---

## 📚 Documentación

Para entender en detalle el funcionamiento del sistema contable, los expedientes, el armado de *Ideas de Viaje* (paquetes) y la inteligencia artificial, asegúrate de leer el manual original:

👉 [**Leer Manual de Usuario Oficial**](./MANUAL_USUARIO.md)

---

## 🤝 Contribuir
Omni-Agent es un proyecto vivo y tu ayuda es bienvenida. Sea que subas un *issue* reportando un bug o armés un *Pull Request* de una feature revolucionaria, todo es bien recibido. 

Por favor revisa nuestras [**Pautas de Contribución**](./CONTRIBUTING.md) antes de empezar.

---

## ⚖️ Licencia

Omni-Agent está abierto al mundo y sus fuentes liberadas bajo la **Licencia MIT**. 
Básicamente podés usarlo de forma libre comercial y privadamente. Lee el archivo [LICENSE](LICENSE) para mayor información.

> **Copyright (c) 2026 Carlos M.F.**