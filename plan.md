# Plan de Desarrollo: Omni-Agent (Luopan Travel Edition)

## 1. Visión del Proyecto
Sistema CRM + ERP + AI Concierge para "Luopan Viajes y Turismo".
**Objetivo:** Centralizar la operación financiera (multimoneda), gestionar Files de viajes y automatizar la captura de leads vía WhatsApp/Web con IA.
**Estado:** MVP Completado (v1.0). Fases de refinamiento CRM, IA y QA en progreso.

## 2. Stack Tecnológico (Strict Rules)
*   **Framework:** Laravel 12.x
*   **Admin:** FilamentPHP v4 (Resources, Widgets).
*   **Frontend:** Livewire Volt + Tailwind CSS v4 (Estilo nativo, SIN librerías de componentes).
*   **Database:** SQLite (Migrable a MySQL).
*   **AI:** Google Gemini 1.5 Flash (via google-gemini-php).
*   **Infraestructura:** Single Tenant.

## 3. Historial de Fases (Completadas)

- **Fase 1 a 5 (Core):** Modelos base, Backoffice, IA Concierge, Chat Público, Seguridad y Tests.
- **Fase Extra (UX/Reportes):** PDFs de Vouchers y Recibos, Dashboard con métricas, Traducción total.

### Fase 6: CRM y Fidelización (COMPLETO)
Transformar el manejo de datos planos en entidades relacionales para seguimiento histórico.
- Entidad `Customer` **NOTA: Actualmente se usa User con rol Customer; requiere separación si se desea fidelización histórica independiente**.
- Migración de datos históricos (Leads/Bookings -> Customers) ejecutada.
- Resource `Customer` activo y formularios actualizados.

---

## 4. Hoja de Ruta de Ejecución (Nueva Estructura)

A partir de aquí se define la nueva hoja de ruta para la refactorización financiera y la integración profunda de IA.

### 🛑 FASE 7 (Ex Fase 1): Refactorización del Core Financiero (COMPLETO)
Objetivo: Establecer la estructura de base de datos correcta para soportar Tesorería y Cuentas Corrientes.

*   **Tarea 7.1: Tablas de Estructura Financiera** (Done)
*   **Tarea 7.2: Actualización de Tabla Transactions** (Done)
*   **Tarea 7.3: Lógica de Negocio (Modelos)** (Done)

### ⚙️ FASE 8 (Ex Fase 2): Backoffice & Tesorería (COMPLETO)
Objetivo: Que Belén y Nela puedan cargar gastos, pagos y ver saldos reales.

*   **Tarea 8.1: ABMs Financieros** (Done)
*   **Tarea 8.2: TransactionResource** (Done)
    *   Formulario reactivo, lógica condicional, upload y manejo de Cotización opcional.
*   **Tarea 8.3: Gestión de Proveedores** (Done)


### 🌍 FASE EXTRA: Mejoras de Usabilidad y Locale (COMPLETO)
*   **Localización:** Configuración de idioma 'es'.
*   **Widget Tesorería:** Saldos de cajas/bancos en Dashboard.
*   **Multi-moneda en Files:** 
    *   Soporte para items en distintas monedas (BRL, USD, ARS).
    *   Resumen financiero desglosado (Ganancia USD vs ARS).

### 🧠 FASE 9: Motor de Inteligencia Artificial (COMPLETO)
Objetivo: Conectar Laravel con Gemini de forma robusta.

*   **Tarea 9.1: Servicio de IA (AiConciergeService)** (Done)
    *   Implementado con Gemini Flash 1.5. Resúmenes automáticos y respuestas en chat.
*   **Tarea 9.2: Integración con Leads** (Done)
    *   Campos `ai_summary` y resúmenes automáticos activos en el CRM.

### 💬 FASE 10: Frontend Público (Chatbot & Navbar) (COMPLETO)
Objetivo: Interfaz visual premium y captura de leads.

*   **Tarea 10.1: Componente Visual (Volt)** (Done)
    *   Chat tipo WhatsApp integrado.
*   **Tarea 10.2: Lógica Reactiva** (Done)
    *   Conexión con Gemini y persistencia de leads.
*   **Tarea 10.3: Refactor Navbar** (Done)
    -   Modal de Consultas, Dropdown WhatsApp, Botón Instagram y Soporte Dark Mode.

