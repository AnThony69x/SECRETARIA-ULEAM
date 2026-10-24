#!/usr/bin/env bash
# Exporta la base de datos del sitio local para llevarla al servidor. Uso: despliegue/exportar.sh
# Genera despliegue/salida/db-secretaria-FECHA.sql.gz (ignorado por git: lleva el usuario administrador y su correo; no lo subas a ningún repositorio).
set -euo pipefail

RAIZ="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CFG="$RAIZ/wordpress-7.1.3/wp-config.php"
[ -f "$CFG" ] || { echo "ERROR: no existe $CFG"; exit 1; }

# Lee los datos de conexión de wp-config.php (la contraseña no se imprime ni va en la línea de comandos).
cfg() { grep -E "define\( *'$1'" "$CFG" | head -1 | sed -E "s/.*'$1' *, *'([^']*)'.*/\1/"; }
DB_NAME="$(cfg DB_NAME)"; DB_USER="$(cfg DB_USER)"; DB_PASSWORD="$(cfg DB_PASSWORD)"; DB_HOST="$(cfg DB_HOST)"
HOST="${DB_HOST%%:*}"; PUERTO="${DB_HOST##*:}"; [ "$PUERTO" = "$DB_HOST" ] && PUERTO=3306

SALIDA="$RAIZ/despliegue/salida"
mkdir -p "$SALIDA"
ARCHIVO="$SALIDA/db-secretaria-$(date +%Y%m%d-%H%M).sql.gz"
OPCIONES=(--single-transaction --default-character-set=utf8mb4 --no-tablespaces)

if docker ps --format '{{.Names}}' 2>/dev/null | grep -qx secretaria-mariadb; then
  docker exec -e MYSQL_PWD="$DB_PASSWORD" secretaria-mariadb mariadb-dump -u"$DB_USER" "${OPCIONES[@]}" "$DB_NAME" | gzip > "$ARCHIVO"
else
  command -v mariadb-dump >/dev/null 2>&1 && DUMP=mariadb-dump || DUMP=mysqldump
  MYSQL_PWD="$DB_PASSWORD" "$DUMP" -h "$HOST" -P "$PUERTO" -u"$DB_USER" "${OPCIONES[@]}" "$DB_NAME" | gzip > "$ARCHIVO"
fi

gzip -t "$ARCHIVO"
echo "✔ Base de datos exportada: $ARCHIVO ($(du -h "$ARCHIVO" | cut -f1))"
echo "  SHA-256: $(sha256sum "$ARCHIVO" | cut -d' ' -f1)"
echo
echo "Siguiente: despliegue/DESPLIEGUE.md (paso 4: copiar archivos y base de datos al servidor)."
echo "Los PDF y las imágenes están en wordpress-7.1.3/wp-content/uploads ($(du -sh "$RAIZ/wordpress-7.1.3/wp-content/uploads" | cut -f1)) y no van en git: se copian con rsync."
