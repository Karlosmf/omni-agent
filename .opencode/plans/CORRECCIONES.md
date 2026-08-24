# Plan de Correcciones — Omni-Agent

Auditado: 2026-08-06

---

## FASE 1 — CRÍTICO (bloquea funcionamiento)

### 1.1 Error fatal en `TopProductsWidget`

**Archivo:** `app/Filament/Admin/Widgets/Analytics/TopProductsWidget.php:15`

**Problema:** `TableWidget` de Filament v4 declara `protected static ?string $heading = null;` (static), pero el widget lo redeclara como `protected ?string $heading` (instance). PHP lanza un FatalError por conflicto de tipos.

**Fix:**
```php
// Línea 15: reemplazar
protected ?string $heading = 'Top Paquetes Vendidos';

// Por:
// Eliminar la línea y usar ->heading() en el método table()
```

En el método `table()`, agregar:
```php
return $table
    ->heading('Top Paquetes Vendidos')
    ->query(...)
    // ...
```

---

### 1.2 Doble hash de contraseñas (3 archivos)

**Problema:** El modelo `User` tiene `'password' => 'hashed'` en `casts()`. El cast aplica `Hash::make()` automáticamente al guardar. Los siguientes archivos llaman `Hash::make()` o `bcrypt()` explícitamente, causando doble hash. Los usuarios creados por estos flujos no pueden loguearse.

**Archivo 1:** `app/Actions/Leads/CaptureLeadAction.php:65`
```php
// Cambiar:
'password' => Hash::make(Str::random(16)),
// Por:
'password' => Str::random(16),
```

**Archivo 2:** `app/Livewire/PublicCotizador.php:75`
```php
// Cambiar:
'password' => bcrypt(Str::random(16)),
// Por:
'password' => Str::random(16),
```

**Archivo 3:** `app/Filament/Admin/Resources/Bookings/Schemas/BookingForm.php:58`
```php
// Cambiar:
$data['password'] = Hash::make(Str::random(12));
// Por:
$data['password'] = Str::random(12);
```

---

### 1.3 Job `ProcessAiResponse` llama método inexistente

**Archivo:** `app/Jobs/ProcessAiResponse.php:27`

**Problema:** `$aiService->analyzeLead($this->lead)` — el método `analyzeLead()` no existe en `AiConciergeService`. Lanza `BadMethodCallException` en runtime.

**Fix:** Reemplazar con el método correcto. Verificar cuál aplica:
```php
// Cambiar:
$aiService->analyzeLead($this->lead);
// Por (verificar cuál aplica):
$aiService->extractLeadData($this->lead);
```

---

### 1.4 Referencia a columna inexistente `customer_name`

**Problema:** La tabla `leads` NO tiene columna `customer_name` (verificado en migración `2026_01_13_195123`). Múltiples archivos referencian `$lead->customer_name` que siempre retorna `null`.

**Archivo 1:** `app/Jobs/ProcessLeadExtraction.php:56-57`
**Archivo 2:** `app/Services/AiConciergeService.php:274-275`

**Fix (recomendado):** Agregar columna `customer_name` a la tabla `leads`:
```php
// Nueva migración
$table->string('customer_name')->nullable()->after('customer_id');
```
Y agregar `'customer_name'` al `$fillable` del modelo `Lead`.

---

### 1.5 Referencia a columna inexistente `customer_phone` en Leads

**Archivo:** `app/Filament/Admin/Widgets/UpcomingDepartures.php:42`

**Problema:** `TextColumn::make('lead.customer_phone')` — la tabla `leads` no tiene `customer_phone`.

**Fix:** Cambiar a acceder a través de la relación:
```php
TextColumn::make('lead.customer.phone')
    ->label('Contacto'),
```
Y asegurar eager loading de `lead.customer` en la query del widget.

---

## FASE 2 — SEGURIDAD (alto riesgo)

### 2.1 Eliminar `public/laravel-deployer.php`

**Archivo:** `public/laravel-deployer.php`

**Problema:** Archivo accesible vía web con token hardcoded (`laravel_deploy_v1`). Permite ejecutar Artisan, editar `.env`, correr migraciones, ver logs. Expone `PHP_VERSION`, ruta raíz, y errores completos.

**Acción:**
```bash
rm public/laravel-deployer.php
git add public/laravel-deployer.php
git commit -m "Remove exposed deployer script"
```

Si se necesita deploy remoto, usar CI/CD o SSH.

---

### 2.2 Rate limiting en API pública

**Archivo:** `routes/api.php`

**Problema:** `POST /api/leads` no tiene rate limiting ni autenticación. Crea usuarios y leads libremente.

**Fix:**
```php
Route::post('/leads', [LeadController::class, 'store'])
    ->middleware('throttle:30,1'); // 30 requests por minuto
```

