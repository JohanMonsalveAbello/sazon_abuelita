# 04 · Seguridad y pruebas

Resultado de la última ejecución: [tests/resultados/ultimo_resultado.md](../tests/resultados/ultimo_resultado.md) (**37 de 37 correctas**).

## Controles de seguridad

| ID | Implementación | Dónde | Prueba |
|---|---|---|---|
| RS-01 | `password_hash()` con Argon2id y `password_verify()`. Las cuentas sembradas en bcrypt se actualizan al iniciar sesión | `AuthService` | PT-03 |
| RS-02 | PDO con prepares nativos y parámetros enlazados. Las columnas de orden solo se aceptan de una lista blanca | `Database`, `Model` | PT-04, PT-05 |
| RS-03 | `session_regenerate_id(true)` al iniciar sesión, cookies HttpOnly y SameSite=Lax, Secure bajo HTTPS, expiración por inactividad | `Session`, `Auth` | PT-06, PT-07, V-01 |
| RS-04 | Permiso en cada ruta y control de propiedad en los controladores | `PermissionMiddleware`, métodos `autorizada()` | PT-11 a PT-14 |
| RS-05 | Token CSRF por sesión, verificado en todo POST con `hash_equals` | `CsrfMiddleware` | PT-08 |
| RS-06 | `e()` con `htmlspecialchars()` en toda salida, validación en servidor y cabecera CSP sin scripts en línea | `helpers.php`, `Validator`, `Application` | PT-09, PT-34 |
| RS-07 | Bloqueo progresivo por correo + IP tras 5 fallos (30 s, se duplica, máximo 15 min) y mensajes genéricos | `AuthService` | PT-02, PT-10 |
| RS-08 | Credenciales en `.env`, errores técnicos solo en el log, todo el tráfico entra por `public/`, subida de imágenes validada por contenido | `.htaccess`, `ErrorHandler`, `ImagenService` | PT-33, PT-37, V-02 |

Los pagos son **simulados** de forma explícita: el sistema lo advierte en pantalla, no pide datos de tarjeta y no se conecta a ninguna pasarela. Todos los datos son ficticios.

## Matriz de requisitos funcionales

| ID | Requisito | Estado | Evidencia |
|---|---|---|---|
| RF-01 | Autenticación | Cumplido | PT-01, PT-02 |
| RF-02 | Cuatro roles reales | Cumplido | PT-01, PT-11, tabla `rol_permisos`, `/permisos` |
| RF-03 | Gestión de usuarios | Cumplido | PT-15, PT-16 |
| RF-04 | Proceso principal de extremo a extremo | Cumplido | PT-21 a PT-27 |
| RF-05 | CRUD real (categorías, platos, mesas, reservas, órdenes, ítems) | Cumplido | PT-29, PT-30, PT-37 |
| RF-06 | Integridad y relaciones | Cumplido | PT-22, PT-29, `schema.sql` |
| RF-07 | Interfaz por rol | Cumplido | PT-01, evidencias de paneles |
| RF-08 | Búsqueda, filtro, orden y paginación | Cumplido | PT-05, PT-31 (reservas, órdenes, platos, usuarios y auditoría) |
| RF-09 | Dashboard, reporte filtrable y CSV | Cumplido | PT-32 |
| RF-10 | Validación en cliente y servidor | Cumplido | PT-17, PT-34 |
| RF-11 | Historial y auditoría | Cumplido | PT-28 |
| RF-12 | Navegación, errores y confirmaciones | Cumplido | PT-33, V-02; modal en `panel.js` |
| RF-13 | Tres o más reglas propias del caso | Cumplido: diez reglas | PT-17 a PT-27, PT-36 |
| RF-14 | Datos de demostración | Cumplido | `seed.sql`: una orden en cada estado, reservas pasadas, de hoy y futuras |

## Matriz de requisitos no funcionales

