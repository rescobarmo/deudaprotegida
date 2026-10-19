# Deuda Protegida · Dashboard de leads

Panel analítico gerencial conectado a MySQL (`totemly`) para explorar leads, montos y actividad por fecha. Incluye login con sesión, KPIs, tendencia temporal, distribución por rangos de monto, tabla interactiva con búsqueda, filtros globales, exportación CSV y tema claro/oscuro.

## Stack

- Backend: PHP 8.1+ con PDO y consultas preparadas (extensiones `pdo_mysql`, `mbstring`, `json`).
- Base de datos: MySQL 8.0+ / MariaDB 10.4+ (se usan expresiones regulares, CTEs y funciones de ventana).
- Frontend: HTML5, Tailwind CSS 3.4 compilado, ApexCharts 3.54 y módulos ES nativos.
- Sin dependencia de Node en ejecución: los assets de terceros están en `assets/vendor`.
- Docker opcional: PHP 8.2 + Apache y MariaDB 10.11 mediante `docker compose`.

## Inicio rápido

1. Inicia Apache y MySQL desde el panel de XAMPP.
2. Copia `api/config.example.php` a `api/config.php` y define el usuario y el hash de la clave. El archivo `api/config.php` no se publica en el repositorio.
3. Abre `http://localhost/deudaprotegida/`.
4. La conexión por defecto es `127.0.0.1:3306`, base `totemly`, usuario `root`, sin contraseña.

La autenticación se explica en la sección siguiente. También puedes desplegar con Docker usando la sección **Docker**.

La tabla se detecta automáticamente. El requerimiento indicaba `datos_deudaprotegida`, pero la base local contiene `datos_deudaprotegina`; el backend intenta, en orden:

1. `DEUDA_TABLE` (variable de entorno).
2. `datos_deudaprotegida`.
3. `datos_deudaprotegina`.

Si prefieres exponer el nombre original del requerimiento, crea una vista:

```sql
CREATE OR REPLACE VIEW `datos_deudaprotegida` AS
SELECT `id`, `nombre`, `numero`, `monto`, `otro`, `fecha`
FROM `datos_deudaprotegina`;
```

## Autenticación

El acceso usa sesiones PHP con cookie `HttpOnly`, `SameSite=Lax`, ruta acotada al proyecto y expiración por inactividad de 2 horas. Todas las APIs y `dashboard.php` exigen sesión. El login y el logout usan token CSRF. Tras 5 intentos fallidos en una misma sesión, el formulario se bloquea 5 minutos.

La clave se guarda hasheada, nunca en texto plano. Para cambiarla, genera un hash nuevo:

```powershell
php -r "echo password_hash('NuevaClaveSegura', PASSWORD_DEFAULT), PHP_EOL;"
```

Reemplaza `password_hash` en `api/config.php` o define `DEUDA_AUTH_PASS_HASH` en el entorno. También puedes cambiar el usuario con `DEUDA_AUTH_USER`.

## Configuración

Edita `api/config.php` o define variables de entorno en Apache/Windows:

| Variable | Valor por defecto |
| --- | --- |
| `DEUDA_DB_HOST` | `127.0.0.1` |
| `DEUDA_DB_PORT` | `3306` |
| `DEUDA_DB_NAME` | `totemly` |
| `DEUDA_DB_USER` | `root` |
| `DEUDA_DB_PASS` | vacío |
| `DEUDA_TABLE` | autodetección |
| `DEUDA_TIMEZONE` | `America/Santiago` |
| `DEUDA_DEBUG` | `false` |
| `DEUDA_AUTH_USER` | `admin` |
| `DEUDA_AUTH_PASS_HASH` | vacío; debes configurarlo |
| `DEUDA_AUTH_SESSION` | `DEUDAPROTEGIDA` |
| `DEUDA_AUTH_IDLE` | `7200` segundos |
| `DEUDA_AUTH_MAX_ATTEMPTS` | `5` |
| `DEUDA_AUTH_LOCKOUT` | `300` segundos |

Para producción crea un usuario MySQL dedicado con permisos de solo lectura sobre la tabla o vista, y no publiques credenciales en el repositorio.

## Docker

Requiere Docker Engine y Docker Compose v2. La imagen usa PHP 8.2 con Apache, PDO MySQL y mbstring. El Compose incluye MariaDB 10.11, una red interna y un volumen persistente. La imagen genera `api/config.php` desde el ejemplo y toma las credenciales por variables de entorno; tu configuración local no se copia. No requiere Composer ni Node dentro del contenedor.

