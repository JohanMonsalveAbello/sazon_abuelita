# El Sazón de la Abuela · Sistema de gestión de restaurante

Proyecto final de Desarrollo Web y Móvil (Universidad EAN) · **Caso 13: Restaurante**.
Aplicación web adaptable para gestionar carta, reservas, órdenes, preparación en cocina y cuentas simuladas.

**Stack:** HTML5, CSS3, Bootstrap 5, JavaScript, PHP puro orientado a objetos con MVC propio, MySQL/MariaDB, phpMyAdmin y XAMPP.

## Instalación rápida

1. Copia esta carpeta a `C:\xampp\htdocs\sazon_abuelita`.
2. Enciende **Apache** y **MySQL** en el panel de XAMPP.
3. En `http://localhost/phpmyadmin` importa, en este orden, `database/schema.sql` y `database/seed.sql`.
4. Copia `.env.example` como `.env` (con XAMPP por defecto no hay que cambiar nada).
5. Abre `http://localhost/sazon_abuelita/`.

La guía completa y la solución de fallas frecuentes están en [docs/06_manual.md](docs/06_manual.md).

## Cuentas de demostración

Solo para el entorno de evaluación. Todos los datos son ficticios.

| Rol | Correo | Contraseña | Qué puede hacer |
|---|---|---|---|
| Administrador | `admin@sazon.test` | `Admin#2026` | Todo: usuarios, catálogo, reportes, auditoría, moderación |
| Mesero | `mesero@sazon.test` | `Mesero#2026` | Reservas, sus propias órdenes y cobro |
| Mesero 2 | `mesero2@sazon.test` | `Mesero#2026` | Igual; sirve para probar que no opera órdenes ajenas |
| Cocina | `cocina@sazon.test` | `Cocina#2026` | Tablero de comandas y platos agotados |
| Cliente | `cliente@sazon.test` | `Cliente#2026` | Sus reservas, consumos, cuentas y reseñas |
| Cliente 2 | `cliente2@sazon.test` | `Cliente#2026` | Igual; sirve para probar el acceso a recursos ajenos |
| Inactivo | `mesero.inactivo@sazon.test` | `Mesero#2026` | No puede iniciar sesión |

## Estructura

```text
sazon_abuelita/
├── app/
│   ├── Controllers/      # Solicitudes, autorización de propiedad y flujos
│   ├── Models/           # Persistencia: SQL con PDO y consultas preparadas
│   ├── Views/            # HTML/PHP de presentación (layouts, parciales y pantallas)
│   ├── Core/             # Router, Controller, Model, Database, Session, Auth, Validator...
│   ├── Middleware/       # Auth, Guest, Permission, CSRF
│   └── Services/         # Reglas de negocio: reservas, órdenes, autenticación, auditoría
├── config/               # app, database, menu y routes (mapa de todas las rutas)
├── database/             # schema.sql, seed.sql y su generador
├── docs/                 # Análisis, diseño, pruebas, bitácora de IA, manual y evidencias
├── public/               # index.php (front controller), css, js, imágenes, Bootstrap local
├── storage/logs/         # Registros de error, no accesibles por URL
├── tests/                # Pruebas automáticas y resultados
├── .env.example          # Variables de ejemplo, sin secretos
└── README.md
```

## Pruebas

Con Apache y MySQL encendidos y la base recién importada:

```text
C:\xampp\php\php.exe tests\run_tests.php
```

Ejecuta 37 pruebas de extremo a extremo por HTTP contra la aplicación real y verifica los resultados en MySQL. El informe queda en `tests/resultados/ultimo_resultado.md`. Las pruebas agregan datos, así que al terminar vuelve a importar `schema.sql` y `seed.sql`.

## Documentación

| Documento | Contenido |
|---|---|
| [01_analisis.md](docs/01_analisis.md) | Empresa, problema, alcance, navegación, actores, historias y permisos |
| [02_diseno_tecnico.md](docs/02_diseno_tecnico.md) | Diagrama ER, arquitectura MVC, rutas y flujos de negocio |
| [03_diseno_experiencia.md](docs/03_diseno_experiencia.md) | Guía visual, componentes y evidencias responsive |
| [04_seguridad_y_pruebas.md](docs/04_seguridad_y_pruebas.md) | Controles, matriz RF/RNF, pruebas y limitaciones conocidas |
| [05_bitacora_ia.md](docs/05_bitacora_ia.md) | Uso de IA, habilidades y licencias. **Tiene secciones que debe completar el equipo** |
| [06_manual.md](docs/06_manual.md) | Instalación desde cero, uso por rol y fallas frecuentes |

La interfaz estática original se conserva sin cambios en `docs/interfaz_original/`.
