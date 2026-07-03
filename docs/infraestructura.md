# Infraestructura: producción, seguridad y despliegue

> Extraído VERBATIM de CLAUDE.md el 03/07/2026 (fase 1 de la red de documentación).
> Los invariantes globales y la tabla de enrutamiento viven en CLAUDE.md.

## Seguridad y estado REAL de producción (sesión 8 — endurecimiento)

> Esta sección refleja cómo quedó realmente el despliegue y **SUPERSEDE** lo que
> diga distinto la sección "Producción — Hostinger" de más abajo (esa describía el
> plan original con subdominio y docroot a `public/`; el real usa el dominio).

### Despliegue real (Hostinger)
- **Dominio:** `sigacociap.net` (se descartó el subdominio).
- **Ruta en servidor:** `~/domains/sigacociap.net/public_html` (es el repo; clon shallow → "grafted").
- **Document root:** `public_html` (NO `public/`). El `.htaccess` raíz reescribe todo a `public/`.
- **SSH:** `ssh -p 65002 u761410128@89.116.115.116`.
- **BD:** `u761410128_siga_cociap` / usuario `u761410128_ktcdev` (contraseña rotada).

### El auto-deploy BORRA todo lo no versionado (CRÍTICO)
El Git auto-deploy de Hostinger hace un **checkout limpio** en cada push: elimina
archivos en `.gitignore` y carpetas no rastreadas. Por eso **todo secreto o archivo
subido debe vivir FUERA del repo** (`~/...`), nunca bajo `public_html/`.

### Credenciales de BD — fuera del repo
- Viven en `~/siga_secrets/database.php` (array de conexión, `chmod 600`).
- `config/database.php` es un **cargador versionado SIN secretos**: si existe el archivo
  externo lo usa, si no cae al fallback de XAMPP local. Ya NO se gitignora.

### Firmas/sello del Director EBR — fuera del repo
- Se guardan en `~/siga_uploads/firmas/` (config `firmas_path`, env-aware por `is_dir`).
  El directorio externo debe existir ANTES de subir (si no, cae al fallback local).
- Se sirven por la ruta pública `GET /firmas/{archivo}` (`FirmaController`, valida el
  nombre contra path traversal). La subida guarda en BD `firmas/{archivo}`.
- Ya NO se usa `public/assets/img/firmas/`.

### config/app.php — valores env-aware (no fijos)
- `debug`: `true` solo en hosts locales/privados (localhost, 127.*, 192.168.*, 10.*);
  `false` en producción y ante un `Host` inyectado (default seguro OFF).
- `app_url`: `https://sigacociap.net` si el host es `sigacociap.net`; `''` en local.
- `firmas_path`: `~/siga_uploads/firmas` en prod; `storage/firmas` en local.
- El manejo de errores se cablea en `public/index.php` según `debug`:
  producción → `display_errors=0` + `log_errors=1`; local → `display_errors=1`.

### .htaccess raíz — endurecido
- **Fuerza HTTPS** (301, loop-safe con `X-Forwarded-Proto` / puerto 443).
- **Cabeceras** (en `<IfModule mod_headers.c>`, que LiteSpeed sí procesa): HSTS,
  `X-Content-Type-Options`, `X-Frame-Options`, `Referrer-Policy`.
- **Niega** acceso directo a `.git`, carpetas internas (app/core/config/database/
  resources/storage) y extensiones sensibles (sql/md/log/lock/sh/yml/dist).

### Sesión endurecida (`core/Session.php`)
Cookie `Secure` (solo bajo HTTPS, detectado), `HttpOnly`, `SameSite=Lax`;
`session.use_strict_mode` + `use_only_cookies`. El login regenera el ID (anti-fijación).

### Vistas públicas
- **Rate limiting** en `POST /boleta-publica/consultar`: `Core\Throttle` (contadores
  en `storage/throttle/`), máx 15 intentos por IP cada 5 min → responde 429.
- **noindex** en layouts `digital` y `print` + `public/robots.txt` (`Disallow: /`).

### QR — TODO local
Todo el QR usa la librería local `qrcode.min.js` (boleta digital, footer A4, hoja de
códigos). **NO se usa `chart.googleapis.com` en ningún lado**; no se filtran códigos a
terceros. (Las menciones a Google Charts en este documento quedaron obsoletas.)

### PENDIENTES de seguridad (próximas sesiones)
- [x] **Fase 3 (CERRADA, en prod):** interpolaciones SQL auditadas, longitud/charset
  de entradas públicas validados, subida de imágenes reforzada.
- [x] **Fase 4 (CERRADA, en prod):** `error_log` fuera del docroot (via config
  `log_path`, verificado por SSH), permisos revisados, auditoría de accesos.
- [ ] **CSP:** pasada dedicada — auditar estilos inline (`style="--pct:..."`) y el QR
  antes de aplicar `Content-Security-Policy`.
- [ ] **Limpieza menor:** quitar del `.gitignore` las reglas obsoletas de
  `public/assets/img/firmas/`; `AuthMiddleware` está SIN USAR (la auth es por controlador)
  → decidir si se conecta o se elimina.
- [ ] **Re-subir firma/sello** si se recrea el entorno (se pierden solo si se borra el
  directorio externo; el deploy ya no los toca).

## Producción — Hostinger

> NOTA: la sección de arriba ("Seguridad y estado REAL de producción") es la fuente
> autoritativa. Lo de abajo es el plan original previo al despliegue real.

### Proveedor y requisitos mínimos
Hostinger (plan Premium o Business). Requiere PHP 8.2+ — confirmar y fijar la versión
en hPanel antes de subir cualquier archivo. El código usa `match`, `str_starts_with`,
tipos de retorno `never` y otras funciones de PHP 8.1/8.2.

