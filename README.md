# Secretaría General — ULEAM

Sitio WordPress de la Secretaría General de la Universidad Laica Eloy Alfaro de Manabí. La maqueta de referencia está en `wordpress-7.1.3/maqueta-secretaria-general.html`.

## Requisitos

- PHP (con extensión `mysqli`)
- Docker (para MariaDB)

## Arranque rápido

```bash
cp arrancar.sh.example arrancar.sh && chmod +x arrancar.sh
cp detener.sh.example detener.sh && chmod +x detener.sh
# edita DB_PASS en arrancar.sh y crea credenciales.txt (ambos ignorados por git)

./arrancar.sh   # MariaDB (puerto 3307) + PHP (puerto 8000)
./detener.sh
```

- Sitio: http://localhost:8000
- Admin: http://localhost:8000/wp-admin/

La primera vez, completa `wp-config.php` (host `127.0.0.1:3307`, base `db-secretaria`, usuario `wpuser`, la contraseña de `DB_PASS`).

## Tipografía

Fuente del sistema: `"Segoe UI", system-ui, -apple-system, Roboto, Arial, sans-serif`.

## Colores

| Vista | Variable | Hex | Uso |
|-------|----------|-----|-----|
| ![](https://placehold.co/48x24/0B3D9E/0B3D9E) | `--azul` | `#0B3D9E` | Principal: cabecera, botones, enlaces |
| ![](https://placehold.co/48x24/082C75/082C75) | `--azul-osc` | `#082C75` | Azul oscuro: hover, pie |
| ![](https://placehold.co/48x24/E8EFFF/E8EFFF) | `--azul-cl` | `#E8EFFF` | Azul claro: fondos de tarjetas |
| ![](https://placehold.co/48x24/F2B705/F2B705) | `--dorado` | `#F2B705` | Acento |
| ![](https://placehold.co/48x24/FFE07A/FFE07A) | `--dorado-cl` | `#FFE07A` | Acento claro |
| ![](https://placehold.co/48x24/F4F7FC/F4F7FC) | `--fondo` | `#F4F7FC` | Fondo de página |
| ![](https://placehold.co/48x24/1E2A44/1E2A44) | `--texto` | `#1E2A44` | Texto |
| ![](https://placehold.co/48x24/5F6B85/5F6B85) | `--gris` | `#5F6B85` | Texto secundario |
| ![](https://placehold.co/48x24/E3E9F5/E3E9F5) | `--linea` | `#E3E9F5` | Bordes |

```css
:root {
  --azul: #0b3d9e;  --azul-osc: #082c75;  --azul-cl: #e8efff;
  --dorado: #f2b705; --dorado-cl: #ffe07a;
  --fondo: #f4f7fc; --texto: #1e2a44; --gris: #5f6b85; --linea: #e3e9f5;
  --radio: 16px;
}
```
