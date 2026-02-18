# ESL Retail Updater

Sistema de gestión de Etiquetas Electrónicas de Góndola (ESL) con integración al servidor eRetail.
Arquitectura SaaS multi-tenant: cada organización tiene su propio servidor eRetail aislado.

---

## Tecnologías

- **Backend**: Laravel 11 (PHP 8.2+)
- **Base de datos**: MySQL (shared DB con aislamiento por `organization_id`)
- **Cola de trabajos**: driver `database` (compatible con Hostinger shared hosting)
- **Frontend**: Blade + Tailwind CSS + Font Awesome
- **Procesamiento Excel**: PhpSpreadsheet
- **HTTP API eRetail**: Guzzle

---

## Arquitectura Multi-Tenant

El sistema usa un modelo de **base de datos compartida con discriminador por organización**:

- Todas las tablas de datos (`uploads`, `products`, `product_variants`, `app_settings`, etc.) tienen columna `organization_id`
- El trait `BelongsToTenant` aplica un global scope automático en cada query cuando hay un tenant activo
- `TenantManager` (singleton) mantiene el contexto del tenant por request y sobreescribe la conexión `eretail` en runtime con las credenciales de cada organización
- El middleware `SetTenantContext` resuelve el tenant a partir del usuario autenticado antes de cada request

### Flujo de autenticación

```
Login → AuthController
  ├── is_super_admin=true → /admin (sin tenant context)
  └── usuario regular → SetTenantContext middleware → TenantManager::setTenant() → /dashboard
```

### Cola de trabajos

Los workers de cola no pasan por middleware HTTP. `ProcessUploadJob` lleva el `organization_id` y restaura el tenant context al inicio de `handle()`.

---

## Módulos

### Panel de usuario (tenant)
- **Dashboard**: métricas en tiempo real desde la BD eRetail (etiquetas, productos, APs online)
- **Uploads**: carga de archivos Excel con procesamiento en cola, progreso en tiempo real
- **Etiquetas**: listado con DataTable, detalle y refresco individual/masivo vía API eRetail
- **Configuración**: settings por organización (shop code, plantilla, descuento, etc.)
- **Usuarios**: gestión de usuarios dentro de la organización

### Panel de super-admin (`/admin`)
- **Organizaciones**: CRUD completo con credenciales eRetail (API + BD directa) cifradas
- **Test de conexión BD**: diagnóstico en vivo de la conectividad por organización
- **Impersonación**: "Ver como cliente" — accede al dashboard con el tenant de esa org
- **Usuarios**: gestión cross-tenant (crear, editar, asignar organización)
- **Uploads**: historial global filtrable por organización, shop code y estado

---

## Instalación

### Requisitos
- PHP 8.2+
- MySQL 5.7+ / MariaDB 10.3+
- Composer
- Node.js + npm (para compilar assets)

### Pasos

```bash
# 1. Instalar dependencias
composer install
npm install && npm run build

# 2. Configurar entorno
cp .env.example .env
php artisan key:generate

# 3. Configurar .env
#    DB_CONNECTION, DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD

# 4. Migraciones
php artisan migrate

# 5. Crear super-admin
php artisan tinker
> $u = new App\Models\User();
> $u->name = 'Admin'; $u->email = 'admin@example.com';
> $u->password = bcrypt('contraseña'); $u->is_super_admin = true;
> $u->save();
```

### Cola de trabajos (Hostinger / shared hosting)

El sistema usa un endpoint HTTP `/__run_queue?key={APP_QUEUE_KEY}` que ejecuta `queue:work --stop-when-empty` después de enviar la respuesta. Configurable vía:

```env
APP_QUEUE_KEY=clave_secreta
QUEUE_CONNECTION=database
```

---

## Variables de entorno relevantes

| Variable | Descripción |
|---|---|
| `APP_QUEUE_KEY` | Clave para el endpoint `/__run_queue` |
| `QUEUE_CONNECTION` | `database` (recomendado para shared hosting) |
| `ERETAIL_DB_HOST` | Fallback host BD eRetail (normalmente vacío; se configura por org) |

Las credenciales eRetail (API y BD) se almacenan **cifradas** en la tabla `organizations` y se inyectan en runtime por `TenantManager`.

---

## Changelog

### v2.0.0 — 2026-02-18
- Conversión completa a arquitectura SaaS multi-tenant
- Tabla `organizations` con credenciales eRetail (API + DB) cifradas por tenant
- Aislamiento automático de datos via trait `BelongsToTenant` con global scopes
- `TenantManager`: gestión de contexto de tenant y override de conexión DB en runtime
- Panel de super-administración (`/admin`): organizaciones, usuarios, uploads cross-tenant
- Impersonación de organizaciones desde el panel admin con banner visible
- Diagnóstico de conexión BD por organización desde el panel admin
- `ProcessUploadJob` restaura contexto de tenant en workers de cola
- `ERetailService` lee credenciales del tenant activo (token cache per-tenant)
- `AppSetting` con cache keys aisladas por tenant
- Corrección: credenciales hardcodeadas eliminadas de `config/database.php`
- Corrección de nombre: ELS → ESL en títulos de página

### v1.2.2 — 2026-02-05
- Actividades recientes del dashboard: resumen por upload en vez de logs individuales
- Títulos diferenciados por estado de upload
- Corrección de error de subida en Hostinger

### v1.2.1 — 2026-02-05
- Queue worker vía ruta HTTP (reemplazo de cron bloqueado en Hostinger)
- Límite de productos por upload con estado `pending_approval`

### v1.2.0 — 2026-02-03
- Nuevo módulo de gestión de Etiquetas (Tags) con DataTable
- Refresco individual y masivo de etiquetas

### v1.1.0 — 2026-02-03
- Corrección de detección de APs en línea
- ShopCode desde configuración en lugar de `.env`
- Sistema de versionado

### v1.0.0 — 2026-01-15
- Versión inicial: dashboard, uploads Excel, integración API eRetail
