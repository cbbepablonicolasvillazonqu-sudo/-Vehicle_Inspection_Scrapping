# Despliegue en Hostinger — paso a paso

Guía para poner **Forte Towing** en producción en un plan de hosting compartido de Hostinger
(hPanel + `public_html`). Pensada para seguirse literalmente, de arriba abajo.

> **Antes de nada:** este proyecto **no necesita** Node, ni worker de colas, ni cron. Los assets
> ya vienen compilados y versionados en `public/build`, y la app no encola trabajos ni tiene
> tareas programadas.

---

## 0. Lo que tenés que tener a mano

| Dato | Dónde sale |
|---|---|
| Dominio | El que apuntaste a Hostinger |
| Acceso a hPanel | Correo y contraseña de Hostinger |
| SSH (si tu plan lo incluye) | hPanel › Avanzado › Acceso SSH |
| Hash de tu usuario admin | Se saca de tu base local (paso 8) |

---

## 1. Crear la base de datos

hPanel › **Bases de datos** › **Bases de datos MySQL**:

1. Nombre de la base: `forte` → queda como `u123456789_forte`.
2. Usuario: `forte` → queda como `u123456789_forte`.
3. Contraseña: generá una larga y guardala.

Anotá los cuatro datos. El host es **`localhost`** salvo que hPanel indique otro.

---

## 2. Preparar el paquete en tu PC

```bash
cd C:\autos\forte-towing

# Dependencias de producción (sin Breeze, Sail, PHPUnit…)
composer install --no-dev --optimize-autoloader

# CRÍTICO: la caché local lista proveedores de desarrollo. Si viaja al
# servidor junto a un vendor/ sin esos paquetes, la app muere al arrancar
# con un error fatal y sin pista de la causa.
rm -f bootstrap/cache/*.php
```

Comprimí la carpeta **excluyendo**: `node_modules/`, `.git/`, `tests/`, `.env`, `storage/logs/*`,
`storage/framework/cache/*`, `storage/framework/sessions/*`, `storage/framework/views/*`.

> Cuando termines, en tu PC volvé a `composer install` (a secas) para recuperar las herramientas
> de desarrollo.

---

## 3. Subir el código

Elegí **un** camino:

### Camino A — SSH (el más cómodo)
```bash
ssh -p PUERTO uXXXXXXXXX@tudominio.com
cd ~
git clone https://github.com/cbbepablonicolasvillazonqu-sudo/-Vehicle_Inspection_Scrapping.git forte-towing
cd forte-towing
composer install --no-dev --optimize-autoloader
```

### Camino B — Git de hPanel + `vendor` aparte
1. hPanel › **Avanzado** › **Git** → conectar el repositorio y desplegar.
2. Comprimí solo `vendor/` en tu PC y subilo por el Administrador de archivos, descomprimiéndolo
   dentro de la carpeta del proyecto.

### Camino C — Todo por ZIP/FTP
Subí el ZIP del paso 2 al Administrador de archivos y descomprimilo.

---

## 4. Ubicación y carpeta raíz del dominio

### Plan A (preferido) — se puede cambiar la carpeta raíz
- Proyecto en `~/forte-towing` (**fuera** de `public_html`).
- hPanel › **Sitios web** › tu dominio › **Cambiar carpeta raíz** → `forte-towing/public`.

Así `.env`, `vendor/` y todo el código quedan **inaccesibles desde la web**. Es la opción correcta.

### Plan B — no se puede cambiar la carpeta raíz
1. Proyecto en `~/forte-towing`, y el **contenido** de `forte-towing/public/` copiado dentro de
   `public_html/` (incluido `.htaccess`, `.user.ini`, `build/`, `iconos/`, `sw.js`, `manifest.webmanifest`).
2. Editar `public_html/index.php` y corregir las dos rutas:
   ```php
   require __DIR__.'/../forte-towing/vendor/autoload.php';
   $app = require_once __DIR__.'/../forte-towing/bootstrap/app.php';
   ```
3. `public/build` tiene que quedar en **los dos** lugares: el proyecto y `public_html`.

---

## 5. El archivo `.env`

