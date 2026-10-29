# Llevar el sitio al servidor

Guía para publicar el sitio de la Secretaría General. Estado: el flujo de exportar, restaurar y cambiar la dirección
se **ensayó en local** (restaurar la exportación en una base temporal y cambiar la dirección). Lo que depende del
servidor (Apache o Nginx, HTTPS, correo) **no se pudo probar aquí**: sigue los pasos y comprueba la lista del paso 8.

## Dominio de destino y reemplazo del sitio anterior

El sitio va en **https://secretariageneral.uleam.edu.ec/**, el mismo dominio donde hoy está el sitio anterior, que se da de baja.

- **Antes de bajarlo:** pide a quien lo administra una copia completa (archivos y base de datos) y guárdala; es tu vuelta atrás.
- **Enlaces viejos:** anota las direcciones del sitio anterior que el público o Google usan (búscalas con `site:secretariageneral.uleam.edu.ec`).
  Las que no existan en el nuevo se redirigen con `301` en nginx/`.htaccess` a la página equivalente; si no, darán 404.
- **Orden:** deja el sitio nuevo listo en el servidor (o en un subdominio temporal) y haz el cambio de apuntamiento en un
  momento de poco uso. Comprueba la lista del paso 8 enseguida.
- **Certificado y DNS:** el dominio ya existe; confirma con quien lo gestiona que el certificado HTTPS cubre el servidor nuevo.

## Las dos direcciones

| Qué es | Dirección | Dónde se define |
|---|---|---|
| **Parte visual** (lo que ve el público) | `https://secretariageneral.uleam.edu.ec/` | `WP_HOME` en `wp-config.php` y la opción `home` de la base de datos |
| **Administrador** | `https://secretariageneral.uleam.edu.ec/wp-admin/` | `WP_SITEURL` en `wp-config.php` y la opción `siteurl` de la base de datos |

Normalmente son el mismo dominio. En el sitio local ambas son `http://localhost:8000`. El script
`despliegue/cambiar-url.php` cambia la dirección en la base de datos y `wp-config.produccion.example.php` la fija en el
servidor; las dos deben coincidir.

## Qué va en git y qué no

| Va por git (`git clone`) | Se copia aparte |
|---|---|
| WordPress, el tema `secretaria-uleam` y las traducciones al español | `wp-config.php` (datos y claves del servidor) |
| Esta carpeta `despliegue/` | `wp-content/uploads/` (12 GB: los PDF y las imágenes) |
| | La base de datos (contenido, menús, ajustes) |

El contenido (páginas, documentos, menús, ajustes del tema) vive **en la base de datos**, no en el código.
Importa la base de datos: no instales WordPress desde cero y copies solo el tema, porque entonces no tendrías nada de lo
cargado (y las páginas que crea el tema al primer acceso al administrador se pisarían).

## Requisitos del servidor

- PHP 8.1 o superior con `mysqli`, `mbstring`, `gd` o `imagick`, `zip` y `curl`. El tema usa funciones de PHP 7.4+;
  se probó con PHP 8.5.
- MariaDB 10.6+ o MySQL 8 con `utf8mb4` (la base local usa MariaDB 11.8).
- HTTPS con certificado válido.
- `php.ini`: `upload_max_filesize = 64M`, `post_max_size = 64M`, `memory_limit = 256M`, `max_execution_time = 120`
  (hay actas de hasta ~60 MB que se subirán desde el administrador).
- Apache con `mod_rewrite` y `AllowOverride All`, o Nginx (ejemplos en esta carpeta).
- Espacio: unos 13 GB entre `uploads/` y la base.

## Paso a paso

### 1. En tu equipo: exportar la base de datos
```bash
despliegue/exportar.sh
```
Crea `despliegue/salida/db-secretaria-FECHA.sql.gz` (unos 0,5 MB) con su huella SHA-256. Esa carpeta está ignorada por
git porque contiene el usuario administrador y su correo: no la subas a ningún repositorio.

### 2. En el servidor: obtener el código
```bash
git clone https://github.com/AnThony69x/SECRETARIA-ULEAM.git /var/www/SECRETARIA-ULEAM
```
Apunta el servidor web a **`/var/www/SECRETARIA-ULEAM/wordpress-7.1.3`** y no a la raíz del repositorio: en la raíz hay
`.git`, `credenciales.txt` y scripts que no deben ser públicos.

