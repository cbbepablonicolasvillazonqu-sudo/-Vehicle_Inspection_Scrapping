# Forte Towing — Inventario de Vehículos

Aplicación web (con soporte **PWA**, instalable en el celular) para gestionar el inventario de vehículos de **Forte Towing**: compra de autos usados, reparación, venta o desguace, con control de gastos, rentabilidad y auditoría completa.

## Stack

| Capa | Tecnología |
|---|---|
| Framework | **Laravel 12** (monolito) |
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

**El texto en español es la clave.** Las traducciones al inglés viven en `lang/en.json` (interfaz) y `lang/en/*.php` (validación). Al renderizar en español no se busca nada: Laravel devuelve la clave, que ya es el texto correcto.

```bash
php scripts/verificar-traducciones.php
```

El verificador hace dos pasadas y devuelve `1` si algo falla, así que sirve como puerta antes de un commit:

1. **Cobertura** — cada clave que el código pide traducir (`__()`, `trans_choice()`, `#[Title()]`, `->title()`) existe en `lang/en.json`.
2. **Detección** — no queda texto en español suelto en `app/` ni en las vistas. Un literal que ya figura como clave en `en.json` nunca se marca: da igual si se traduce donde se escribe o al mostrarlo.

#### Tres reglas que no son obvias

- **`APP_FALLBACK_LOCALE` tiene que ser `es`.** Parece un error y es al revés: con `en`, al renderizar en español Laravel no encontraría la clave en `es.json`, iría a `en.json`, la encontraría y devolvería el inglés. La interfaz en español se llenaría de inglés.
- **Los títulos de página van sin `__()`.** `#[Title('Panel')]` es un atributo PHP y no admite llamadas a función: el texto en español es la clave y la traducción ocurre en `layouts/app.blade.php`.
- **Lo que se guarda en la base va en español, nunca traducido.** Las notas del historial de estados y los detalles de auditoría se escriben siempre en español (o como valor neutro del enum) y se traducen al mostrarse. Si se tradujeran al escribir, cada fila quedaría congelada en el idioma de quien hizo la acción. Para nombrar campos y valores en la auditoría están los helpers `nombreCampo()` y `valorCampo()` de `app/Support/helpers.php`, que reutilizan el bloque `attributes` de `lang/*/validation.php`.

El manifiesto de la PWA está duplicado, `public/manifest.webmanifest` (español) y `public/manifest.en.webmanifest` (inglés), porque el navegador lo pide **sin cookies**: una ruta que lo generara no vería la sesión y saldría siempre en español. Si se toca uno, hay que tocar el otro y subir la versión de caché en `public/sw.js`.

---

## Requisitos

- PHP **8.4+** con extensiones: `pdo_mysql`, `mbstring`, `zip`, `gd`, `fileinfo`, `xml`, `dom`, `simplexml`, `xmlreader`, `xmlwriter`, `iconv`, `ctype`, `json`, `openssl`, `tokenizer`, `zlib`
- Composer 2
- MariaDB / MySQL (XAMPP sirve tal cual)
- Node 18+ **solo si vas a recompilar assets** — `public/build` ya viene compilado y versionado, así que ni XAMPP ni Hostinger necesitan Node.

> ### ⚠️ Por qué 8.4, si `composer.json` dice `"php": "^8.2"`
>
> `composer.json` declara el rango que soporta el código. **`composer.lock` congela las versiones exactas que se instalan**, y manda él: `composer install` no vuelve a resolver nada, solo verifica que lo congelado entre en el PHP actual — y si no entra, aborta.
>
> El lock actual trae cinco componentes de **Symfony 8.1** que exigen **PHP ≥ 8.4.1**:
> `symfony/clock`, `symfony/css-selector`, `symfony/event-dispatcher`, `symfony/string` y `symfony/translation`.
>
> No los pide Laravel (él fija los suyos en `^7.2.0`). Entraron como dependencias transitivas: `symfony/console` pide `symfony/string` con `^7.2|^8.0`, `symfony/http-kernel` pide `symfony/event-dispatcher` con `^7.3|^8.0`, `nesbot/carbon` pide `symfony/clock` y `symfony/translation` con `^8.0`… Ante un rango, Composer elige la versión más alta que permita **el PHP de la máquina donde se corrió `composer update`**. Este lock se generó sobre PHP 8.4.
>
> Con PHP 8.2 u 8.3, `composer install` falla con:
> `Your lock file does not contain a compatible set of packages. Please run composer update.`
>
> Dos salidas válidas:
>
> - **Instalar sobre PHP 8.4+** — es lo que corre hoy en Hostinger, y garantiza exactamente las versiones que se probaron.
> - **Bajar el lock a 8.2 de verdad** — agregar a `composer.json`:
>   ```json
>   "config": { "platform": { "php": "8.2.0" } }
>   ```
>   y correr `composer update`. Composer resolverá como si el PHP fuera 8.2 y el lock resultante instalará de 8.2 en adelante. Cambia versiones de dependencias: hay que volver a pasar las pruebas.
>
> **Cada `composer update` vuelve a atar el lock al PHP de quien lo ejecuta.** Si no querés esa dependencia oculta, dejá fijado `config.platform.php` y no lo pises.

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