1. Copia `.env.example` a `.env`.
2. Genera el hash de la clave:

   ```powershell
   php -r "echo password_hash('NuevaClaveFuerte', PASSWORD_DEFAULT), PHP_EOL;"
   ```

3. Edita `.env` y define `DEUDA_AUTH_PASS_HASH`, `DEUDA_DB_PASS` y `MARIADB_ROOT_PASSWORD`. Usa comillas simples para evitar problemas con los `$` del hash.
4. Para cargar datos en la primera ejecución, exporta la tabla y guarda el archivo como `sql/init/10-deudaprotegida.sql`:

   ```powershell
   C:\xampp1\mysql\bin\mysqldump.exe -u root --single-transaction --default-character-set=utf8mb4 totemly datos_deudaprotegida > sql/init/10-deudaprotegida.sql
   ```

5. Levanta los contenedores:

   ```bash
   docker compose up -d --build
   ```

6. Abre `http://127.0.0.1:8080`. El puerto se puede cambiar con `APP_PORT`.

En un VPS, `127.0.0.1:8080` no es accesible desde tu equipo: publica el panel mediante un proxy inverso con TLS o un túnel SSH. Si el proxy inverso es otro contenedor, conéctalo a la red de Compose o ajusta `APP_BIND`.

Comandos útiles:

```bash
docker compose logs -f app
docker compose down
docker compose down -v
docker compose up -d --build
```

`docker compose down` conserva la base de datos. `docker compose down -v` elimina el volumen y los datos. Los scripts de `sql/init` solo se ejecutan cuando el volumen se inicializa por primera vez.

`sql/init/00-permissions.sql` deja al usuario de la aplicación con permisos de solo lectura. Los dumps `sql/init/*.sql` están excluidos de Git para evitar publicar datos personales. Para un VPS, coloca un proxy inverso con TLS (Caddy, Nginx o Traefik) delante del puerto `8080`; MariaDB no se expone al exterior.

## Estructura

```text
deudaprotegida/
├─ index.php
├─ dashboard.php
├─ logout.php
├─ Dockerfile
├─ docker-compose.yml
├─ docker/
│  └─ apache.conf
├─ api/
│  ├─ auth.php
│  ├─ bootstrap.php
│  ├─ config.example.php
│  ├─ config.php
│  ├─ db.php
│  ├─ helpers.php
│  ├─ overview.php
│  ├─ leads.php
│  └─ export.php
├─ assets/
│  ├─ css/app.css
│  ├─ css/tailwind.css
│  ├─ css/tailwind.input.css
│  ├─ js/api.js
│  ├─ js/app.js
│  ├─ js/charts.js
│  ├─ js/format.js
│  └─ vendor/apexcharts.min.js
├─ sql/
│  ├─ init/
│  │  ├─ .gitkeep
│  │  └─ 00-permissions.sql
│  ├─ analytics.sql
│  └─ indexes.sql
├─ composer.json
├─ tailwind.config.js
├─ .dockerignore
├─ .env.example
├─ .gitignore
└─ .htaccess
```

## API

Todos los endpoints requieren sesión autenticada y responden `401` si la sesión no es válida. Las respuestas usan `{ "ok": true, "data": ... }` o `{ "ok": false, "error": ... }`. Las fechas usan `YYYY-MM-DD`. Los filtros de monto son:

`hasta_5m`, `5m_10m`, `10m_20m`, `20m_30m`, `30m_50m`, `mas_50m`, `sin_monto`, `all`.

### `GET /api/overview.php`

| Parámetro | Descripción |
| --- | --- |
| `from`, `to` | Rango de fechas. Si se omiten, usa los últimos 30 días. |
| `all=1` | Ignora `from`/`to` y usa todo el histórico disponible. |
| `monto` | Rango de monto. |
| `granularity` | `auto`, `day` o `month`. `auto` usa mensual sobre 120 días. |

Devuelve KPIs históricos y del período, comparación con el período anterior, tendencia rellenada con ceros, distribución, rango principal y metadatos.

### `GET /api/leads.php`

| Parámetro | Descripción |
| --- | --- |
| `from`, `to`, `all`, `monto` | Mismos filtros globales. |
| `q` | Búsqueda parcial por `nombre`. |
| `rut` | Búsqueda parcial por `id`. |
| `page` | Página, desde 1. |
| `per_page` | 1 a 100. Por defecto 10. |
| `sort` | `fecha`, `nombre`, `id`, `monto`, `estado`. |
| `dir` | `asc` o `desc`. |

