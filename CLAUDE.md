# Secretaría General ULEAM: reglas del proyecto

Sitio WordPress de la Dirección de Secretaría General (ULEAM). Todo el código propio vive en el tema
`wordpress-7.1.3/wp-content/themes/secretaria-uleam/`. No se modifica el núcleo de WordPress ni plugins de terceros.

## Arquitectura del tema

```
secretaria-uleam/
├── style.css              Solo la cabecera del tema (WordPress la exige). No poner CSS aquí.
├── functions.php          Soportes del tema, menús, iconos (uleam_icon) y require de inc/*.php
├── header.php / footer.php / page.php / index.php   Plantillas
├── inc/
│   ├── recursos.php       Carga de CSS y JS (módulos en orden)
│   ├── documentos.php     Documentos PDF: CPT, shortcode [consejo], REST uleam/v1/documentos
│   ├── horario.php        Shortcode [horario] + sección del Personalizador
│   └── organigrama.php    CPT "persona" + taxonomía "area" (color, ícono, orden, estilo) + shortcode [organigrama]
└── assets/
    ├── css/NN-nombre.css  Un archivo por componente; NN fija el orden de la cascada
    └── js/nombre.js       Un archivo por componente (IIFE con guarda)
```

### Reglas de modularidad
- Un componente = un archivo. Ningún archivo CSS/JS/PHP debe pasar de ~250 líneas; si crece, divídelo por subcomponente.
- CSS nuevo: va al módulo de su componente en `assets/css/`. Si es un componente nuevo, crea `NN-nombre.css`
  (se carga solo, por orden de nombre: `inc/recursos.php` lee la carpeta). Nunca vuelvas a meter CSS en `style.css`.
- Corrige la regla existente en lugar de añadir otra al final que la sobrescriba. Evita `!important`.
- JS nuevo: `assets/js/nombre.js`, envuelto en `(function(){ ... })();` y que salga si su componente no está en la página
  (`if (!document.querySelector('.mi-componente')) return;`). Regístralo en la lista de `inc/recursos.php`.
- No metas `<script>` dentro del contenido de las páginas: WordPress convierte `&&` en `&#038;&#038;` y rompe el código.
  La lógica va en `assets/js/`, los datos en wp-admin (ver abajo).
- PHP nuevo: `inc/nombre.php` y un `require` en `functions.php`. Prefijo `uleam_` / `ha_` / `doc_` para funciones y opciones.

## Todo editable desde wp-admin
El administrador debe poder cambiar textos, datos, imágenes y enlaces sin tocar código:
- Ajustes globales (horarios, correos, redes, direcciones): **Apariencia → Personalizar**, sección propia por componente
  (patrón de `inc/horario.php`: `add_setting` + `add_control` con `sanitize_callback`).
- Contenido de página: bloques del editor (Grupo con clase CSS, Título, Párrafo, Lista, Imagen) o un shortcode cuyo
  contenido salga del Personalizador, de metadatos o de un tipo de entrada propio.
- Listas que crecen (personal, documentos, slides): tipo de entrada propio (CPT) con campos (patrón `slide` en
  `functions.php`, documentos en `inc/documentos.php`, personal y áreas en `inc/organigrama.php`). El HTML lo genera
  PHP en un shortcode; el JS solo añade comportamiento.
- Nunca valores fijos en el código si el administrador podría querer cambiarlos.
- Toda entrada del administrador se sanitiza al guardar y se escapa al imprimir (`esc_html`, `esc_attr`, `esc_url`),
  con nonce y `current_user_can` en los formularios.

## Diseño (UI)
- **Colores:** solo la paleta institucional del `README.md`, siempre por variable CSS definida en
  `assets/css/01-base.css` (`--azul`, `--azul-osc`, `--azul-medio`, `--azul-claro`, `--azul-cl`, `--rojo`, `--rojo-osc`,
  `--rojo-claro`, `--texto`, `--gris`, `--linea`, `--fondo`, y el azul oscuro del banner `--azul-noche`, `--azul-rey`,
  `--grad-azul`). Azules ~80 %, rojos de apoyo ~20 %.
  Nunca rojo sobre azul (ni al revés) como texto/fondo. Un color nuevo se añade como variable, no como hex suelto.
- **Tipografía:** Montserrat, solo pesos 400 y 600.
- **Iconos:** SVG de trazo tipo Lucide. En PHP usa `uleam_icon('nombre')` (añade el trazo en `functions.php` si falta);
  en CSS, SVG como `mask`/`background` en data URI. **Nunca emojis** como iconos ni en la interfaz.
- Usa los tokens existentes: `--radio`, `--sombra`, espacios `--e1..--e4` (8/16/24/40 px).
- Reutiliza componentes de `02-componentes.css` (`.btn`, `.card`, `.icono`, `.tag`, `.chip`, `.kicker`, `.titulo`).

## Experiencia (UX) y accesibilidad
- **Responsive en todos los dispositivos:** diseña móvil primero y prueba al menos 360 px (móvil), 768 px (tableta),
  1024 px (portátil) y 1440 px o más (escritorio). Sin scroll horizontal, objetivos táctiles de 40 px o más,
  texto corrido de 14 px o más (etiquetas cortas en mayúsculas, mínimo 11 px) y rejillas que pasen a 1–2 columnas en móvil.
  El menú en móvil es un panel con submenús plegables (`assets/js/menu.js`); los filtros y formularios van en una columna.
- Contraste AA, foco visible (`:focus-visible`), `aria-label` en botones de solo icono, `alt` en imágenes, HTML semántico.
- Animaciones cortas y con propósito; todas se desactivan con `@media (prefers-reduced-motion: reduce)`.
- Estados vacíos, de carga y de error con mensaje claro.
- Mensajes y textos de la interfaz en español.

## Ingeniería
- Cambio mínimo y completo: no dupliques; reutiliza helpers y patrones existentes.
- Comentarios cortos en español que expliquen el porqué.
- Antes de dar algo por terminado: `php -l` en el PHP tocado, `node --check` en el JS tocado, y revisar la página en
  móvil y escritorio.
- No hagas migraciones ni borrados en la base de datos sin autorización explícita; respalda el contenido antes de
  reemplazarlo.
