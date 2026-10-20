#!/usr/bin/env bash
# Plantilla: cp detener.sh.example detener.sh && chmod +x detener.sh

port_open() {
  timeout 1 bash -c "echo >/dev/tcp/0.0.0.0/$1" 2>/dev/null
}

echo "▶ Deteniendo servidor PHP (puerto 8000)..."
if port_open 8000; then
  pkill -f "php -S 0.0.0.0:8000" >/dev/null 2>&1 || true
  echo "✔ Servidor PHP detenido."
else
  echo "  (no estaba corriendo)"
fi

echo "▶ Deteniendo MariaDB (Docker)..."
if command -v docker >/dev/null 2>&1 && docker ps --format '{{.Names}}' 2>/dev/null | grep -qx 'secretaria-mariadb'; then
  docker stop secretaria-mariadb >/dev/null
  echo "✔ MariaDB detenida."
else
  echo "  (no estaba corriendo)"
fi
