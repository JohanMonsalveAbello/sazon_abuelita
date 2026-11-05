# Resultado de pruebas automáticas

- Fecha: 2026-11-05 02:29
- URL: http://localhost/sazon_abuelita
- PHP: 8.2.12
- Resultado: **37 de 37 correctas**

| ID | Área | Prueba | Resultado | Evidencia | ms |
|---|---|---|---|---|---|
| PT-01 | Autenticación | Cada uno de los 4 roles inicia sesión y ve su panel propio | Correcta | 4 paneles distintos renderizados | 2344 |
| PT-02 | Autenticación | Credenciales erróneas: mensaje genérico, sin revelar si el correo existe | Correcta | Mismo mensaje con correo existente e inexistente | 446 |
| PT-03 | Seguridad RS-01 | Contraseñas guardadas como hash; al iniciar sesión se actualizan a Argon2id | Correcta | $argon2id$v=19$m… | 0 |
| PT-04 | Seguridad RS-02 | Inyección SQL en el login (' OR '1'='1) no entra ni altera la consulta | Correcta | Sin acceso; formato rechazado | 94 |
| PT-05 | Seguridad RS-02 | Inyección SQL en búsqueda y orden de listados: tabla intacta, sin error 500 | Correcta | La entrada se trató como texto literal: 0 resultados | 234 |
| PT-06 | Seguridad RS-03 | Cookie de sesión HttpOnly y SameSite; ID nuevo al iniciar sesión | Correcta | HttpOnly, SameSite=Lax, ID regenerado | 300 |
| PT-07 | Seguridad RS-03 | Tras cerrar sesión, una ruta protegida deja de abrir | Correcta | GET /panel redirige a /login | 394 |
| PT-08 | Seguridad RS-05 | Un POST sin token CSRF válido es rechazado | Correcta | HTTP 403 en ambos casos; nada se guardó | 44 |
| PT-09 | Seguridad RS-06 | Texto con <script> se muestra escapado y no se ejecuta | Correcta | Se muestra &lt;script&gt;…; además CSP bloquea scripts en línea | 145 |
| PT-10 | Seguridad RS-07 | Fuerza bruta: tras 5 fallos se bloquea el intento, incluso con la clave correcta | Correcta | Bloqueo progresivo activo (30 s, luego se duplica) | 1319 |
| PT-11 | Autorización RS-04 | Rutas de administración denegadas por URL a mesero, cocina y cliente (403) | Correcta | 18 combinaciones rol/ruta devuelven 403 | 416 |
| PT-12 | Autorización RS-04 | Visitante sin sesión es enviado al login | Correcta | 6 rutas protegidas redirigen a /login | 87 |
| PT-13 | Autorización RS-04 | Cliente no accede a reservas ni órdenes ajenas por URL ni por POST | Correcta | HTTP 403 y sin cambios en MySQL | 158 |
| PT-14 | Autorización RS-04 | Mesero no opera la orden de otro mesero | Correcta | HTTP 403; la orden sigue «lista» | 90 |
| PT-15 | Autorización RF-03 | Registro con rol=admin inyectado crea un Cliente; nadie se autoasigna administrador | Correcta | Rol almacenado = Cliente en ambos intentos | 363 |
| PT-16 | Autorización RF-03 | Usuario desactivado no entra y pierde la sesión abierta | Correcta | Sesión invalidada de inmediato | 848 |
| PT-17 | RN-02 / RF-10 | Reserva pública fuera de horario, en el pasado o con datos inválidos es rechazada | Correcta | 6 casos rechazados con mensaje claro | 344 |
| PT-18 | RN-05 | No se aceptan reservas duplicadas (mismo correo, fecha y hora) | Correcta | Segunda solicitud rechazada | 166 |
| PT-19 | RN-01 / RN-03 | Una mesa no admite dos reservas a menos de 2 h, ni un grupo mayor a su capacidad | Correcta | Doble reserva y sobrecupo rechazados | 464 |
| PT-20 | RN-04 | El cliente cancela con anticipación, pero no una reserva a menos de 2 h | Correcta | Regla de 2 horas verificada en MySQL | 180 |
| PT-21 | RF-04 Mesero | Mesero abre orden, agrega platos; orden vacía no va a cocina; plato agotado rechazado | Correcta | Orden #235 con 2 ítems | 336 |
| PT-22 | RN-06 | Una mesa no puede tener dos órdenes activas | Correcta | Rechazado por regla; además índice único en BD | 41 |
| PT-23 | RF-04 Estados | No se puede saltar estados: cobrar o servir una orden aún en edición | Correcta | Estado intacto: abierta | 152 |
| PT-24 | RF-04 Cocina | Mesero envía; Cocina recibe y prepara; un ítem enviado ya no se puede cambiar; cocina no cobra | Correcta | enviada → en_preparacion → lista | 357 |
| PT-25 | RF-04 Rondas | Tras servir se puede pedir otra ronda y la cocina solo ve lo nuevo | Correcta | Ronda 2 servida | 503 |
| PT-26 | RN-09 Cuenta | El total lo calcula el servidor (precio congelado + 8 % + propina), aunque cambie el precio | Correcta | Total 132160; mesa liberada; sin doble cobro | 174 |
| PT-27 | RF-04 Cliente | El cliente consulta su orden y cuenta, y la reseña se publica solo tras aprobarla | Correcta | Flujo completo: orden → cuenta → reseña → moderación | 282 |
| PT-28 | RF-11 Auditoría | Las operaciones críticas quedan auditadas y sin secretos | Correcta | 5 tipos de acción sobre la orden; 0 secretos | 35 |
| PT-29 | RF-05 CRUD | CRUD de mesas y categorías con duplicados y eliminación lógica | Correcta | Crear, duplicado, editar, eliminar lógico y restricciones | 354 |
| PT-30 | RF-05 Cocina/Platos | Cocina marca un plato agotado y sale de la carta pública; no puede editar precios | Correcta | Disponibilidad reflejada al instante | 170 |
| PT-31 | RF-08 Listados | Búsqueda, filtro, orden y paginación en reservas y órdenes | Correcta | Filtros combinados y paginación correctos | 66 |
| PT-32 | RF-09 Reportes | Indicadores coinciden con MySQL y el CSV respeta los filtros | Correcta | 66 filas; total $11.741.940 | 69 |
| PT-33 | RF-12 Errores | Pantallas 403 y 404 propias; archivos privados inaccesibles por URL | Correcta | 6 archivos privados devuelven 403 o 404, sin contenido | 137 |
| PT-34 | RF-14 / Validación | Validación de servidor en contraseña débil y campos con valor inesperado | Correcta | Errores claros, sin error 500 y sin devolver la clave | 57 |
| PT-36 | RN-01 Concurrencia | Dos confirmaciones simultáneas de la misma mesa: solo una gana (transacción y bloqueo) | Correcta | Exactamente 1 confirmada; la otra sigue pendiente | 71 |
| PT-37 | RS-08 Archivos | Subida de fotos: un PHP disfrazado se rechaza; una imagen real recibe nombre aleatorio | Correcta | Tipo leído del contenido; nombre aleatorio y extensión forzada | 154 |
| PT-35 | RNF-06 Rendimiento | Listados paginados y reportes responden en menos de 1 segundo | Correcta | /panel 17 ms, /ordenes 26 ms, /reservas 39 ms, /reportes 37 ms, /auditoria 13 ms | 134 |
