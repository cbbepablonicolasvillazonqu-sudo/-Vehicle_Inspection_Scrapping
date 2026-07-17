# Forte Towing — Inventario de Vehículos

Aplicación web (con soporte **PWA**, instalable en el celular) para gestionar el inventario de vehículos de **Forte Towing**: compra de autos usados, reparación, venta o desguace, con control de gastos, rentabilidad y auditoría completa.

## Stack

| Capa | Tecnología |
|---|---|
| Framework | **Laravel 11** (monolito) |
| Interactividad | **Livewire 3** + **Alpine.js** (incluido por Livewire) |
| Vistas | Blade + **Tailwind CSS** (mobile-first) |
| Base de datos | **MariaDB** (driver `mysql`) |
| Autenticación | **Laravel Breeze** (sin registro público) |
| Roles/permisos | **Spatie Laravel-Permission** |
| Fotos | Storage local (`storage/app/public`, organizado por vehículo y etapa) |
| Exportaciones | **Maatwebsite/Excel** (XLSX y CSV) |
| PWA | `manifest.webmanifest` + service worker + página offline |
| Idiomas | **Español (predeterminado)** e **Inglés**, con selector en la barra superior |

Interfaz bilingüe español/inglés. El español es el idioma por defecto; cada usuario elige el suyo con el selector **ES / EN** (en la barra de navegación y en el login) y su preferencia se guarda en el perfil, persistiendo entre dispositivos.

### Añadir o ampliar traducciones

El texto en español actúa como clave; las traducciones al inglés viven en `lang/en.json` (interfaz) y `lang/en/*.php` (validación). Para comprobar que no falte ninguna clave por traducir:

```bash
php scripts/verificar-traducciones.php
```

---

## Requisitos

- PHP **8.2+** (probado con 8.4) con extensiones: `pdo_mysql`, `mbstring`, `zip`, `gd`, `fileinfo`, `xml`
- Composer 2
- MariaDB / MySQL (XAMPP sirve tal cual)
- Node 18+ **solo si vas a recompilar assets** — `public/build` ya viene compilado y versionado, así que ni XAMPP ni Hostinger necesitan Node.

## Instalación local (XAMPP)

```bash
# 1. Clonar dentro de htdocs (o donde prefieras)
cd C:\xampp\htdocs
git clone https://github.com/cbbepablonicolasvillazonqu-sudo/-Vehicle_Inspection_Scrapping.git forte-towing
cd forte-towing

# 2. Dependencias PHP
composer install

# 3. Configuración
copy .env.example .env
php artisan key:generate

# 4. Crear la base de datos (phpMyAdmin o consola):
#    CREATE DATABASE forte_towing CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 5. Migrar y sembrar (roles + usuarios + vehículos de demostración)
php artisan migrate --seed

# 6. Enlace público para las fotos
php artisan storage:link

# 7. Arrancar
php artisan serve
```

Abrir <http://localhost:8000>. Para servir por Apache de XAMPP, apunta el DocumentRoot (o un VirtualHost) a la carpeta `public/`.

> **Importante (MariaDB):** el `.env.example` ya trae `DB_COLLATION=utf8mb4_unicode_ci`. No lo quites: la colación por defecto de Laravel 11 solo existe en MySQL 8 y MariaDB fallaría.

> Los vehículos de demostración se cargan solo si `SEED_DEMO_DATA=true` (ya viene así en `.env.example`). En producción ponlo en `false`.

## Usuarios sembrados

| Rol | Correo | Contraseña |
|---|---|---|
| **Admin** | `admin@fortetowing.com` | `password` |
| **Comprador** | `compras@fortetowing.com` | `password` |
| **Mecánico** | `taller@fortetowing.com` | `password` |
| **Vendedor** | `ventas@fortetowing.com` | `password` |

⚠️ Cambia las contraseñas en el primer uso (Perfil, o Admin → Usuarios). No hay registro público: las cuentas las crea el Admin.

## Roles y permisos (resumen)

- **Admin** — acceso total: usuarios, reportes de ganancias, exportaciones, eliminar registros, editar ventas cerradas, desguace, revertir estados finales.
- **Comprador** — registra vehículos, edita sus datos, sube fotos, registra gastos, pasa a "En reparación". Ve precios de compra.
- **Mecánico** — ve los vehículos **pendientes de revisión, en reparación y listos**; puede iniciar la revisión él mismo (Comprado → En reparación), registra reparaciones (gastos) y fotos, marca "Listo para la venta" y puede revertirlo si detecta un problema. **No ve** precios de compra ni ganancias.
- **Vendedor** — ve listos/publicados/vendidos; publica y registra la venta (fecha, precio, comprador, teléfono, método de pago). Al vender, el registro queda **bloqueado** para todos excepto Admin.