### Estructura de despliegue obligatoria (Opción B)
El document root del dominio/subdominio debe apuntar directamente a `public/`, no a la
raíz del proyecto. Si apunta a la raíz, `url()` incluirá `/public/` en todas las URLs
y los QR generarán rutas incorrectas desde el primer día.

Configuración en hPanel: crear subdominio (ej. `siga.cociap.edu.pe`), establecer su
document root en `public_html/siga-cociap/public/`.

```
public_html/
└── siga-cociap/          ← repositorio completo
    ├── app/
    ├── config/
    ├── public/           ← document root del subdominio ← apunta aquí
    └── ...
```

### `url()` — no requiere cambios de código
Con `app_url = ''` (valor actual en `config/app.php`), `url()` lee `$_SERVER['HTTP_HOST']`
en cada request y construye la base automáticamente. En Hostinger genera
`https://siga.cociap.edu.pe/boleta-publica?codigo=...` sin tocar una línea.
Solo setear `app_url` si se necesita forzar una URL fija.

### SSL — activar antes de la primera prueba
Hostinger incluye Let's Encrypt gratuito. Activarlo en hPanel desde el inicio.
Los teléfonos modernos bloquean contenido HTTP mixto y muestran advertencia en HTTP puro,
lo que arruina la experiencia de escaneo de QR para los padres.

### QR — dos tipos con comportamiento distinto
| QR | Vista | URL incrustada | Requiere login |
|---|---|---|---|
| Footer boleta A4 | `boleta/alumno.php` | `/boleta/digital/{id}/{id}` | **Sí** → redirige a login |
| Hoja de códigos admin | `/admin/boletas-publicas/{id}/imprimir` | `/boleta-publica?codigo=COCIAP-...` | **No** → acceso directo |

El único QR que un padre puede escanear desde su celular sin cuenta es el de la
**hoja de códigos**. El QR del footer de la boleta A4 es para usuarios autenticados.

Todo el QR (boleta digital, footer A4 y hoja de códigos) se genera con `qrcode.min.js`
(librería local, sin dependencia de terceros). No requiere salida a internet ni filtra
datos a servicios externos.

### Cambios en `config/app.php` para producción
```php
'debug'   => false,   // OBLIGATORIO — en true expone stack traces al público
'app_url' => '',      // mantener vacío — la auto-detección funciona correctamente
```

### Archivos fuera de Git — transferir manualmente
- `config/database.php` — crear con las credenciales MySQL de Hostinger
- `public/assets/img/firmas/` — copiar los PNG de firma y sello del Director EBR
- Permisos del directorio: `chmod 775 public/assets/img/firmas/` para que el
  controlador pueda escribir nuevas imágenes al subir desde `/admin/director-ebr`

### Base de datos en Hostinger
- Crear BD y usuario MySQL desde hPanel de Hostinger.
- Exportar el dump desde XAMPP local: estructura + datos, charset utf8mb4.
  **No usar los backups del repositorio** — pueden estar desactualizados; los datos
  reales (notas del I Bimestre) solo existen en la BD local de XAMPP.
- Importar el dump via phpMyAdmin de Hostinger.
- Las migraciones ya están aplicadas en la BD local — el dump las incluye.
  No volver a correr los archivos de `migrations/` sobre el dump importado.

### Checklist de despliegue
1. Commitear todos los cambios pendientes (incluyendo `public/css/app.css` compilado)
2. Exportar dump completo de la BD local (utf8mb4, con datos reales)
3. Subir el repositorio a Hostinger vía Git o FTP
4. Crear subdominio en hPanel y apuntar document root a `public/`
5. Activar SSL (Let's Encrypt) en hPanel
6. Crear BD MySQL en hPanel e importar el dump
7. Crear `config/database.php` con credenciales de Hostinger
8. Confirmar que `mod_rewrite` está activo (activo por defecto en planes compartidos)
9. `chmod 775 public/assets/img/firmas/`
10. Transferir los PNG de firma y sello del Director EBR
11. Probar en navegador: login, boleta imprimible, boleta digital, boleta pública con código
12. Escanear un QR de la hoja de códigos desde un celular con datos móviles (no WiFi local)
13. Imprimir boleta de prueba en la RICOH MP4054 PCL6 del colegio

### Riesgos conocidos
- **PHP `exif_imagetype()`**: no disponible en XAMPP local → se usa `getimagesize()[2]`.
  Verificar si Hostinger tiene `ext-exif` habilitada; si es así, la validación funciona
  igual porque `getimagesize()[2]` es equivalente y más portable.
- **Versión PHP**: confirmar PHP 8.2 en hPanel — algunos planes Hostinger tienen 8.1
  como versión por defecto.
- **`mod_rewrite`**: los planes compartidos de Hostinger lo incluyen, pero si se usa
  un plan VPS hay que habilitarlo manualmente (`a2enmod rewrite`).

## Orden de ejecución SQL (setup desde cero)
```
1. migrations/000_crear_base_de_datos.sql
2. migrations/siga_cociap.sql
3. migrations/002_criterios_calificaciones.sql
4. migrations/003_bloqueos_competencia.sql
5. migrations/004_limpiar_datos_semilla.sql
6. migrations/005_boletas_publicas.sql
7. migrations/006_soft_delete_criterios.sql   ← sesión 7
8. migrations/007_director_ebr_historial.sql  ← sesión 7
9. migrations/008_director_ebr_imagenes.sql   ← sesión 7
   (… 009-017 según database/migrations/)
10. migrations/018_criterios_descripcion.sql  ← criterios: descripción opcional
11. migrations/019_transversales_docente.sql  ← transversales por docente + cierre del tutor
12. migrations/020_bloqueos_origen.sql        ← origen del bloqueo (docente/cierre)