| ID | Dimensión | Estado | Evidencia |
|---|---|---|---|
| RNF-01 | Usabilidad | Cumplido | Estados vacíos y mensajes en todas las pantallas |
| RNF-02 | Diseño visual | Cumplido | `03_diseno_experiencia.md` |
| RNF-03 | Responsividad | Cumplido | V-03: sin desplazamiento horizontal a 360, 768 y 1366 px |
| RNF-04 | Accesibilidad | Cumplido en revisión automática | V-04: 0 violaciones de axe-core. Falta el recorrido manual con teclado |
| RNF-05 | Calidad del código | Cumplido | Capas separadas; `Validator` y parciales reutilizables |
| RNF-06 | Rendimiento | Cumplido | PT-35: respuestas de 13 a 39 ms en local con 234 órdenes y más de 800 ítems; índices en `schema.sql` |
| RNF-07 | Confiabilidad | Cumplido | Transacciones en `Services`; PT-36; V-02 |
| RNF-08 | Mantenibilidad | Cumplido | `README.md`, `.env.example`, ruta base detectada automáticamente |
| RNF-09 | Compatibilidad | Cumplido en Chrome y Edge | V-03. Firefox no se probó porque no está instalado en el equipo |
| RNF-10 | Pruebas | Cumplido | 37 pruebas automáticas y 4 verificaciones adicionales |

## Plan de pruebas automáticas

Se ejecutan con `php tests/run_tests.php`. Cada una usa HTTP real con un navegador simulado por rol y, cuando corresponde, verifica el resultado en MySQL.

| ID | Área | Prueba | Rol |
|---|---|---|---|
| PT-01 | Autenticación | Cada rol inicia sesión y ve su panel | Todos |
| PT-02 | Autenticación | Error genérico con correo existente e inexistente | Visitante |
| PT-03 | RS-01 | Contraseñas siempre como hash; actualización a Argon2id | — |
| PT-04 | RS-02 | `' OR '1'='1` en el login no da acceso | Visitante |
| PT-05 | RS-02 | Inyección en búsqueda y en orden de listados; sin error 500 | Administrador |
| PT-06 | RS-03 | Cookie HttpOnly y SameSite; ID nuevo al iniciar sesión | Cocina |
| PT-07 | RS-03 | Tras cerrar sesión, la ruta protegida no abre | Mesero |
| PT-08 | RS-05 | POST sin token o con token falso se rechaza | Administrador |
| PT-09 | RS-06 | `<script>` se muestra como texto; CSP activa | Visitante, Administrador |
| PT-10 | RS-07 | Bloqueo tras 5 fallos, incluso con la clave correcta | Visitante |
| PT-11 | RS-04 | Rutas de administración devuelven 403 a otros roles | Mesero, Cocina, Cliente |
| PT-12 | RS-04 | Rutas protegidas redirigen al login | Visitante |
| PT-13 | RS-04 | Reservas, órdenes y cuentas ajenas devuelven 403 por URL y POST | Cliente |
| PT-14 | RS-04 | No se opera la orden de otro mesero | Mesero |
| PT-15 | RF-03 | Inyectar `rol=admin` en registro y perfil no cambia el rol | Cliente |
| PT-16 | RF-03 | Usuario desactivado no entra y pierde la sesión abierta | Administrador |
| PT-17 | RN-02, RF-10 | Fuera de horario, pasado, muy lejano o inválido se rechaza | Visitante |
| PT-18 | RN-05 | Solicitud duplicada rechazada | Cliente |
| PT-19 | RN-01, RN-03 | Doble reserva y sobrecupo rechazados | Mesero |
| PT-20 | RN-04 | Cancelación permitida con anticipación y rechazada a menos de 2 h | Cliente |
| PT-21 | RF-04 | Abrir orden; orden vacía y plato agotado rechazados | Mesero |
| PT-22 | RN-06 | Dos órdenes activas en una mesa, rechazado | Mesero 2 |
| PT-23 | RF-04 | No se puede cobrar ni servir saltando estados | Mesero |
| PT-24 | RF-04 | Flujo de cocina; lo enviado no se edita; cocina no ve precios ni la orden | Mesero, Cocina |
| PT-25 | RF-04 | Nueva ronda: cocina ve solo lo nuevo | Mesero, Cocina |
| PT-26 | RN-08, RN-09 | Total del servidor con precio congelado, sin doble cobro y con mesa liberada | Mesero |
| PT-27 | RN-10 | El cliente consulta y califica; se publica solo tras aprobar | Cliente, Administrador |
| PT-28 | RF-11 | Acciones críticas auditadas, sin secretos | Administrador |
| PT-29 | RF-05, RF-06 | CRUD de mesas, duplicados, eliminación lógica y restricciones | Administrador |
| PT-30 | RF-05 | Cocina agota un plato y sale de la carta; no edita precios | Cocina |
| PT-31 | RF-08 | Búsqueda, filtro, orden y paginación | Administrador |
| PT-32 | RF-09 | Indicadores coinciden con MySQL; el CSV respeta filtros | Administrador |
| PT-33 | RF-12, RS-08 | Pantallas 403 y 404; `.env`, código y logs inaccesibles | Cliente, Visitante |
| PT-34 | RF-10 | Contraseña débil y campos inesperados, sin error 500 | Visitante |
| PT-35 | RNF-06 | Listados y reportes por debajo de 1 segundo | Administrador |
| PT-36 | RN-01 | Dos confirmaciones simultáneas: solo una gana | Mesero, Mesero 2 |
| PT-37 | RS-08 | PHP disfrazado de imagen rechazado; nombre aleatorio | Administrador |