O configurar throttling global para API en `bootstrap/app.php`:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->api(append: [
        \Illuminate\Routing\Middleware\ThrottleRequests::class.':60,1',
    ]);
})
```

---

### 2.3 Livewire release token débil

**Archivo:** `config/livewire.php:185`

**Problema:** `'release_token' => 'a'` — token trivialmente adivinable.

**Fix:** Usar valor aleatorio desde `.env`:
```php
'release_token' => env('LIVEWIRE_RELEASE_TOKEN', Str::random(40)),
```
Y agregar al `.env`:
```
LIVEWIRE_RELEASE_TOKEN=<generar con: php artisan tinker --execute 'echo Str::random(40);'>
```

---

### 2.4 `User::$fillable` incluye `role`

**Archivo:** `app/Models/User.php:31`

**Problema:** Si algún código hace `User::create($request->all())`, un atacante puede asignarse el rol `admin`.

**Fix:** Remover `role` de `$fillable`:
```php
protected $fillable = [
    'name',
    'email',
    'phone',
    'is_guest',
    'permissions',
    'password',
];
```
Donde se asigne el rol, hacerlo explícitamente:
```php
$user->role = UserRole::Admin;
$user->save();
```

---

### 2.5 Credenciales FTP en git

**Archivo:** `update_server.sh:5`

**Problema:** Credenciales FTP hardcoded (`yer929:x0MVh8*GqxWZ@server.dns-principal-33.com`). El archivo está commiteado.

**Acción:**
```bash
git rm --cached update_server.sh
echo "update_server.sh" >> .gitignore
```

Si las credenciales están en el historial de git, usar BFG Repo-Cleaner para limpiar:
```bash
brew install bfg
bfg --delete-files update_server.sh
```

**IMPORTANTE:** Rotar la contraseña FTP inmediatamente.

---

### 2.6 SQL dumps commiteados

**Archivos:** `database_mysql_57_v3.sql`, `omniagent.sql`, `raw_dump.sql`, `mi_migracion.sql`

**Acción:**
```bash
git rm --cached *.sql
echo "*.sql" >> .gitignore
```

Si contienen datos de producción, considerar limpiar el historial git.

---

### 2.7 Configuración de producción

**Archivo:** `.env`

Cambiar para producción:
```
APP_DEBUG=false
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
```

---

## FASE 3 — RENDIMIENTO (N+1 Queries)

### 3.1 Leads Table — falta eager load de `customer`

**Archivo:** `app/Filament/Admin/Resources/Leads/Tables/LeadsTable.php:35`

**Fix:** En `LeadResource::getEloquentQuery()` agregar:
```php
->with(['customer', 'travelPackage'])
```

---

### 3.2 Bookings Table — falta eager load de `agent`

**Archivo:** `app/Filament/Admin/Resources/Bookings/Tables/BookingsTable.php:46`

**Fix:** En `BookingResource::getEloquentQuery()` agregar:
```php
->with(['agent', 'lead', 'lead.customer'])
```

---

### 3.3 Customers Table — falta eager load de `profile`

**Archivo:** `app/Filament/Admin/Resources/Customers/Tables/CustomersTable.php:28`

**Fix:** En `CustomerResource::getEloquentQuery()` agregar:
```php
->with('profile')
```

---

### 3.4 Transactions Table — falta eager load de `booking`

**Archivo:** `app/Filament/Admin/Resources/Transactions/Tables/TransactionsTable.php:26`

**Fix:** En `TransactionResource::getEloquentQuery()` agregar:
```php
->with('booking')
```

---

### 3.5 Supplier `balanceUsd` ejecuta 2 queries por fila

**Archivo:** `app/Models/Supplier.php:52-58`

**Problema:** El accessor `balanceUsd` está en `$appends`, ejecutando 2 aggregate queries por cada Supplier.

**Fix:** Remover de `$appends` y calcular con subquery en la query, o usar `Cache::remember()` con TTL corto.

---

### 3.6 UpcomingDepartures — N+1 en `lead`

**Archivo:** `app/Filament/Admin/Widgets/UpcomingDepartures.php`

**Fix:** En la query del widget, agregar eager loading:
```php
->with(['lead', 'lead.customer'])
```

---

## FASE 4 — DEUDA TÉCNICA

### 4.1 Código duplicado — WhatsApp URL

**Archivos afectados:**
- `app/Filament/Admin/Resources/Bookings/Tables/BookingsTable.php:145-170`
- `app/Filament/Admin/Resources/Bookings/Pages/EditBooking.php:32-58`
- `app/Filament/Admin/Resources/Leads/Tables/LeadsTable.php:86-97`

**Fix:** Crear trait `app/Concerns/GeneratesWhatsAppUrl.php` con la lógica común y usarlo en las 3 clases.

---

### 4.2 Código duplicado — Currency options

**Archivos afectados:**
- `app/Filament/Admin/Resources/Bookings/Schemas/BookingForm.php:304-317`
- `app/Filament/Admin/Resources/Transactions/Schemas/TransactionForm.php:186-199`

**Fix:** Crear método estático `CurrencyService::options()` y reemplazar los closures duplicados.

---

### 4.3 Código duplicado — Passenger parsing

**Archivos afectados:**
- `app/Filament/Admin/Resources/Bookings/Schemas/BookingForm.php:114-134`
- `app/Filament/Admin/Resources/Bookings/Pages/CreateBooking.php:62-74`

**Fix:** Extraer la lógica de regex a un método compartido.

---

### 4.4 Métodos sin return type declarations

| Archivo | Método | Return type sugerido |
|---------|--------|---------------------|
| `ReceiptController.php:11` | `download()` | `Response\|BinaryFileResponse` |
| `PublicCotizador.php:37,44,56` | `nextStep()` | `void` |
| `PublicLeadForm.php:63` | `submit()` | `void` |
| `Task.php:25-26,31-32` | `agent()`, `related()` | `BelongsTo` |
| `ItineraryDay.php:31-33` | `booking()` | `BelongsTo` |
| `Booking.php:107-114` | `itineraryDays()`, `transactions()` | `HasMany` |

---

### 4.5 Excepciones silenciadas

**Archivo 1:** `app/Services/AiConciergeService.php:396-410`
- Catch de `Throwable` retorna array vacío sin feedback.
- **Fix:** Lanzar excepción personalizada o retornar array con flag de error.

**Archivo 2:** `app/Livewire/PublicCotizador.php:61-107`
- Sin try/catch en `submit()`.
- **Fix:** Envolver en try/catch y notificar al usuario.

---

### 4.6 `AgencySetting::first()` sin caché

**Archivos:**
- `app/Filament/Admin/Resources/AgencySettings/Pages/ManageAgencySettings.php:68`
- `app/Filament/Admin/Resources/Bookings/Tables/BookingsTable.php:209`
- `app/Filament/Admin/Resources/Bookings/Pages/EditBooking.php:96`

**Fix:** Reemplazar `AgencySetting::first()` por `get_agency_settings()` (helper global con caché).

---

### 4.7 Upload de pasaporte inconsistente

**Archivo:** `resources/views/livewire/portal/dashboard.blade.php`

- HTML: `accept="image/*,.pdf"`
- Validación: `'image|max:5120'`

**Fix:** Unificar — aceptar PDF en validación o solo imágenes en HTML.

---

### 4.8 Modelos con patrón inconsistente de `$casts`

Modelos con propiedad: Lead, ServiceType, FinancialAccount, BookingPassenger, Task, ItineraryDay, TransactionCategory, Booking, Transaction, BookingItem, UserProfile

Modelos con método: User, TravelPackage, AgencySetting

**Fix:** Migrar todos al método `casts()` según convención del proyecto.

---

### 4.9 Métodos vacíos en LeadObserver

**Archivo:** `app/Observers/LeadObserver.php:25-52`

**Fix:** Eliminar `updated()`, `deleted()`, `restored()`, `forceDeleted()` vacíos.

---

### 4.10 `PublicCotizador::previousStep()` sin bounds check

**Archivo:** `app/Livewire/PublicCotizador.php:56`

**Fix:**
```php
public function previousStep(): void
{
    $this->step = max(1, $this->step - 1);
}
```

---

### 4.11 Relaciones Eloquent sin return type

**Archivos:** `Task.php`, `ItineraryDay.php`, `Booking.php`

**Fix:** Agregar tipos de retorno `BelongsTo`, `HasMany`, etc.

---

## FASE 5 — MEJORAS MENORES

### 5.1 Scripts utility commiteados

```bash
git rm --cached convert_sqlite_to_mysql.php split_layout.py test.txt
echo -e "convert_sqlite_to_mysql.php\nsplit_layout.py\ntest.txt" >> .gitignore
```

---

### 5.2 `currency_rates.json` en disco local

**Fix:** Usar `Cache::remember()` o la base de datos para persistir tasas de cambio.

---

### 5.3 API expone modelo completo

**Archivo:** `app/Http/Controllers/Api/LeadController.php:26-29`

**Fix:** Crear Eloquent Resource para filtrar campos sensibles.

---

### 5.4 `helpers.php` — `hex_to_oklch` inexacta

**Archivo:** `app/helpers.php:11-53`

**Fix:** Usar librería de conversión de colores o implementar OKLCH correcto.

---

## Resumen por prioridad

| Fase | Severidad | Items | Esfuerzo estimado |
|------|-----------|-------|-------------------|
| FASE 1 | CRÍTICO | 5 fixes | ~2 horas |
| FASE 2 | SEGURIDAD | 7 fixes | ~1.5 horas |
| FASE 3 | RENDIMIENTO | 6 fixes | ~2 horas |
| FASE 4 | DEUDA TÉCNICA | 11 fixes | ~4 horas |
| FASE 5 | MENORES | 4 fixes | ~1 hora |
| **TOTAL** | | **33 items** | **~10.5 horas** |

---

## Orden de ejecución recomendado

1. FASE 1 completa (desbloquea tests y funcionalidad básica)
2. FASE 2.1 (eliminar deployer expuesto)
3. FASE 2.2-2.3 (rate limiting + token)
4. FASE 3 completa (mejora rendimiento)
5. FASE 4.1-4.3 (extraer código duplicado)
6. FASE 4.4-4.11 (resto de deuda técnica)
7. FASE 5 completa (mejoras menores)
8. Correr `vendor/bin/pint --dirty --format agent`
9. Correr `php artisan test --compact` para verificar