### 🏗️ FASE 11: Refinamiento CRM y Relacional (COMPLETO)
Objetivo: Separar Customer de User para fidelización histórica y datos relacionales.

*   **Tarea 11.1: Crear Modelo Customer** (Done)
    *   [x] Modelo `Customer` existente con campos: name, email, phone.
    *   [x] Relaciones: hasMany Booking, hasMany Lead.

*   **Tarea 11.2: Migración de Datos** (Done)
    *   [x] Foreign keys actualizados en bookings/leads.

*   **Tarea 11.3: Actualizar CustomerResource** (Done)
    *   [x] Resource activo con formularios y relaciones.

Beneficio: Mejor seguimiento de leads a clientes recurrentes.

### 🧠 FASE 12: Motor de IA Enriquecido y Contextual (Alta Prioridad)
Objetivo: Hacer IA más inteligente con aprendizaje contextual y eliminación de leads genéricos.

*   **Tarea 12.1: Enriquecimiento Contextual Durante Conversación** (Done)
    *   [x] Detección automática de nombre durante chat y extracción por IA
    *   [x] Datos del lead inyectados en system prompt para continuidad
    *   [x] Extracción de destino, presupuesto y pasajeros vía extractLeadData()

*   **Tarea 12.2: Historial Persistente y Memoria de Conversación** (Done)
    *   [x] Modelo `Message` existente (belongsTo Lead)
    *   [x] Contexto expandido a 15 mensajes para Gemini
    *   [x] Sistema de temperatura dinámica basada en keywords de conversación

*   **Tarea 12.3: Trigger de Escalado Humano Inteligente**
    *   [x] Detectar cuando cliente pide explícitamente hablar con humano (keywords: humano/agente/asesor)
    *   [ ] Notificaciones push para Belén/Nela (diferido — ver Fase 15)

*   **Tarea 12.4: Mejora de Error Handling y Fallbacks** (Done)
    *   [x] Retry automático con backoff exponencial (2 reintentos, 0.5s/1s)
    *   [x] Mensajes de error amigables en español
    *   [x] Log detallado de fallos API

### 🧪 FASE 13: Testing UX y QA Integral (Alta Prioridad)
Objetivo: Asegurar experiencia de usuario excepcional con testing completo.

*   **Tarea 13.1: Browser Tests para Flujo Completo**
    *   [ ] Test E2E: Landing → Captura Lead → Dashboard → Conversión
    *   [ ] Test de conversión de chatbot a lead calificado con datos reales
    *   [x] Test mobile experience en iPhone, Android y tablet
    *   [ ] Test de accessibility WCAG 2.1 AA con axe-core integration
    *   [ ] Performance testing: Load time <2s, Mobile score >90

*   **Tarea 13.2: Feature Tests para Componentes Críticos**
    *   [ ] Feature para SmartLeadCapture (modal, validación, conversion)
    *   [ ] Feature para TransactionResource (form reactivo, condicional).
    *   [ ] Unit para AiConciergeService (mock Gemini + context testing).
    *   [ ] Browser tests para chat-assistant (enviar msg, verificar respuesta con IA real).

*   **Tarea 13.3: Métricas y Analytics Implementation**
    *   [ ] Implementar Google Analytics con eventos personalizados (lead_capture, chat_interaction)
    *   [ ] Dashboard de conversión funnel con etapas claras
    *   [ ] Heatmaps para comportamiento del usuario en landing
    *   [ ] A/B testing framework para variantes de formulario y chatbot

*   **Tarea 13.4: QA Post-Fase Ampliado**
    *   [ ] `php artisan test --compact` (cobertura >80% incluyendo browser tests)
    *   [ ] `php artisan model:show [Modelo]` para verificar relaciones.
    *   [ ] Pruebas manuales con Belén y Nela (flujo completo de negocio)
    *   [ ] Performance audit con Lighthouse (>90 score)
    *   [ ] `vendor/bin/pint --dirty` para estilo de código.

### 📄 FASE 14: Mejoras Críticas de UX y Captura de Leads (Máxima Prioridad)
Objetivo: Transformar la experiencia del usuario y eliminar leads genéricos "Web Guest".