## Verificaciones adicionales

Se hicieron una vez sobre la aplicación real, porque necesitan cambiar la configuración o usar un navegador real.

| ID | Verificación | Cómo reproducirla | Resultado |
|---|---|---|---|
| V-01 | Expiración por inactividad | `SESSION_IDLE_SECONDS=2` en `.env`, iniciar sesión y esperar 4 s | `/panel` redirige al login con "Tu sesión expiró por inactividad" |
| V-02 | Error 500 sin filtrar datos | `DB_PORT=3999` en `.env` y abrir el sitio | Pantalla amable, sin SQL ni rutas; el detalle queda en `storage/logs/app.log` |
| V-03 | Recorrido en navegador real | Chrome y Edge por las pantallas principales de cada rol a 360, 768 y 1366 px | Sin desbordes, errores de consola, violaciones de CSP ni respuestas HTTP 4xx/5xx |
| V-04 | Accesibilidad automática | axe-core 4.10, reglas WCAG A/AA, 62 vistas | 0 violaciones |

## Errores encontrados y corregidos durante las pruebas

| Error | Cómo se detectó | Corrección |
|---|---|---|
| El token CSRF inválido respondía 500: Apache no reconoce el código 419 | PT-08 | Se responde 403 con un mensaje específico |
| La barra lateral perdía su fondo en escritorio: Bootstrap fuerza transparencia en `offcanvas-lg` | Revisión visual | Fondo definido con `!important` y comentario |
| El helper `campo()` ignoraba el `type` indicado | Revisión de código | Corregido el orden de combinación de atributos |
| Un cobro indebido perdía su mensaje por una doble redirección | PT-23 | El error ahora vuelve al detalle de la orden |
| Desbordes en la tabla de platos a 768 px y en la matriz a 360 px | V-03 | Tablas apiladas bajo 992 px; contenedor con posición relativa |
| Contraste insuficiente y áreas táctiles pequeñas | V-04 | Ajustes de color y tamaño mínimo en la paginación |

## Limitaciones conocidas

- **HTTPS:** XAMPP local trabaja en HTTP. La cookie activa `Secure` automáticamente bajo HTTPS, pero esto no se probó con un certificado.
- **Fuerza bruta:** el bloqueo es por correo + IP. Evita bloquear a la víctima desde otra IP, pero no frena un ataque distribuido.
- **Notificaciones:** no se envían correos ni WhatsApp. La confirmación es manual, y un visitante sin cuenta solo conserva su código de reserva.
- **Almuerzo ejecutivo:** el bloque "Menú del Día" sigue siendo texto informativo de la interfaz original, sin un módulo propio.
- **Firefox:** no se probó.
- **Teclado:** no se hizo un recorrido manual completo.