### 3. Crear la base de datos y el usuario
```sql
CREATE DATABASE secretaria CHARACTER SET utf8mb4;
CREATE USER 'secretaria'@'localhost' IDENTIFIED BY 'UNA-CONTRASEÑA-NUEVA-Y-LARGA';
GRANT ALL ON secretaria.* TO 'secretaria'@'localhost';
```
No reutilices la contraseña de desarrollo: está escrita en `arrancar.docker.sh`, que sí está en git.

### 4. Copiar la base de datos y los archivos
```bash
# desde tu equipo (rsync se puede reanudar si se corta; son 12 GB)
rsync -avz --progress wordpress-7.1.3/wp-content/uploads/ USUARIO@SERVIDOR:/var/www/SECRETARIA-ULEAM/wordpress-7.1.3/wp-content/uploads/
scp despliegue/salida/db-secretaria-FECHA.sql.gz USUARIO@SERVIDOR:/tmp/
```
Después, en el servidor:
```bash
gunzip -c /tmp/db-secretaria-FECHA.sql.gz | mysql -u secretaria -p secretaria
chown -R www-data:www-data wordpress-7.1.3/wp-content/uploads   # el usuario del servidor web (puede ser apache o nginx)
rm /tmp/db-secretaria-FECHA.sql.gz
```