*   **Tarea 14.1: Captura Progresiva Inteligente de Leads** (Done)
    *   [x] SmartLeadCapture integrado en chat-assistant (formulario pre-chat)
    *   [x] Formulario reducido: Nombre (requerido) + Destino (select con opciones populares)
    *   [x] Lead creado con datos reales al enviar formulario
    *   [x] Chat abre con saludo personalizado usando nombre y destino
    *   [x] Leads "Web Guest" eliminados — todo lead tiene nombre real

*   **Tarea 14.2: Dashboard Limpio y Operativo** (Done)
    *   [x] Eliminado widget DashboardShortcuts (código muerto)
    *   [x] SupplierDebtsWidget movido al fondo (sort=4) — solo relevante para admin
    *   [x] Dashboard mantiene: AiInsights (leads), UpcomingDeadlines (viajes), ActionWidget (accesos rápidos)
    *   NOTA: Métricas avanzadas (gráficos, analytics) diferidas a Fase 15 cuando haya volumen

*   **Tarea 14.3: Experiencia del Panel Admin Optimizada** (Parcial)
    *   [x] Listados ordenados por último creado (Files, Transacciones) — ya implementado
    *   [ ] Búsqueda global con autocompletado (diferido — Fase 15)
    *   [x] Optimizar vista móvil del panel (Dashboard y Tablas)

*   **Tarea 14.4: Mejoras en Chatbot** (Parcial)
    *   [x] Auto-apertura cambiada de 2s a 5s (menos intrusiva)
    *   [ ] Quick replies para destinos populares (Sprint 2)
    *   NOTA: Micro-interacciones avanzadas (avatars animados, estados contextuales) diferidas a Fase 15

*   **Tarea 14.5: Mejoras Técnicas y de Negocio** (Parcial)
    *   [ ] Actualizar números de teléfono en recibos PDF para Nela y Belén
    *   [x] Numeración correlativa automática (Files, movimientos, recibos) — ya implementado
    *   [x] Agregar opción de servicio "Crucero" al dropdown de Files

*   **Tarea 14.6: Refinar Listado de Presupuestos (Quotations)** (Done)
    *   [x] Pestañas: Pendientes, Aprobados (Files), Rechazados/Expirados, Todos. Navegación habilitada en sidebar.

### 🔒 FASE 14.7: Seguridad y UX (NUEVO - COMPLETADO)
*   **Gestión de Permisos:**
    *   [x] Sistema de permisos granular por usuario (JSON en DB).
    *   [x] Acceso controlado a módulos (Ventas, Tesorería, Reportes).
    *   [x] Protección de rutas y visibilidad de menú.
*   **Mejoras Visuales (Badges):**
    *   [x] Indicadores de estado (Globitos) en sidebar para Leads Nuevos, Presupuestos Pendientes y Próximas Salidas.

### 🚀 FASE 15: Optimización Continua y Métricas (Media Prioridad)
Objetivo: Monitoreo constante y optimización basada en datos.

*   **Tarea 15.1: Optimización Basada en Datos**
    *   [ ] Analizar drop-off points en el funnel con Google Analytics
    *   [ ] Optimizar tiempos de carga con lazy loading y compression
    *   [ ] Implementar PWA para mejor experiencia móvil offline
    *   [ ] A/B testing para diferentes variantes de formulario y CTAs

*   **Tarea 15.2: Reportes Avanzados y Business Intelligence**
    *   [ ] Dashboard con charts para finanzas mensuales (Laravel Charts)
    *   [ ] Reportes de conversión por canal de origen
    *   [ ] Análisis de ROI por campaña y destino
    *   [ ] Exportación automática de reportes para stakeholders

*   **Tarea 15.3: Preparación Futura y Escalabilidad**
    *   [ ] Arquitectura para multi-tenant futuro (tenant_id en modelos clave)
    *   [ ] Endpoint REST para bookings (con Sanctum) para apps móviles
    *   [ ] Rate limiting mejorado en chat IA (via Middleware)
    *   [ ] Logs de IA en Laravel Telescope para debugging avanzado

## 5. Métricas de Éxito (KPIs)

### 📊 KPIs de Negocio (Medición Trimestral)
*   **Conversión Lead → Cliente**: 15% → 35% (objetivo)
*   **Reducción Leads Genéricos**: 100% → <5% (objetivo)
*   **Tiempo de Respuesta Promedio**: <2 horas (objetivo)
*   **Tasa de Completación de Formulario**: >80% (objetivo)
*   **Score Satisfacción Cliente**: >4.5/5 (objetivo)