Cada ruta está protegida con middleware `role:`/`permission:` de Spatie **y** cada componente Livewire vuelve a validar en `mount()`/acciones (defensa en profundidad).

## Estados del vehículo

`Comprado / pendiente de revisión` → `En reparación` → `Listo para la venta` → `Publicado / en venta` → `Vendido` — o `Desguace` en cualquier punto (Admin).

- Colores: 🟠 comprado · 🟡 reparación · 🟢 listo · 🔵 publicado/vendido · ⚪ desguace.
- Se permiten **retrocesos** (ej. Listo → En reparación) según el rol; **todo** cambio queda en el historial con usuario, fecha y nota opcional.
- `Vendido`/`Desguace` se revierten únicamente eliminando la venta/el desguace (solo Admin); el estado vuelve al anterior según el historial.

## Rentabilidad (solo Admin)

`Ganancia = (precio de venta o monto de desguace) − precio de compra − total de gastos`

- Panel: total invertido en inventario activo, ganancia del mes y acumulada.
- Reportes → Ganancias: detalle por vehículo con selector de mes y exportación a Excel.

## PWA (instalar en el celular)

1. Abre la app en Chrome/Safari del teléfono (en local funciona por `http://localhost`; en producción requiere **HTTPS** — Hostinger lo da gratis).
2. Menú del navegador → **"Agregar a pantalla de inicio"** / **"Instalar app"**.
3. Sin conexión, la app muestra una página de aviso y conserva iconos/estilos cacheados. Las páginas con datos nunca se cachean (privacidad).

Los iconos se regeneran con: `php scripts/generar-iconos.php`.

## Despliegue en Hostinger

1. **BD:** crea la base y el usuario MariaDB en hPanel y pon sus credenciales en `.env` (mantén `DB_COLLATION=utf8mb4_unicode_ci`).
2. **Código:** clona el repo (Git de hPanel o SSH). `composer install --no-dev --optimize-autoloader`.
3. **.env de producción:** `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://tudominio.com`, `SEED_DEMO_DATA=false`, y `php artisan key:generate`.
4. **Document root** apuntando a `public/` (en planes Cloud/Business se cambia desde hPanel; en VPS, config del vhost).
5. `php artisan migrate --seed --force` (siembra solo roles y usuarios).
6. `php artisan storage:link` (por SSH). Si el plan no permite symlinks, mueve `storage/app/public` vía panel o usa un cron con el comando.
7. `php artisan config:cache && php artisan route:cache && php artisan view:cache`.
8. Activa el **SSL gratuito** (necesario para la PWA).

No hace falta Node en el servidor: `public/build` está versionado.

## Pruebas

```bash
php artisan test
```

45 pruebas (autenticación, accesos por rol, flujo de vehículos, ventas/bloqueo, desguace, rentabilidad y PWA) contra una base MariaDB de testing (`forte_towing_testing`, ver `phpunit.xml`).

## Estructura del código (lo importante)

```
app/
├── Enums/                  # EstadoVehiculo (colores), CategoriaGasto, MetodoPago…
├── Exports/                # VehiculosExport, GananciasExport (Excel/CSV)
├── Http/Controllers/       # ExportController + auth de Breeze
├── Livewire/
│   ├── Dashboard.php       # Panel con tarjetas y finanzas
│   ├── Admin/              # GestionUsuarios, ReporteGanancias
│   └── Vehiculos/          # Lista, Formulario, Ficha, GestorEstado,
│                           # GestorFotos, GestorGastos, GestorVenta, GestorDesguace
├── Models/                 # Vehicle, Expense, Sale, ScrapRecord, VehiclePhoto,
│                           # VehicleStatusHistory, AuditLog, User
├── Policies/VehiclePolicy  # Bloqueo de vendidos/desguace salvo Admin
└── Services/               # ServicioEstadoVehiculo (matriz de transiciones),
                            # ServicioAuditoria, ServicioRentabilidad
```

## Nota de seguridad

El proyecto usa **Laravel 11** por requerimiento del stack. Laravel 11 dejó de recibir parches de seguridad en **marzo de 2026** (por eso `composer.json` desactiva `audit.block-insecure`). Recomendación: planificar la migración a **Laravel 12** antes de exponer la app a internet en producción; el código usa APIs estándar y la migración es directa.
