# Secretaría General — ULEAM

Sitio WordPress de la Secretaría General de la Universidad Laica Eloy Alfaro de Manabí. La maqueta de referencia está en `maqueta-secretaria-general.html`.

## Requisitos

- PHP (con extensión `mysqli`)
- Docker (para MariaDB)

## Arranque rápido

```bash
cp arrancar.sh.example arrancar.sh && chmod +x arrancar.sh
cp detener.sh.example detener.sh && chmod +x detener.sh
# edita DB_PASS en arrancar.sh y crea credenciales.txt (ambos ignorados por git)

./arrancar.sh   # MariaDB (puerto 3308) + PHP (puerto 8000)
./detener.sh
```

- Sitio: http://localhost:8000
- Admin: http://localhost:8000/wp-admin/

La primera vez, completa `wp-config.php` (host `127.0.0.1:3308`, base `db-secretaria`, usuario `wpuser`, la contraseña de `DB_PASS`).

## Publicar en un servidor

Guía completa (direcciones de la parte visual y del administrador, base de datos, archivos y lista de comprobación): [`despliegue/DESPLIEGUE.md`](despliegue/DESPLIEGUE.md).

## Identidad visual

Según la guía *Aplicación de nueva gráfica institucional* de la ULEAM. El tema `secretaria-uleam` define los colores una sola vez, como variables CSS en `wordpress-7.1.3/wp-content/themes/secretaria-uleam/style.css`; el resto del CSS solo usa las variables.

### Tipografía

Familia oficial: [Montserrat](https://fonts.google.com/specimen/Montserrat), solo dos pesos (se carga desde Google Fonts en `functions.php`).

| Peso | Uso |
|------|-----|
| Regular (`400`) | Cuerpo de texto, párrafos, descripciones |
| SemiBold (`600`) | Títulos, subtítulos, énfasis, botones, menús |

### Azules institucionales (80 % de predominancia)

| Vista | Variable | Hex | RGB | CMYK | Rol |
|-------|----------|-----|-----|------|-----|
| ![](https://placehold.co/48x24/005689/005689) | `--azul` | `#005689` | `0, 86, 137` | `95, 63, 22, 7` | Principal: barra superior, botones, títulos, fondos |
| ![](https://placehold.co/48x24/004499/004499) | `--azul-osc` | `#004499` | `0, 68, 153` | `100, 77, 3, 0` | Azul profundo: hover, pie, tipografía fuerte |
| ![](https://placehold.co/48x24/0059A8/0059A8) | `--azul-medio` | `#0059A8` | `0, 89, 168` | `94, 64, 0, 0` | Azul medio: enlaces |
| ![](https://placehold.co/48x24/46A4FF/46A4FF) | `--azul-claro` | `#46A4FF` | `70, 164, 255` | `65, 29, 0, 0` | Azul claro: acentos web, foco de teclado |

`--azul-cl` (`#E6F0F8`) es un tinte claro derivado del azul principal, solo para fondos de tarjetas y secciones; no forma parte de la guía.

`--azul-noche` (`#07235C`) y `--azul-rey` (`#0B3D9E`) son tonos profundos derivados de los azules institucionales, usados juntos como degradado `--grad-azul` en las bandas oscuras: barra superior, banner de las páginas internas, tarjeta del horario, perfil destacado y pie. No forman parte de la guía.

### Colores de apoyo (20 % cromática secundaria)

Úsalos con moderación (máx. ~20 % de la composición).

| Vista | Variable | Hex | RGB | CMYK | Uso permitido |
|-------|----------|-----|-----|------|---------------|
| ![](https://placehold.co/48x24/DB1D1F/DB1D1F) | `--rojo` | `#DB1D1F` | `219, 29, 31` | `5, 97, 93, 0` | Apoyo 3: iconos, bordes, indicador de página activa, texto (4.99:1 sobre blanco) |
| ![](https://placehold.co/48x24/B11719/B11719) | `--rojo-osc` | `#B11719` | `177, 23, 25` | `20, 100, 97, 13` | Apoyo 2: antetítulos y etiquetas pequeñas, fondos con texto blanco (6.99:1) |
| ![](https://placehold.co/48x24/FF0000/FF0000) | `--rojo-claro` | `#FF0000` | `255, 0, 0` | `0, 95, 91, 0` | Apoyo 1: **solo decorativo** (líneas, franjas); 4.00:1, no para texto pequeño |

> Nunca combinar rojo y azul institucional como texto/fondo: el contraste no supera 2.3:1.

| Elemento | Color |
|----------|-------|
| Cabecera, menú, botones, enlaces, títulos, fondos | Azules (80 %) |
| Antetítulos de sección (`.kicker`) | `--rojo-osc` |
| Indicador de la página activa en el menú, iconos de título | `--rojo` |
| Línea bajo los títulos (`.titulo::after`) | Degradado `--rojo` → `--rojo-claro` |

```css
:root {
  --azul:#005689; --azul-osc:#004499; --azul-medio:#0059A8; --azul-claro:#46A4FF;
  --rojo:#DB1D1F; --rojo-osc:#B11719; --rojo-claro:#FF0000;
  --font-uleam:"Montserrat",system-ui,sans-serif;
}
```

### Colores por facultad (complementarios)

Solo para piezas propias de cada facultad; no se usan en el sitio. Los hex se extrajeron de la guía visual: si la institución publica valores oficiales distintos, actualizar.

| Vista | Hex | Facultad |
|-------|-----|----------|
| ![](https://placehold.co/48x24/E84E2F/E84E2F) | `#E84E2F` | Educación y Turismo |
| ![](https://placehold.co/48x24/39BBD8/39BBD8) | `#39BBD8` | Ciencias de la Salud |
| ![](https://placehold.co/48x24/309947/309947) | `#309947` | Ciencias de la Vida y Tecnología de la Información |
| ![](https://placehold.co/48x24/633252/633252) | `#633252` | Artes, Humanidades y Patrimonio |
| ![](https://placehold.co/48x24/035685/035685) | `#035685` | Ciencias Administrativas, Contables y Comercio |
| ![](https://placehold.co/48x24/A73145/A73145) | `#A73145` | Ciencias Sociales |
| ![](https://placehold.co/48x24/3C4A93/3C4A93) | `#3C4A93` | Ingeniería, Industria y Construcción |

Fuente: manual de aplicación de nueva gráfica institucional (ULEAM).
