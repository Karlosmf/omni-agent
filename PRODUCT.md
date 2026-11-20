# Product

<!-- impeccable:product-schema 1 -->

## Platform

web

## Users

**Co-primary audiences (confirmed “Both equally primary”):**

1. **Agency operators** — owners, agents, and back-office staff of a travel agency or franchise. Situation: daily operations in the Filament Admin backoffice. Job: capture and qualify leads (including AI-extracted `temperature`/`ai_summary`), convert `Lead → Booking` (Expediente), assemble `BookingItems` (Vuelo, Hotel, etc.), track bimonetary `Transactions` (ARS/USD → `amount_usd_fixed`), calculate `profit_usd`/`total_sell_usd`, and issue branded PDFs (Presupuestos/Cotizaciones, Recibos, Vouchers, Contratos, Balance) and shareable proposal links.

2. **End travelers (prospects/clients)** — individuals browsing the white-label storefront. Situation: discovering destinations on the public site (`/` welcome, `/packages`, `/packages/{slug}`), interacting with the AI concierge chat (`chat-assistant`, `public-lead-form`, `initial-query-form`), and requesting quotes. Job: find inspiration in “Ideas de Viaje” / `TravelPackage`, get a personalized itinerary and price, and be handed off cleanly to the agency.

Secondary/roadmap audience: sub-agency tenants via future `CustomerPanelProvider` and isolated scopes (not primary today).

## Product Purpose

Omni-Agent is a white-label ERP + CRM + tourism-agency engine that lets any travel agency run under its own corporate identity without touching code. It exists to give small and franchise agencies the same end-to-end toolchain that large operators have: branded storefront and lead capture, structured AI qualification, file-based booking management, and tourism-specific finance — all parametrizable.

Success means an agency can deploy the monolith, set `AgencySetting` (logos, favicons/isotipo, palettes, social links, hero CTA), and operate entirely branded — from the `welcome.blade.php` hero/photo-stack and promo sliders through PDFs/emails — while leads flow from chat → expediente → cobros/pagos with correct margins and currency handling.

## Positioning

The mechanism a neighboring generic CRM/ERP or travel SaaS could not truthfully copy is the **tripod: 100% white-label parametrizability + AI concierge + tourism-native finance** (confirmed “White-label + AI + Finance”).

* **White-label without code:** Visual identity centralized in `AgencySetting` and injected everywhere via cached helpers (`get_agency_settings()`, `get_agency_logotipo_url()`, `hex_to_oklch()`) — storefront, `app.blade.php`, and DomPDF templates consume the same tokens. `public/images/branding/` fallbacks (`logo-full.png`, `logo-icon.png`) keep deployments resilient.
* **AI Concierge:** Gemini 2.0 Flash via `AiConciergeService` + async `ProcessAiResponse` job; extracts structured intent (destinos/tiempos), writes `ai_data`/`temperature`/`ai_summary` to `Lead`, injects brand voice dynamically, with mock-data fallback on quota/error. Fronted by Livewire Volt `chat-assistant`.
* **Integral financial engine:** `Booking` (`file_number`, `total_sell_usd`, `profit_usd`) + `BookingItems` (`cost_usd` vs `sell_usd`) + `Transactions` (original `amount` in ARS/USD + normalized `amount_usd_fixed`); supports liquidaciones, diferencias de tipo de cambio, and carga de servicios con costos diferenciados.

Together they position Omni-Agent as “tu compañera de viajes” infrastructure: not a booking widget, but the agency operating system.

## Operating Context

**Workflows (factual):**

* **Capture → Qualify:** Traveler message on `/chat` or `PublicLeadForm` → `Lead` created sync → `CaptureLeadAction` → `ProcessAiResponse` queued → Gemini (or mock) updates `Lead` → `ai_summary`/`temperature` visible in Filament.
* **Lead → Booking:** One-click conversion to `Booking` (Expediente); add `BookingItems`; register `Transactions`; profit calculated in real time; share `booking-proposal` link; emit PDF receipts/contracts.
* **Package publishing:** `TravelPackage` (`title`, `destination`, `nights`, `cover_image`, `tags`, `price_from`, `currency`, `price_basis`, `is_active`, `slug`) rendered in `welcome.blade.php` carousel (Alpine `x-data` with autoplay/scroll), `packages.index` and `packages.show`.
* **Brand administration:** Filament `AdminPanelProvider` pages `ManageAgencySettings`, `ManageSliders` (model `JsonSlider` with `slider_type: main | hero_stack | promo`), `FinancialReports`, `AnalyticsDashboard`, `ServerAdmin`. Future `CustomerPanelProvider` + multi-tenancy scopes planned.
* **Content & comms:** Transactional emails (`emails/lead/received`, `emails/booking/proposal`), PDFs (`pdf/booking`, `pdf/contract`, `pdf/receipt`, `pdfs/quotation`), and sliders all pull `AgencySetting` colors/logos.

**Environments & tools:** Laravel 12 monolith, Filament v4 (Schemas/Tables), Livewire 3 + Volt, Tailwind v4 + DaisyUI with OKLCH tokens, Vite, Pest 4, DomPDF, Intervention Image, `laravel/boost`. Dev entry `composer run dev` (serve + queue:listen + pail + vite). Served via `php artisan serve` / Herd/Valet; SQLite for local dev, MySQL in production (`DATABASE_SCHEMA.md`).

