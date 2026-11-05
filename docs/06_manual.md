# 06 · Manual de instalación y uso

## Requisitos

- XAMPP con PHP 8.0 o superior y MariaDB/MySQL. Se probó con PHP 8.2.12 y MariaDB 10.4.32.
- Un navegador moderno (Chrome o Edge).

## Instalación desde cero

1. **Copiar el proyecto.** Copia la carpeta a `C:\xampp\htdocs\sazon_abuelita`. El nombre de la carpeta puede cambiar: el sistema detecta su ruta base.
2. **Encender los servicios.** En el panel de XAMPP, pulsa **Start** en **Apache** y **MySQL**.
3. **Crear la base de datos.** Abre `http://localhost/phpmyadmin`, entra a **Importar** e importa primero `database/schema.sql` y luego `database/seed.sql`. No hay que crear la base antes: `schema.sql` lo hace.

   También puedes hacerlo desde una terminal:

   ```text
   C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database\schema.sql
   C:\xampp\mysql\bin\mysql.exe -u root --default-character-set=utf8mb4 < database\seed.sql
   ```

4. **Configurar.** Copia `.env.example` como `.env`. Con XAMPP por defecto no hay que cambiar nada. Si tu MySQL tiene contraseña o usa otro puerto, ajústalo ahí.
5. **Abrir el sistema.** Entra a `http://localhost/sazon_abuelita/`.

> `schema.sql` **borra y recrea** las tablas de la base `sazon_abuelita`. Las demás bases de datos no se tocan.

Las fechas de `seed.sql` son relativas al momento de importar. Si la demostración es otro día, importa de nuevo justo antes para tener reservas y ventas del día.

## Cuentas de prueba

Están en el [README](../README.md#cuentas-de-demostración).

## Uso por rol

### Visitante
1. En la página pública, consulta la carta y filtra por categoría.
2. Completa el formulario de reserva. Recibirás un código.

### Cliente
1. Crea una cuenta o entra con `cliente@sazon.test`.
2. **Inicio:** tus próximas reservas y las visitas por calificar.
3. **Reservas:** crea, edita mientras esté pendiente, o cancela hasta 2 horas antes.
4. **Órdenes:** consulta tus consumos y cuentas.
5. **Reseñas:** tus opiniones y su estado.

### Mesero
1. **Reservas:** abre una pendiente, elige una mesa libre y confirma.
2. **Abrir orden:** elige la mesa o entra desde la reserva confirmada de hoy.
3. En la orden, agrega platos con sus notas y pulsa **Enviar a cocina**.
4. Cuando esté **Lista para servir**, pulsa **Marcar servida**.
5. Pulsa **Pedir algo más** para otra ronda, o **Cobrar cuenta** para registrar el pago simulado.

### Cocina
1. **Cocina:** el tablero tiene tres columnas y se actualiza solo.
2. Pulsa **Empezar a preparar** y luego **Marcar lista**.
3. **Platos:** marca un plato como agotado; deja de ofrecerse de inmediato.

### Administrador
- **Inicio:** indicadores, ventas de 14 días y platos más vendidos.
- **Usuarios, Platos, Categorías y Mesas:** gestión completa.
- **Reseñas:** aprueba o rechaza lo que se publica.
- **Reportes:** filtra por fecha, mesero y método; exporta a CSV.
- **Permisos y Auditoría:** consulta quién puede hacer qué y quién hizo qué.

## Guion sugerido para la demostración

1. Con `mesero@sazon.test`, abre una orden en una mesa libre, agrega platos y envíala.
2. En otra ventana privada, con `cocina@sazon.test`, prepárala y márcala lista.
3. Como mesero, sírvela y cóbrala.
4. En MySQL, comprueba `ordenes`, `orden_historial`, `cuentas` y `auditoria`.
5. Como cliente, intenta abrir una orden ajena cambiando el número en la URL: debe mostrar 403.
6. Escribe `<script>alert(1)</script>` en una reserva y míralo como texto.
7. Como administrador, filtra el reporte y exporta el CSV.

## Fallas frecuentes

### 1. "Not Found" de Apache al abrir `/login` u otra ruta

**Causa:** `mod_rewrite` está apagado o Apache ignora los `.htaccess`.

**Solución:** en `C:\xampp\apache\conf\httpd.conf`, verifica que esté activa la línea `LoadModule rewrite_module modules/mod_rewrite.so` y que el bloque de `htdocs` tenga `AllowOverride All`. Reinicia Apache.

### 2. Pantalla "Algo se nos quemó en la cocina" (error 500)

**Causa más común:** MySQL está apagado, no se importó la base o los datos de `.env` no coinciden.

**Solución:** enciende MySQL, verifica que exista la base `sazon_abuelita` con sus tablas y revisa `.env`. El motivo exacto queda en `storage/logs/app.log`.

### 3. MySQL o Apache no encienden en XAMPP

**Causa:** otro programa ocupa el puerto 3306 u 80.

**Solución:** cierra ese programa, o cambia el puerto en la configuración de XAMPP. Si cambias el de MySQL, ajusta `DB_PORT` en `.env`. Si cambias el de Apache, agrégalo a la URL, por ejemplo `http://localhost:8080/sazon_abuelita/`.

### Otros casos

- **"Demasiados intentos fallidos":** es el bloqueo contra fuerza bruta. Espera el tiempo indicado o, en el entorno de prueba, ejecuta `DELETE FROM intentos_login;`.
- **"Formulario caducado":** el formulario llevaba demasiado tiempo abierto o la sesión cambió. Recarga la página.

## Ejecutar las pruebas

```text
C:\xampp\php\php.exe tests\run_tests.php
```

Si tu carpeta tiene otro nombre, indica la URL como argumento: `tests\run_tests.php http://localhost/mi_carpeta`. Después, vuelve a importar `schema.sql` y `seed.sql`, porque las pruebas agregan datos.

Si Xdebug está activo en tu XAMPP, puede volver más lentas las respuestas. Se desactiva para una terminal con `set XDEBUG_MODE=off`.
