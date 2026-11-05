# 02 · Diseño técnico

## Arquitectura MVC

```mermaid
flowchart LR
  N[Navegador] -->|solicitud| H[.htaccess]
  H --> F[public/index.php]
  F --> A[Application: sesión y CSRF]
  A --> R[Router]
  R --> M[Middleware: auth y permiso]
  M --> C[Controlador]
  C --> S[Servicio: reglas de negocio]
  C --> MO[Modelo]
  S --> MO
  MO -->|PDO preparado| DB[(MySQL)]
  C --> V[Vista + layout]
  V -->|HTML escapado| N
```

| Capa | Responsabilidad | Ejemplo |
|---|---|---|
| Vista | Solo presentación; no contiene SQL | `app/Views/reservas/show.php` |
| Controlador | Interpreta la solicitud, valida el formato, comprueba la propiedad y elige la respuesta | `ReservaController::confirmar()` |
| Servicio | Reglas de negocio y transacciones | `ReservaService::confirmar()` |
| Modelo | Consultas SQL preparadas | `MesaModel::hayConflicto()` |
| Middleware | Sesión, permiso y CSRF antes del controlador | `PermissionMiddleware` |
| Core | Infraestructura reutilizable | `Router`, `Database`, `Session`, `Validator` |

## Recorrido de una solicitud: confirmar una reserva

Son los puntos que pide la defensa técnica:

| Pieza | Ubicación |
|---|---|
| Ruta | `config/routes.php`: `POST /reservas/{id}/confirmar` con `perm:reservas.aprobar` |
| Sesión | `app/Core/Session.php` |
| Validación CSRF | `app/Middleware/CsrfMiddleware.php` |
| Autorización | `app/Middleware/PermissionMiddleware.php` y `ReservaController::autorizada()` |
| Controlador | `app/Controllers/ReservaController.php`, método `confirmar` |
| Validación de datos | `Controller::validar()` y `app/Core/Validator.php` |
| Regla y transacción | `app/Services/ReservaService.php`, método `confirmar` |
| Conexión PDO | `app/Core/Database.php` |
| Consulta preparada | `app/Models/MesaModel.php`, método `hayConflicto` |
| Vista resultante | `app/Views/reservas/show.php` |

## Modelo entidad-relación

```mermaid
erDiagram
  roles ||--o{ usuarios : asigna
  roles ||--o{ rol_permisos : define
  categorias ||--o{ platos : agrupa
  usuarios ||--o{ reservas : "cliente de"
  mesas ||--o{ reservas : "asignada a"
  mesas ||--o{ ordenes : atiende
  usuarios ||--o{ ordenes : "mesero de"
  usuarios ||--o{ ordenes : "cliente de"
  reservas ||--o| ordenes : origina
  ordenes ||--|{ orden_items : contiene
  platos ||--o{ orden_items : "pedido en"
  ordenes ||--o{ orden_historial : registra
  usuarios ||--o{ orden_historial : ejecuta
  ordenes ||--o| cuentas : "se cierra con"
  usuarios ||--o{ cuentas : cierra
  ordenes ||--o| resenas : recibe
  usuarios ||--o{ resenas : escribe
  usuarios ||--o{ auditoria : genera

  usuarios { int id PK
    tinyint rol_id FK
    varchar correo UK
    varchar password_hash
    tinyint activo }
  platos { int id PK
    smallint categoria_id FK
    varchar nombre UK
    int precio
    tinyint disponible
    datetime eliminado_en }
  mesas { smallint id PK
    smallint numero UK
    tinyint capacidad
    enum zona
    datetime eliminado_en }
  reservas { int id PK
    char codigo UK
    int cliente_id FK
    smallint mesa_id FK
    date fecha
    time hora
    tinyint personas
    enum estado }
  ordenes { int id PK
    char codigo UK
    smallint mesa_id FK
    int mesero_id FK
    int cliente_id FK
    int reserva_id FK
    enum estado
    tinyint ronda_actual
    smallint mesa_activa UK }
  orden_items { int id PK
    int orden_id FK
    int plato_id FK
    tinyint ronda
    smallint cantidad
    int precio_unitario }
  cuentas { int id PK
    int orden_id FK
    int subtotal
    int impoconsumo
    int propina
    int total
    enum metodo }
  resenas { int id PK
    int orden_id FK
    int cliente_id FK
    tinyint calificacion
    enum estado }
```

Además, `intentos_login` controla la fuerza bruta. No tiene relaciones: su clave es un hash de correo e IP.

**Decisiones de integridad**