### 🎯 KPIs Técnicos (Medición Semanal)
*   **Performance**: Load time <2s, Mobile score >90
*   **Uptime**: >99.5%
*   **Bug Reports**: <5 por semana
*   **Test Coverage**: >70%

### 💰 ROI Proyectado
*   **Inversión en UX**: ~160 horas de desarrollo
*   **Retorno Esperado**: +35% conversión, +50% eficiencia operativa
*   **Break-even**: 2 meses post-implementación

## 6. Pipeline de Ejecución (Reorganizado por Impacto)

### 🚀 Sprint 1: Impacto Inmediato (Semana 1 - 48 horas)
- FASE 14.1: Captura Progresiva (24h) - Eliminar leads genéricos
- FASE 14.2: Dashboard Inteligente (12h) - Métricas en tiempo real  
- FASE 14.3: Ordenamiento Panel (12h) - UX admin optimizada

### ⚡ Sprint 2: Optimización (Semana 2 - 72 horas)
- FASE 14.4: Mejoras Chatbot (20h) - Micro-interacciones
- FASE 12.1: Enriquecimiento IA (25h) - Contexto inteligente
- FASE 12.2: Historial y Memoria (15h) - Experiencia persistente
- Técnicas existentes (12h)

### 🔧 Sprint 3: Calidad y Testing (Semana 3 - 40 horas)
- FASE 13.1: Browser Tests (20h) - Testing completo
- FASE 13.2: Feature Tests (10h) - Validación técnica
- FASE 13.4: QA Final (10h) - Calidad integral

## 7. Protocolo de Control de Calidad (QA) Ampliado

Al finalizar cada Sprint, ejecutar:

### QA Técnica:
*   `php artisan test --compact` (Cobertura >80% incluyendo browser tests)
*   `php artisan model:show [Modelo]` para verificar relaciones
*   `vendor/bin/pint --dirty` para estilo de código
*   Performance audit con Lighthouse (>90 score)

### QA de UX:
*   User testing con Belén y Nela (flujo completo de negocio)
*   Mobile testing en 3 dispositivos diferentes
*   Accessibility test con axe-core (WCAG 2.1 AA)
*   Formulario conversion test con Google Analytics

### QA de Negocio:
*   Validar KPIs de conversión (lead → cliente)
*   Probar flujo completo lead → dashboard → acción
*   Verificar notificaciones push y escalado humano
*   Test de integración IA + workflow manual

## 8. Nuevas Ideas Generales (Future Roadmap)
*   **API para Integraciones:** Endpoint REST para bookings (con Sanctum), permitiendo apps móviles futuras.
*   **Reportes Avanzados:** PDFs con charts (usando Laravel Charts) para finanzas mensuales.
*   **Multi-Tenant Futuro:** Prep para tenants (ej. añadir tenant_id en modelos clave).
*   **Seguridad Extra:** Rate limiting en chat IA (via Middleware), encriptación de ai_data sensibles.
*   **Monitoreo:** Logs de IA en Laravel Telescope para debugging.
*   **AI Avanzada:** Sistema de recomendación de destinos basado en historial de clientes.
*   **Integraciones:** WhatsApp Business API para comunicación directa con clientes.
*   **Mobile App:** Aplicación nativa para clientes con tracking de itinerarios en tiempo real.

---

## 9. Impacto del Proyecto (Resumen de Valor)

### 🎯 Transformación del Negocio
- **Eliminación completa** de leads "Web Guest" → 100% leads calificados
- **Dashboard inteligente** para decisiones de negocio en tiempo real
- **UX premium** que refleja calidad de Luopan Viajes
- **Automatización inteligente** que libera tiempo para atención personalizada

### 📈 Resultados Esperados
- **Conversión +133%** (15% → 35%) 
- **Eficiencia +50%** en tiempo de procesamiento de leads
- **Satisfacción cliente >4.5/5** con experiencia personalizada
- **ROI 150%** en 2 meses post-implementación

### 🚀 Ventaja Competitiva
- **Lead capture progressive** único en el mercado local
- **IA conversacional** con memoria y contexto real
- **Analytics avanzado** para optimización continua
- **Experiencia móvil-first** para el viajero moderno
