# 03 · Diseño de experiencia

## Principio

La identidad parte de la interfaz original del restaurante: una cocina tradicional, cálida y familiar. El panel interno usa Bootstrap 5, pero con la paleta y la tipografía de esa interfaz, para que el sitio público y la herramienta de trabajo se sientan como un mismo producto.

## Paleta

| Uso | Color | Valor |
|---|---|---|
| Fondo general | Crema | `#f8f2eb` |
| Tarjetas | Blanco cálido | `#fffdfa` |
| Texto | Café oscuro | `#2d211b` |
| Texto secundario | Café medio | `#6d5c52` |
| Acción principal y marca | Terracota | `#aa583b` |
| Barra lateral | Carbón | `#231914` |
| Foco de teclado | Dorado | `#e4b23b` |

## Tipografía

- **Playfair Display** en títulos: evoca la carta de un restaurante tradicional.
- **DM Sans** en textos, datos y formularios: es muy legible en tamaños pequeños.

## Componentes principales

| Componente | Uso | Implementación |
|---|---|---|
| Campos de formulario | Etiqueta, error y accesibilidad en un solo lugar | `campo()`, `campo_select()` en `app/Core/helpers.php` |
| Barra de filtros | Búsqueda y filtros en listados | `partials/filtros.php` |
| Tabla apilable | En pantallas angostas cada fila se vuelve una tarjeta | Clase `.tabla-apilable` |
| Paginación | Conserva los filtros activos | `partials/paginacion.php` |
| Insignia de estado | Siempre icono y texto, nunca solo color | `insignia_estado()` |
| Indicador | Cifra calculada con MySQL | `partials/kpi.php` |
| Estado vacío | Explica qué pasa y qué hacer | `partials/vacio.php` |
| Confirmación | Modal antes de acciones destructivas | `data-confirmar` + `public/js/panel.js` |
| Progreso de orden | Muestra en qué paso va la orden | `.pasos` en `ordenes/show.php` |
| Tablero de cocina | Tres columnas que se actualizan cada 15 s | `cocina/_tablero.php` |

## Microinteracciones justificadas

- **Tablero que se actualiza solo:** en cocina no hay tiempo de recargar. No reemplaza el contenido si alguien está a punto de pulsar un botón.
- **Tiempo de comanda en rojo a partir de 30 minutos:** señala qué está demorado, con el tiempo escrito además del color.
- **Bloqueo del botón al enviar:** evita registros dobles por un doble clic.

## Cambios sobre la interfaz original

`css/styles.css` no se modificó. Los ajustes están en `public/css/publico-extra.css` y en la vista `home/index.php`:

| Cambio | Motivo |
|---|---|
| Platos, precios, categorías y testimonios vienen de MySQL | RF-05: el contenido debe ser real |
| "4.9 Calificación Google", "1,200+", "98 %" y "324 reseñas" ahora se calculan con las reseñas aprobadas; las etiquetas cambiaron a "Calificación Promedio" y "Reseñas Publicadas" | El documento prohíbe las estadísticas inventadas |
| "Enviar Reserva por WhatsApp" pasa a "Enviar Reserva" | La reserva ahora se guarda en la base de datos |
| El plato estrella se llama "Ajiaco Santafereño", no "Ajiaco Bogotano" | Es el mismo registro de la carta |
| Botón de menú visible entre 721 y 1000 px; enlaces de cuenta en el menú móvil | En esos anchos el menú original quedaba inaccesible y en móvil no había cómo iniciar sesión |
| Contraste ajustado en botón verde, indicadores, tarjeta del plato estrella y menú del día | Referencia WCAG 2.2 AA |

## Accesibilidad

- Etiquetas asociadas a cada campo, enlace para saltar al contenido, foco visible y marcado semántico.
- Los estados combinan icono y texto. La gráfica de ventas incluye una vista en tabla.
- Revisión automática con **axe-core 4.10** (reglas WCAG 2.0, 2.1 y 2.2 A/AA) en 62 vistas, a 1366 y 360 px: **0 violaciones**.
- Una herramienta automática no detecta todos los problemas. Conviene recorrer el sistema con teclado antes de la sustentación.

## Evidencias responsive

Capturas reales en [docs/evidencias/](evidencias/). Un recorrido automatizado por las pantallas principales a 360, 768 y 1366 px no encontró desplazamiento horizontal de página ni errores de consola, en Chrome ni en Edge.

| Pantalla | Escritorio | Tableta | Móvil |
|---|---|---|---|
| Página pública | [1366](evidencias/landing-1366.jpg) | [768](evidencias/landing-768.jpg) | [360](evidencias/landing-360.jpg) |
| Panel de administrador | [1366](evidencias/admin-panel-1366.jpg) | [768](evidencias/admin-panel-768.jpg) | [360](evidencias/admin-panel-360.jpg) |
| Reservas | [1366](evidencias/admin-reservas-1366.jpg) | | [360](evidencias/admin-reservas-360.jpg) |
| Usuarios | | [768](evidencias/admin-usuarios-768.jpg) | |
| Reportes | [1366](evidencias/admin-reportes-1366.jpg) | | |
| Permisos | [1366](evidencias/admin-permisos-1366.jpg) | | |
| Panel de mesero | [1366](evidencias/mesero-panel-1366.jpg) | | |
| Detalle de orden | [1366](evidencias/mesero-orden-1366.jpg) | | [360](evidencias/mesero-orden-360.jpg) |
| Cuenta | | | [360](evidencias/mesero-cuenta-360.jpg) |
| Tablero de cocina | [1366](evidencias/cocina-cocina-1366.jpg) | | [360](evidencias/cocina-cocina-360.jpg) |
| Panel de cliente | [1366](evidencias/cliente-panel-1366.jpg) | | [360](evidencias/cliente-panel-360.jpg) |
| Menú móvil y login | | | [Menú](evidencias/admin-menu-movil-360.jpg) · [Login](evidencias/login-360.jpg) |