> **Importante (MariaDB):** el `.env.example` ya trae `DB_COLLATION=utf8mb4_unicode_ci`. No lo quites: la colación por defecto de Laravel (utf8mb4_0900_ai_ci) solo existe en MySQL 8 y MariaDB fallaría.

> Los vehículos de demostración se cargan solo si `SEED_DEMO_DATA=true` (ya viene así en `.env.example`). En producción ponlo en `false`.

## Usuarios sembrados

| Rol | Correo | Contraseña |
|---|---|---|
| **Admin** | `admin@fortetowing.com` | `password` |
| **Gruero** | `gruero@fortetowing.com` | `password` |
| **Mecánico** | `taller@fortetowing.com` | `password` |
| **Vendedor** | `ventas@fortetowing.com` | `password` |

⚠️ Solo se siembran con `SEED_DEMO_DATA=true`, es decir **en local**. `UsuariosSeeder` usa `updateOrCreate`, así que reescribe estas 4 contraseñas cada vez que corre: nunca lo ejecutes en producción. Allí se usan `ProduccionSeeder` (solo roles) y `php artisan forte:crear-admin`.

No hay registro público: las cuentas las crea el Admin desde **Usuarios**.

## Roles y permisos (resumen)

- **Admin** — acceso total: usuarios, reportes de ganancias, exportaciones, eliminar registros, editar ventas cerradas, desguace, revertir estados finales y **fijar el precio de venta sugerido** de cada vehículo.
- **Gruero** — trabaja solo desde su panel (no entra al inventario). Ve **únicamente los vehículos que el Admin le asigna**; registra el recojo (forma de pago, dónde dejó el vehículo, titulación y monto pagado) y completa los datos del Junk car. El monto que carga alimenta el precio y la fecha de compra.
- **Mecánico** — ve los vehículos **pendientes de revisión, en reparación y listos**; puede iniciar la revisión él mismo (Comprado → En reparación), registra reparaciones (gastos) y fotos, marca "Listo para la venta" y puede revertirlo si detecta un problema. **No ve** precios de compra ni ganancias.
- **Vendedor** — ve listos/publicados/vendidos; publica y registra la venta (fecha, precio, comprador, teléfono, método de pago) usando como referencia el **precio de venta sugerido** que fija el Admin (sin ver compra, gastos ni margen). Al vender, el registro queda **bloqueado** para todos excepto Admin.

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

## Despliegue en Hostinger (hosting compartido)

Guía completa paso a paso: **[`docs/DESPLIEGUE-HOSTINGER.md`](docs/DESPLIEGUE-HOSTINGER.md)**.

Resumen del camino feliz:

1. **Base de datos** en hPanel › Bases de datos MySQL. Anotá host, base, usuario y contraseña.
2. **Artefacto** en tu PC: `composer install --no-dev --optimize-autoloader` y **borrar `bootstrap/cache/*.php`** (si no, producción arranca con proveedores de desarrollo y muere).
3. **Subir** por SSH/Git/FTP y apuntar la **carpeta raíz del dominio a `public/`**.
4. **`.env`**: partí de `.env.production.example`, que ya trae los valores correctos.
5. Poner en marcha:
   ```bash
   php artisan key:generate --force
   php artisan migrate --force
   php artisan db:seed --class=ProduccionSeeder --force   # solo roles y permisos
   php artisan forte:crear-admin admin@tudominio.com --password=...
   php artisan storage:link
   php artisan config:cache && php artisan route:cache && php artisan view:cache
   ```
6. Activar el **SSL gratuito** y recién entonces poner `FORZAR_HTTPS=true` y recachear.

No hace falta Node, ni worker de colas, ni cron: `public/build` está versionado y la app no encola trabajos ni tiene tareas programadas.

> ⚠️ **Nunca** corras `db:seed` sin `--class` en producción: `UsuariosSeeder` reescribe las contraseñas de las 4 cuentas de demostración.

## Pruebas

```bash
php artisan test
```

111 pruebas (autenticación, accesos por rol, flujo de vehículos, recojos del gruero, ventas/bloqueo, Junk car, rentabilidad, fotos, gestión de usuarios y PWA) contra una base MariaDB de testing (`forte_towing_testing`, ver `phpunit.xml`).

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

El proyecto usa **Laravel 12** (migrado desde Laravel 11), que recibe parches de seguridad activos. Mantén las dependencias al día con `composer update` periódico y revisa `composer audit`. Ten presente que cada `composer update` regenera `composer.lock` fijándolo al PHP de la máquina donde lo corres (ver **Requisitos**): si el servidor tiene una versión más baja, el despliegue siguiente fallará al instalar. La opción `audit.block-insecure` sigue en `false` para no bloquear instalaciones por avisos de terceros; conviene revisarla manualmente antes de cada despliegue.