### 5. Configuración
```bash
cd /var/www/SECRETARIA-ULEAM/wordpress-7.1.3
cp ../despliegue/wp-config.produccion.example.php wp-config.php
```
Edita `wp-config.php`: base de datos, **claves nuevas** (https://api.wordpress.org/secret-key/1.1/salt/) y
`WP_HOME` / `WP_SITEURL` con tu dominio. Copia también `../despliegue/htaccess.example` como `.htaccess` (Apache) o usa
`nginx.example.conf`.

### 6. Cambiar la dirección en la base de datos
```bash
php ../despliegue/cambiar-url.php http://localhost:8000 https://secretariageneral.uleam.edu.ec --seco   # solo cuenta; deben salir unas 10 filas
php ../despliegue/cambiar-url.php http://localhost:8000 https://secretariageneral.uleam.edu.ec          # aplica el cambio
```
Cambia las opciones `home` y `siteurl`, los enlaces del menú y los enlaces dentro del texto de las páginas (por ejemplo
el botón del BrandBook). Maneja datos serializados sin romperlos. La dirección tiene que ser `https://dominio` sin barra
final. Si WordPress está en otra carpeta, añade `--wp=/ruta/a/wordpress`.

### 7. Seguridad (antes de abrir el sitio al público)
- **Dominio fijo:** en `wp-config.php` debe estar definido `ULEAM_DOMINIO` (descomenta la línea) o usar el
  `wp-config.produccion.example.php`; sin eso, el sitio acepta cualquier host (enlaces de recuperar contraseña envenenables).
- **Clave del chatbot:** define `define( 'ULEAM_CB_KEY', 'la-clave' );` en `wp-config.php` y deja vacío el campo del
  Personalizador, para que no quede en la base ni en sus copias.
- **Detrás de Cloudflare:** añade `define( 'ULEAM_PROXY_CF', true );` solo si el servidor acepta tráfico únicamente de
  Cloudflare; así los límites de uso del chat y de la ficha son por visitante y no globales.
- **Servidor:** usa las reglas de `nginx.example.conf` / `htaccess.example` (bloquean PHP en `uploads` y añaden cabeceras).
- **Contraseña del administrador:** la base importada trae el usuario `admin` con la contraseña de desarrollo
  (no se guarda en ningún archivo del proyecto). Cámbiala nada más entrar (Usuarios → Perfil). Si no puedes entrar:
  `UPDATE wp_users SET user_pass = MD5('PROVISIONAL') WHERE ID = 1;` y cámbiala dentro del administrador.
- Crea un usuario por persona que edite (Usuarios → Añadir) y usa `admin` solo como cuenta de respaldo. El correo del
  administrador (Ajustes → Generales) es el que recibe los avisos.
- Borra el archivo de ejemplo `wp-content/plugins/hello.php`. El plugin Akismet viene desactivado y se puede quitar.
- Permisos: archivos `644`, carpetas `755`, `wp-config.php` en `640`.
- Ajustes → Lectura: confirma que "Visibilidad en los motores de búsqueda" **no** está marcada (en local está permitida).
- Ajustes → Enlaces permanentes: abre la pantalla y guarda sin cambiar nada para regenerar las reglas.

### 8. Comprobar que todo funciona
- [ ] `https://secretariageneral.uleam.edu.ec/` carga con el logo, las diapositivas y el menú.
- [ ] `https://secretariageneral.uleam.edu.ec/wp-admin/` pide usuario y entra.
- [ ] Abre `/consejo-universitario/`, `/consejo-de-postgrado/`, `/archivo-general/`, `/rendicion-de-cuentas/`,
      `/sistema-historico-de-gestion/`, `/brandbook/` y `/consejo-universitario/archivo-historico/`.
- [ ] En Consejo Universitario: abre el año 2024, pulsa **Ver** y **Descargar** en un PDF.
- [ ] En Rendición de Cuentas: "Ver en grande" abre la tabla.
- [ ] El buscador del encabezado encuentra un documento (prueba "inventario").
- [ ] Una dirección que no existe muestra la página "Página no encontrada".
- [ ] Sin avisos de contenido mixto (mira la consola del navegador): todo por `https://`.
- [ ] En un teléfono: el menú se despliega y no hay desplazamiento horizontal.
- [ ] Apariencia → Personalizar → Identidad del sitio: el icono del escudo está puesto.

## Alternativa: migrar con el plugin WPvivid

Otra forma de llevar el sitio, sin exportar la base a mano. No se ha probado en este proyecto; los nombres de los menús
del plugin pueden variar según su versión. La carpeta `uploads/` (unos 12 GB de PDF) **no** se mueve con el plugin.

**Antes del backup (en tu equipo)**
- Revisa las solicitudes de la ficha (tipo `solicitud`, llevan datos personales): borra las de prueba o decide si viajan.
- Vacía la papelera de páginas y borra `wp-content/plugins/hello.php`.
- Confirma el correo de avisos (Ajustes → Generales) y la visibilidad en buscadores (Ajustes → Lectura).
- Instala y activa WPvivid (Plugins → Añadir nuevo).

**Backup**
1. WPvivid → Backup & Restore → Backup Now: *Database + Themes* (solo `secretaria-uleam`). Sin `uploads`, plugins ni núcleo.
2. Descarga todos los archivos que genere (se parte en varios).
3. Copia `uploads/` aparte, directo al servidor:
   `rsync -av --progress wp-content/uploads/ usuario@servidor:/ruta/wp-content/uploads/`

**En el servidor**
1. WordPress 7.1.3 limpio, base de datos vacía y `wp-config.php` propio (`ULEAM_DOMINIO`, `ULEAM_CB_KEY`, credenciales,
   `WP_DEBUG` en `false`): el plugin no lleva `wp-config.php`. Sube los límites de PHP (`upload_max_filesize`,
   `post_max_size`, `memory_limit`, `max_execution_time`).
2. Instala WPvivid, sube los archivos del backup (Backup & Restore → Upload) y restaura.
3. Entra con el usuario y la contraseña **locales** (la base se reemplaza). Cambia la contraseña ya (paso 7).
4. Ajustes → Enlaces permanentes: guarda sin cambiar nada. Copia el `.htaccess` o el bloque de Nginx (paso 5).
5. Si quedan enlaces a `localhost`: `php despliegue/cambiar-url.php http://localhost:8000 https://secretariageneral.uleam.edu.ec --seco`
   y, si cuenta filas, ejecútalo sin `--seco`.
6. Recorre la lista del paso 8 y desinstala WPvivid.

## Después de publicar

- **La base de datos del servidor pasa a ser la buena.** Los documentos nuevos se suben desde el administrador del
  servidor. No vuelvas a importar la base de tu equipo encima de la del servidor: perderías lo cargado desde entonces.
- **Cambios de código** (diseño, funciones): se hacen en tu equipo, se guardan en git y en el servidor se ejecuta
  `git pull`. No hace falta tocar la base de datos.
- **Copias de seguridad:** programa una copia diaria de la base y de `wp-content/uploads/` (el servidor o el hosting
  suele ofrecerlo). Probar una restauración de vez en cuando es parte de la copia.
- Quita de tu equipo `despliegue/salida/` cuando ya no la necesites.

## Si algo sale mal

- **Pantalla en blanco o error crítico:** en `wp-config.php` pon temporalmente `WP_DEBUG_LOG` en `true` y revisa
  `wp-content/debug.log`. Vuelve a ponerlo en `false` al terminar.
- **Los enlaces dan 404 (salvo la portada):** faltan las reglas de reescritura: `.htaccess` o el bloque de Nginx (paso 5).
- **Se ve sin estilos o con enlaces a `localhost`:** falta el paso 6 o `WP_HOME` / `WP_SITEURL` no coinciden con la base.
- **No deja subir un PDF grande:** revisa `upload_max_filesize` / `post_max_size` y `client_max_body_size` (Nginx).
- **Volver atrás:** conserva la base y la carpeta `uploads/` anteriores hasta confirmar que todo está bien.