**Materials:** Passports/files terminology, ARS/USD dual currency, supplier cost vs sale separation, itineraries and vouchers as print artifacts.

## Capabilities and Constraints

**Confirmed capabilities:**

* Multi-panel Filament architecture; cached white-label helpers; `JsonSlider` polymorphic rendering in `welcome.blade.php`; `TravelPackage` catalog with cover fallback gradient; shareable booking proposal; PDF generation that respects brand palette.
* Gemini integration via `google-gemini-php/laravel` with graceful mock fallback; queue-backed AI.
* Bimonetary ledger and margin arithmetic; `balance-sheet-pdf`, financial reports.

**Technical constraints (must preserve):**

* Stack is settled (omit greenfield choice): PHP ^8.3, Laravel 12, Filament 4, Livewire 3 + Volt, Tailwind 4 + DaisyUI (OKLCH), Pest 4, DomPDF. Use `php artisan make:` generators; follow existing conventions under `app/Filament/Admin`, `resources/views/livewire/**`.
* Preserve domain terminology in Spanish: `Leads`, `Bookings` = Expedientes/Files, `BookingItems`, `Transactions` (Caja), `Presupuestos`/Cotizaciones, `Ideas de Viaje`.
* Preserve helper surface: `get_agency_settings()` (Cache::rememberForever), `get_agency_logotipo_url/path`, `get_agency_isotipo_url`/`get_agency_favicon`, `format_social_link`, `hex_to_oklch`. Frontend must use these, not direct `AgencySetting::first()`.
* Keep MIT open-source license, Spanish-first UX, and existing route/model names.

**Explicitly undecided / roadmap (do not invent):**

* Centralized `Customers` abstraction (separate from Leads/Bookings) and `Suppliers` entities — Phases 6–7 in `PROJECT_BLUEPRINT.md`.
* Full multi-tenancy isolation for sub-agencies (Supplier/Customer scopes, Customer panel activation).
* Pricing/licensing model for white-label tenants; deployment topology beyond standard Laravel/Herd.

## Brand Commitments

* **Name:** Omni-Agent (White Label Edition). Architect/copyright: Carlos M.F. (2026). Tagline in hero: “Tu compañera de viajes” / “Descubre el mundo con {company_name}”.
* **Voice:** Spanish (rioplatense `vos` — “Descubrí destinos…”), warm, helpful, professional tourism advisor. Keep Spanish-first; do not anglicize primary copy.
* **Assets on hand:** `public/images/branding/` (logotipo, isotipo, favicons), OKLCH palettes derived from `AgencySetting` colors, hero-stack/promo imagery via `JsonSlider` and `uploads/` or `storage/predefined/`. Helpers provide versioned URLs (`?v=filemtime`).
* **Identity rule:** Brand is parametrizable — no hard-coded agency name/colors in views or PDFs; every branded surface must read from `AgencySetting` helpers. The default fallback brand is “Omni-Agent” with amber→orange gradient and amber-100 badges.

## Evidence on Hand

* **Real:** `README.md`, `PROJECT_BLUEPRINT.md`, `MANUAL_USUARIO.md` (+ HTML/PDF), `DATABASE_SCHEMA.md`, `composer.json`/`package.json`, `app/helpers.php`, `resources/views/welcome.blade.php` (hero, photo-stack, promo slider, packages carousel, features grid), `app/Filament/Admin` resources/pages/widgets, `resources/views/livewire/**`, `resources/views/pdfs/**` and `resources/views/pdf/**`. Paths listed are authoritative.
* **Absences (do not fabricate):** No verified testimonials, case studies, press, client logos, or partner endorsements committed in repo; no finalized pricing. Future work must not invent them as social proof.

## Product Principles

1. **Brand without code — or it isn’t white-label.** Every logo, color, link, and PDF/email insertion must flow from `AgencySetting` via cached helpers; a new agency’s identity ships via config, not forks or view edits.
2. **From chat to caja in one continuous file.** Design the traveler → lead → expediente → cobro thread as a single traceable `Lead → Booking → Transaction` chain, with profit and currency normalization visible at each step.
3. **Tourism finance is the domain, not a plugin.** Model cost vs sell, ARS vs USD, and exchange differences natively; never reduce them to a generic “payments” CRUD.
4. **AI that structures, not chats for its own sake.** The concierge earns its place only insofar as it extracts actionable `ai_data` and moves a lead toward a qualified expediente; mock fallback must still produce structured data.
5. **Operational clarity over ornament.** Backoffice is “Operate” mode: scanable tables, predictable Filament patterns, bimonetary totals that are never ambiguous; storefront can be expressive but never at the cost of task completion.

## Accessibility & Inclusion

* Primary language is Spanish; all copy, validation, and PDFs must remain intelligible to Spanish-speaking agency staff and travelers.
* As a responsive web product (Tailwind v4, Filament), meet WCAG 2.2 AA for color contrast (especially OKLCH-derived palettes), keyboard navigation, and form labeling in both storefront and admin. DaisyUI tokens must preserve sufficient contrast when agencies pick arbitrary brand colors.
* No product-specific assistive-tech requirement was established beyond baseline web accessibility; record advances when confirmed.