La paginación se resuelve en SQL y devuelve `meta.total`, `meta.pages`, etc.

### `GET /api/export.php`

Aplica los mismos filtros de `leads.php` sin paginación, con un máximo de 50.000 filas. Genera un CSV UTF-8 con BOM, delimitador `;` y protección contra inyección de fórmulas.

## Consultas SQL analíticas

`sql/analytics.sql` contiene consultas listas para ejecutar en phpMyAdmin o cliente MySQL:

- KPIs globales y promedio diario.
- Tendencia diaria y serie rellenada con días sin actividad.
- Acumulado diario con función de ventana.
- Tendencia mensual y variación contra el mes anterior.
- Distribución por rangos de monto con porcentaje.
- Top de valores de `monto` registrados.
- Leads recientes.
- Controles de calidad: montos no interpretables, RUT repetidos, teléfonos inválidos, registros sin estado y fechas futuras.

El parser de montos soporta formatos como `$30.000.000_–_$50.000.000`, `$30.000.000_?_$50.000.000` y montos únicos. Extrae el primer y último grupo numérico del texto; para grandes volúmenes conviene persistir un monto normalizado en columnas propias.

## Rendimiento

- La tabla actual tiene clave primaria `(id, fecha)`. Ejecuta una vez `sql/indexes.sql` para agregar índices por `fecha`, `nombre` y `otro`.
- Todas las consultas usan PDO con parámetros enlazados y límites validados.
- La búsqueda por nombre/RUT usa `LIKE '%texto%'`, que no aprovecha índices; para volúmenes altos considera búsqueda por prefijo o un índice full-text.
- La tabla se pagina en servidor (máximo 100 filas por página).
- La exportación está limitada a 50.000 filas para proteger la memoria del servidor.

## Interfaz

- Login con sesión, bloqueo por intentos fallidos y cierre de sesión desde la barra lateral.
- Cuatro KPIs: acumulado histórico, período seleccionado con variación, hoy y mes actual.
- Tendencia de leads y clientes únicos con granularidad diaria/mensual.
- Distribución donut con leyenda, conteos y porcentajes.
- Filtros de rango: últimos 7/30/90 días, mes actual, mes anterior, histórico completo o fechas personalizadas.
- Tabla con búsqueda por nombre, filtro por RUT, ordenamiento, paginación, enlaces a WhatsApp y exportación CSV.
- Estados de carga, vacío y error; tema claro/oscuro persistente; diseño responsive y accesible.

## Tailwind

El CSS está compilado en `assets/css/tailwind.css`. Si modificas clases en `index.php`, `dashboard.php` o `assets/js`, regenera el archivo:

```powershell
npx tailwindcss@3.4.16 -c tailwind.config.js -i assets/css/tailwind.input.css -o assets/css/tailwind.css --minify
```

## Seguridad y privacidad

El panel muestra datos personales (nombre, RUT y teléfono). Antes de exponerlo fuera de localhost:

- Cambia la clave inicial y usa una clave larga y única.
- Publica el panel bajo HTTPS; la cookie de sesión se marca `Secure` automáticamente cuando detecta HTTPS.
- Restringe el acceso a la carpeta y a `api/config.php`.
- Usa un usuario MySQL de solo lectura.
- Mantén `DEUDA_DEBUG=false`.
- No publiques `.env`; ya está excluido por `.gitignore`.
- Revisa la retención y el tratamiento de datos según la normativa aplicable.

`.htaccess` bloquea el listado de directorios, archivos SQL/Markdown y el acceso web directo a los archivos internos de `api`, además de agregar cabeceras de seguridad. Requiere `AllowOverride` habilitado en Apache.

## Verificación realizada

- `php -l` sin errores en todos los archivos PHP.
- Login, logout, CSRF, bloqueo tras 5 intentos y cookies de sesión verificados con Chrome headless.
- Acceso a `dashboard.php` y a todas las APIs sin sesión redirige al login o responde `401`; el frontend redirige al login cuando expira la sesión.
- `docker compose config` validado y Dockerfile revisado con `dockerfilelint`.
- Endpoints probados con respuestas 200 en overview, leads, filtros, búsqueda, paginación, histórico y exportación; respuestas 422 en fechas invertidas y filtros inválidos.
- Navegación real con Chrome headless: sin errores de JavaScript, dos gráficos renderizados, tabla con datos, tema claro/oscuro y menú móvil funcional.
- CSS compilado con Tailwind 3.4.16, sin warning de CDN en consola.