- `ordenes.mesa_activa` es una columna calculada con índice único: MySQL impide dos órdenes activas en una mesa, aunque falle la validación de la aplicación.
- `orden_items.precio_unitario` guarda el precio del momento, por lo que cambiar la carta no altera órdenes anteriores.
- `cuentas.orden_id` y `resenas.orden_id` son únicos: impiden el doble cobro y la doble reseña.
- Platos, mesas y categorías se eliminan de forma lógica (`eliminado_en`), y los usuarios se desactivan, para conservar el histórico.

## Rutas principales

La lista completa está en `config/routes.php`.

| Método y ruta | Controlador | Permiso |
|---|---|---|
| `GET /` | `HomeController@index` | Público |
| `POST /reservas/solicitar` | `ReservaController@solicitar` | Público |
| `GET/POST /login`, `/registro` | `AuthController` | Solo visitantes |
| `POST /logout` | `AuthController@logout` | Sesión |
| `GET /panel` | `PanelController@index` | Sesión |
| `GET /reservas`, `/reservas/{id}` | `ReservaController` | `reservas.ver` + propiedad |
| `POST /reservas/{id}/confirmar` | `ReservaController@confirmar` | `reservas.aprobar` |
| `POST /reservas/{id}/cancelar` | `ReservaController@cancelar` | `reservas.eliminar` + propiedad |
| `GET /ordenes`, `/ordenes/{id}` | `OrdenController` | `ordenes.ver` + propiedad |
| `POST /ordenes/{id}/items` | `OrdenController@agregarItem` | `ordenes.editar` + propiedad |
| `POST /ordenes/{id}/enviar` | `OrdenController@enviar` | `ordenes.editar` + propiedad |
| `GET /cocina`, `/cocina/tablero` | `CocinaController` | `cocina.ver` |
| `POST /cocina/{id}/iniciar`, `/lista` | `CocinaController` | `cocina.aprobar` |
| `POST /ordenes/{id}/cuenta` | `OrdenController@cobrar` | `cuentas.crear` + propiedad |
| `POST /platos/{id}/disponibilidad` | `PlatoController@disponibilidad` | `platos.aprobar` |
| `GET /reportes`, `/reportes/exportar` | `ReporteController` | `reportes.ver` |
| `GET /usuarios`, `/permisos`, `/auditoria` | Respectivos controladores | Solo Administrador |

## Flujo de negocio 1: orden de extremo a extremo

Es el proceso principal (RF-04) y participan tres roles.

```mermaid
sequenceDiagram
  actor M as Mesero
  actor C as Cocina
  actor L as Cliente
  participant S as Sistema
  M->>S: Abre orden en una mesa libre
  S-->>M: Estado abierta
  M->>S: Agrega platos con notas
  M->>S: Envía a cocina
  S-->>C: Comanda en cola
  C->>S: Empieza a preparar
  C->>S: Marca lista
  S-->>M: Orden lista para servir
  M->>S: Marca servida
  alt La mesa pide algo más
    M->>S: Nueva ronda
    S-->>M: Vuelve a abierta, ronda + 1
  else Pide la cuenta
    M->>S: Registra el pago simulado
    S-->>M: Orden pagada y mesa libre
    L->>S: Consulta su cuenta y califica
  end
```

## Flujo de negocio 2: reserva y asignación de mesa

```mermaid
flowchart TD
  A[Solicitud de reserva] --> B{¿Horario y anticipación válidos?}
  B -- No --> X[Rechazada con mensaje]
  B -- Sí --> C{¿Duplicada?}
  C -- Sí --> X
  C -- No --> D{¿Hay alguna mesa posible?}
  D -- No --> X
  D -- Sí --> E[Pendiente, con código]
  E --> F[Mesero elige una mesa libre]
  F --> G[Transacción: bloquea la mesa]
  G --> H{¿Capacidad suficiente y sin choque de 2 h?}
  H -- No --> I[Rechazada: reversión]
  H -- Sí --> J[Confirmada]
  J --> K{Llega la hora}
  K -- Llega el cliente --> L[Se abre la orden: cumplida]
  K -- No llega --> M[No asistió]
  E -. cancela .-> N[Cancelada]
  J -. cancela .-> N
```

## Transacciones

Las operaciones de varias tablas usan `Database::transaction()`: si algo falla, se revierte todo. Los casos críticos además bloquean filas con `SELECT ... FOR UPDATE`, para que dos solicitudes simultáneas se atiendan en fila. La prueba PT-36 lanza dos confirmaciones simultáneas de la misma mesa y comprueba que solo una gana.