Copiá `.env.production.example` como `.env` en la raíz del proyecto y completá lo marcado con `<< >>`.
Los valores que **no** son negociables:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tudominio.com
DB_COLLATION=utf8mb4_unicode_ci     # el default de Laravel solo existe en MySQL 8
SESSION_SECURE_COOKIE=true          # si no, la cookie viaja sin protección
BCRYPT_ROUNDS=12                    # no bajar: tu hash se creó con coste 12
SEED_DEMO_DATA=false
RESET_PASSWORD_HABILITADO=false
HOSTS_CONFIABLES=tudominio.com,www.tudominio.com
FORZAR_HTTPS=false                  # se activa recién en el paso 9
```

---

## 6. Poner la app en marcha

Con SSH, desde la carpeta del proyecto:

```bash
php artisan key:generate --force
php artisan migrate --force
php artisan db:seed --class=ProduccionSeeder --force
php artisan storage:link
```

**Qué esperar:** las migraciones listan ~22 archivos en verde; el seeder responde
*"Roles y permisos actualizados. Ningún usuario fue modificado."*

> ⚠️ **Nunca** `db:seed` sin `--class`: eso corre `UsuariosSeeder`, que reescribe las contraseñas
> de las 4 cuentas de demostración.

**Si falla `migrate`** con un error de colación: revisá `DB_COLLATION` en el `.env`.
**Si falla `storage:link`** porque ya existe: borrá `public/storage` y repetí.

---

## 7. Crear tu usuario administrador

Primero, **en tu PC**, sacá el hash de tu contraseña actual:

```bash
"C:\xampp\mysql\bin\mysql.exe" -u root -N -B \
  -e "SELECT password FROM users WHERE email='admin@fortetowing.com';" forte_towing
```

Sale algo como `$2y$12$Xk...`. Copialo entero y, **en el servidor**:

```bash
php artisan forte:crear-admin admin@fortetowing.com --nombre="Administrador" --hash='$2y$12$Xk...'
```

Entrás con **la misma contraseña que usás hoy**; la contraseña en claro nunca sale de tu máquina.

*Alternativa:* si preferís empezar con una contraseña nueva, usá `--password='TuClaveNueva'`.

El comando **se niega a pisar un usuario existente** salvo que agregues `--forzar`. Es a propósito:
así ningún despliegue posterior puede resetear contraseñas por accidente.

---

## 8. Cachear la configuración

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Hacelo **siempre al final**, y repetilo cada vez que toques el `.env`. Con la configuración
cacheada, Laravel deja de leer el `.env`: un cambio sin recachear no tiene efecto.

---

## 9. SSL y PWA

1. hPanel › **Seguridad** › **SSL** → instalar el certificado gratuito y esperar a que emita.
2. Comprobá que `https://tudominio.com` abre sin advertencias.
3. Recién ahí, poné `FORZAR_HTTPS=true` en el `.env` y `php artisan config:cache`.

> HSTS le dice al navegador que use HTTPS durante un año. Si lo activás con el certificado a medio
> emitir, el sitio queda inaccesible y revertirlo es lento. Por eso va al final.

**La PWA necesita HTTPS** y que el sitio esté en la **raíz del dominio**. En un subdirectorio
(`tudominio.com/forte/`) el service worker y el manifiesto se rompen enteros.

---

## 10. Si no tenés SSH

| Comando | Alternativa |
|---|---|
| `composer install` | Subí `vendor/` comprimido desde tu PC (Camino B o C del paso 3) |
| `key:generate` | Generá la clave en tu PC con `php artisan key:generate --show` y pegala en `APP_KEY` |
| `migrate` | hPanel › Bases de datos › **phpMyAdmin** › Importar, con un dump generado en tu PC |
| `db:seed` / `forte:crear-admin` | Igual: exportá esas tablas desde tu PC e importalas |
| `storage:link` | Creá el enlace desde el Administrador de archivos, o copiá `storage/app/public` dentro de `public/storage` |
| `config:cache` | Se puede omitir: la app funciona sin caché, solo un poco más lenta |

Muchos planes compartidos de Hostinger incluyen un **Terminal** en hPanel que sirve igual que SSH.

