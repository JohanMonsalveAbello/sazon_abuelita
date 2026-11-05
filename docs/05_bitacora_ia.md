# 05 · Bitácora de uso de IA

> **Pendiente del equipo.** El documento del proyecto exige registrar las decisiones humanas y al menos cinco aportes propios. Esas partes solo las puede escribir quien las tomó, por eso están marcadas como **POR COMPLETAR**. Registrar decisiones que no ocurrieron sería una falsedad académica, y la sustentación las pondrá a prueba.

## Registro de uso

Herramienta: **Claude Code (modelo Claude Opus 5.5)**, extensión de VS Code. Fecha de todas las entradas: **2026-11-05**. No se usaron datos personales reales, secretos ni contraseñas reales en los prompts.

| # | Tarea o prompt | Resultado de la IA | Decisión humana y cambios | 
|---|---|---|---|
| 1 | "Desarrollar lo que pide el documento en PHP con MVC, respetando la interfaz existente" | Identificó el caso 13 (Restaurante), los roles y el plan de trabajo | **POR COMPLETAR** |
| 2 | Estructura MVC | Carpetas según el documento, front controller, router, middleware y núcleo propio | **POR COMPLETAR** |
| 3 | Modelo de datos | `schema.sql`, `seed.sql` y su generador, con datos ficticios | **POR COMPLETAR** |
| 4 | Reglas de negocio | Servicios de reservas y órdenes, con diez reglas y transacciones | **POR COMPLETAR** |
| 5 | Migración de la interfaz | `home/index.php` con datos de MySQL; `styles.css` sin cambios | **POR COMPLETAR** |
| 6 | Panel por rol | Vistas con Bootstrap 5, tablero de cocina, reportes y gráficas | **POR COMPLETAR** |
| 7 | Pruebas | 37 pruebas por HTTP y verificaciones de navegador y accesibilidad | **POR COMPLETAR** |
| 8 | Documentación | Análisis, diseño, matrices y manual | **POR COMPLETAR** |

## Decisiones de diseño que tomó la IA y conviene revisar

No son aportes del equipo. Sirven como punto de partida para que el equipo las evalúe, acepte, cambie o rechace, y para preparar la sustentación.

| Decisión | Alternativa | Pregunta para la defensa |
|---|---|---|
| Reemplazar las estadísticas de la página pública por cálculos reales | Conservar las cifras del diseño | ¿Por qué lo prohíbe el documento? |
| El visitante reserva sin cuenta | Exigir registro | ¿Cómo afecta la consulta de su reserva? |
| Órdenes por rondas | Una sola entrega por orden | ¿Qué ve cocina en la segunda ronda? |
| Impoconsumo del 8 % sumado al precio | Precios con impuesto incluido | ¿Cómo funciona realmente en un restaurante? |
| Reservas de 2 horas de duración fija | Duración variable por grupo | ¿Cómo se calcula el choque de horarios? |
| Índice único sobre `mesa_activa` además de la validación en PHP | Solo validar en PHP | ¿Qué protege que el código no proteja? |
| Bloqueo por correo + IP | Bloqueo solo por cuenta | ¿Qué ataque evita cada opción? |

## Errores de la IA detectados y corregidos

Quedan en `04_seguridad_y_pruebas.md`. Muestran que el código generado necesita verificación: el 419 que Apache convertía en 500, la barra lateral sin fondo y el helper que ignoraba el tipo de campo. Las pruebas o la revisión visual los detectaron durante la misma sesión de IA, así que **no cuentan como aportes del equipo**.

## Aportes técnicos propios del equipo (mínimo 5)

Cada aporte debe indicar qué sugirió la IA, qué se corrigió, rechazó o mejoró, por qué, y en qué archivo se ve.

1. **POR COMPLETAR**
2. **POR COMPLETAR**
3. **POR COMPLETAR**
4. **POR COMPLETAR**
5. **POR COMPLETAR**

Ideas de cambios reales que pueden hacer y defender: ajustar el horario o las reglas de reserva, agregar un filtro por zona a las mesas, un reporte por categoría, una notificación visual de órdenes listas o una consulta pública de reserva por código.

## Habilidades aplicadas

Evidencia en el producto. El dominio de cada una debe demostrarlo cada integrante en la sustentación.

| Habilidad | Evidencia |
|---|---|
| 1. Modelado de dominio (obligatoria) | `01_analisis.md`, `02_diseno_tecnico.md`, estados y reglas |
| 2. PHP MVC (obligatoria) | `app/`, `config/routes.php` |
| 3. Seguridad (obligatoria) | `Session`, `Csrf`, `Auth`, `Database`, `AuthService`, pruebas PT-02 a PT-16 |
| 4. UI/UX y accesibilidad (obligatoria) | `panel.css`, `03_diseno_experiencia.md`, axe-core |
| 5. Consultas SQL avanzadas | `ReporteModel`, `MesaModel`, transacciones y columna calculada |
| 6. JavaScript útil | `panel.js`: validación, modal y actualización del tablero |
| 7. Pruebas y depuración | `tests/run_tests.php`, errores corregidos |
| 9. Visualización de datos | Indicadores, gráfica de ventas y reportes |

## Librerías y material externo

| Elemento | Versión | Licencia | Uso |
|---|---|---|---|
| Bootstrap | 5.3.3 | MIT | Panel interno (copia local en `public/vendor`) |
| Bootstrap Icons | 1.11.3 | MIT | Iconos del panel (copia local) |
| DM Sans y Playfair Display | Google Fonts | SIL OFL | Tipografías |
| puppeteer-core | 23 | Apache-2.0 | Solo para verificar y tomar capturas; no forma parte del proyecto |
| axe-core | 4.10 | MPL-2.0 | Solo para la revisión de accesibilidad; no forma parte del proyecto |

**Imágenes de `public/assets/img/`:** vienen de la interfaz original. **POR COMPLETAR:** el equipo debe declarar su origen y licencia.