---

## 11. Verificación post-despliegue (en este orden)

**Infraestructura**
1. `https://tudominio.com/up` → responde **200**.
2. `https://tudominio.com` → aparece el login con el logo y los estilos aplicados
   (si se ve sin estilos, `public/build` no llegó o el manifiesto no se encuentra).

**Seguridad**

3. El login **no** muestra los botones de cuentas de demostración ni ninguna contraseña.
4. `https://tudominio.com/forgot-password` → **404** (correcto: no hay correo configurado).
5. `https://tudominio.com/.env` → **403 o 404**. Si descarga el archivo, la carpeta raíz está mal
   apuntada: **paralo todo** y corregí el paso 4.

**Datos**

6. Entrá como admin con tu contraseña de siempre.
7. El panel muestra **0 vehículos**. En Usuarios existe **solo tu cuenta**.
8. Creá los otros usuarios que necesites desde **Usuarios**, con contraseñas nuevas.

**Prueba de fuego — el ciclo con archivos**

9. Nuevo vehículo **con foto** → la miniatura se ve en el listado *(prueba real del `storage:link`)*.
10. Registrá un gasto **con foto** en ese vehículo.
11. Publicalo y registrá una venta **con contrato en PDF** → el enlace del contrato abre.

**Por rol**

12. Gruero: solo ve lo asignado, no entra al inventario.
13. Mecánico: sin Junk car en el panel, no ve precios de compra.
14. Vendedor: sin costos ni ganancias.

**Celular**

15. Instalá la PWA ("Agregar a pantalla de inicio") y probá el panel del gruero.

---

## 12. Actualizaciones futuras

```bash
# 1. Respaldo primero (ver punto 13)
# 2. Si hay migraciones, cerrá el sitio dejándote una llave:
php artisan down --secret="una-cadena-larga-que-solo-vos-sepas"
#    …y entrá a https://tudominio.com/una-cadena-larga-que-solo-vos-sepas

git pull                                        # o subir los archivos nuevos
composer install --no-dev --optimize-autoloader # solo si cambió composer.lock
php artisan migrate --force                     # solo si hay migraciones nuevas

# 3. SIEMPRE, aunque solo hayas tocado una vista:
php artisan config:cache && php artisan route:cache && php artisan view:cache

php artisan up
```

Si cambiaste CSS o JavaScript, acordate de correr `npm run build` **en tu PC** y subir
`public/build`: en el servidor no hay Node.

---

## 13. Copias de seguridad

**Antes de cada despliegue**, sin excepción:

```bash
# Base de datos
mysqldump -u USUARIO -p BASE > respaldo-$(date +%F).sql

# Archivos subidos (fotos y contratos)
tar -czf storage-$(date +%F).tar.gz storage/app/public
```

Motivo concreto: la migración `2026_07_23_000006_eliminar_fotos_de_etapas_retiradas.php` **borra
filas y archivos del disco sin vuelta atrás**. En una base nueva no afecta a nada (0 filas), pero
una vez que tengas fotos cargadas, un despliegue mal hecho sí puede costarte.

Hostinger hace copias automáticas, pero en compartido suelen ser **semanales**: no alcanzan como
única red de seguridad.

**No hace falta respaldar** `vendor/`, `node_modules/` ni `public/build` (se regeneran).

---

## Resumen de las trampas que ya están resueltas en el código

| Trampa | Cómo quedó |
|---|---|
| Caché de paquetes con proveedores de desarrollo | Se purga en el paso 2 |
| Cabecera Host falsificable | `trustHosts` acotado por `HOSTS_CONFIABLES` |
| Cookie de sesión sin marca `Secure` | `SESSION_SECURE_COOKIE=true` en la plantilla |
| 10 fotos × 12 MB contra un límite de 64 MB | La app ahora admite 5 por envío |
| Seeder que resetea las 4 contraseñas | En producción se usa `ProduccionSeeder` + `forte:crear-admin` |
| Credenciales visibles en el login | Solo se muestran con `APP_ENV=local` |
| "Olvidé mi contraseña" sin correo configurado | La ruta no se registra y el enlace desaparece |
