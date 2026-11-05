-- =====================================================================
-- El Sazón de la Abuela · Esquema de base de datos
-- Motor: MySQL 8 / MariaDB 10.4+ (XAMPP). Codificación: utf8mb4.
-- Importar primero este archivo y luego seed.sql.
-- ATENCIÓN: recrea las tablas de la base sazon_abuelita (instalación limpia).
-- =====================================================================
CREATE DATABASE IF NOT EXISTS sazon_abuelita
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sazon_abuelita;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS auditoria, intentos_login, resenas, cuentas, orden_historial,
  orden_items, ordenes, reservas, mesas, platos, categorias, usuarios, rol_permisos, roles;
SET FOREIGN_KEY_CHECKS = 1;

-- ---------------------------------------------------------------------
-- Seguridad: roles, matriz de permisos y usuarios
-- ---------------------------------------------------------------------
CREATE TABLE roles (
  id      TINYINT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo  VARCHAR(20) NOT NULL,
  nombre  VARCHAR(40) NOT NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_roles_codigo (codigo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rol_permisos (
  rol_id  TINYINT UNSIGNED NOT NULL,
  modulo  VARCHAR(20) NOT NULL,
  accion  ENUM('ver','crear','editar','aprobar','eliminar') NOT NULL,
  PRIMARY KEY (rol_id, modulo, accion),
  CONSTRAINT fk_permiso_rol FOREIGN KEY (rol_id) REFERENCES roles (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE usuarios (
  id             INT UNSIGNED NOT NULL AUTO_INCREMENT,
  rol_id         TINYINT UNSIGNED NOT NULL,
  nombre         VARCHAR(120) NOT NULL,
  correo         VARCHAR(120) NOT NULL,
  telefono       VARCHAR(20) NULL,
  password_hash  VARCHAR(255) NOT NULL,
  activo         TINYINT(1) NOT NULL DEFAULT 1,
  creado_en      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_usuarios_correo (correo),
  KEY idx_usuarios_rol_activo (rol_id, activo),
  KEY idx_usuarios_nombre (nombre),
  CONSTRAINT fk_usuario_rol FOREIGN KEY (rol_id) REFERENCES roles (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Catálogo: categorías, platos y mesas (eliminación lógica con eliminado_en)
-- ---------------------------------------------------------------------
CREATE TABLE categorias (
  id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  slug        VARCHAR(40) NOT NULL,
  nombre      VARCHAR(60) NOT NULL,
  orden       SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  eliminado_en DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_categorias_slug (slug),
  UNIQUE KEY uq_categorias_nombre (nombre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE platos (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  categoria_id     SMALLINT UNSIGNED NOT NULL,
  nombre           VARCHAR(80) NOT NULL,
  descripcion      VARCHAR(255) NOT NULL,
  descripcion_larga VARCHAR(500) NULL,
  precio           INT UNSIGNED NOT NULL,
  imagen           VARCHAR(120) NOT NULL,
  etiqueta         VARCHAR(20) NULL,
  tiempo_min       SMALLINT UNSIGNED NOT NULL DEFAULT 20,
  porciones        VARCHAR(20) NOT NULL DEFAULT '1 persona',
  disponible       TINYINT(1) NOT NULL DEFAULT 1,
  destacado        TINYINT(1) NOT NULL DEFAULT 0,
  eliminado_en     DATETIME NULL,
  creado_en        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_platos_nombre (nombre),
  KEY idx_platos_categoria (categoria_id, disponible),
  CONSTRAINT fk_plato_categoria FOREIGN KEY (categoria_id) REFERENCES categorias (id),
  CONSTRAINT ck_plato_precio CHECK (precio > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE mesas (
  id          SMALLINT UNSIGNED NOT NULL AUTO_INCREMENT,
  numero      SMALLINT UNSIGNED NOT NULL,
  capacidad   TINYINT UNSIGNED NOT NULL,
  zona        ENUM('salon','terraza','privado') NOT NULL DEFAULT 'salon',
  activa      TINYINT(1) NOT NULL DEFAULT 1,
  eliminado_en DATETIME NULL,
  PRIMARY KEY (id),
  UNIQUE KEY uq_mesas_numero (numero),
  CONSTRAINT ck_mesa_capacidad CHECK (capacidad BETWEEN 1 AND 20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Reservas
-- ---------------------------------------------------------------------
CREATE TABLE reservas (
  id               INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo           CHAR(8) NOT NULL,
  cliente_id       INT UNSIGNED NULL,
  mesa_id          SMALLINT UNSIGNED NULL,
  nombre_contacto  VARCHAR(120) NOT NULL,
  telefono         VARCHAR(20) NOT NULL,
  correo           VARCHAR(120) NOT NULL,
  fecha            DATE NOT NULL,
  hora             TIME NOT NULL,
  personas         TINYINT UNSIGNED NOT NULL,
  mensaje          VARCHAR(500) NULL,
  estado           ENUM('pendiente','confirmada','cumplida','no_asistio','cancelada') NOT NULL DEFAULT 'pendiente',
  motivo_cancelacion VARCHAR(255) NULL,
  creado_en        DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_reservas_codigo (codigo),
  KEY idx_reservas_fecha_hora (fecha, hora),
  KEY idx_reservas_estado_fecha (estado, fecha),
  KEY idx_reservas_cliente (cliente_id, fecha),
  KEY idx_reservas_mesa_fecha (mesa_id, fecha, estado),
  CONSTRAINT fk_reserva_cliente FOREIGN KEY (cliente_id) REFERENCES usuarios (id),
  CONSTRAINT fk_reserva_mesa FOREIGN KEY (mesa_id) REFERENCES mesas (id),
  CONSTRAINT ck_reserva_personas CHECK (personas BETWEEN 1 AND 20)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Proceso principal: órdenes, ítems, historial y cuenta simulada
-- ---------------------------------------------------------------------
CREATE TABLE ordenes (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  codigo        CHAR(8) NOT NULL,
  mesa_id       SMALLINT UNSIGNED NOT NULL,
  mesero_id     INT UNSIGNED NOT NULL,
  cliente_id    INT UNSIGNED NULL,
  reserva_id    INT UNSIGNED NULL,
  estado        ENUM('abierta','enviada','en_preparacion','lista','servida','pagada','cancelada') NOT NULL DEFAULT 'abierta',
  ronda_actual  TINYINT UNSIGNED NOT NULL DEFAULT 1,
  creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  actualizado_en DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  -- Columna calculada: vale el id de la mesa solo mientras la orden está activa.
  -- El índice único impide, desde la propia base, dos órdenes activas en una mesa.
  mesa_activa   SMALLINT UNSIGNED GENERATED ALWAYS AS
                (IF(estado IN ('pagada','cancelada'), NULL, mesa_id)) STORED,
  PRIMARY KEY (id),
  UNIQUE KEY uq_ordenes_codigo (codigo),
  UNIQUE KEY uq_ordenes_mesa_activa (mesa_activa),
  KEY idx_ordenes_estado_fecha (estado, creado_en),
  KEY idx_ordenes_mesero (mesero_id, estado),
  KEY idx_ordenes_cliente (cliente_id, creado_en),
  KEY idx_ordenes_creado (creado_en),
  CONSTRAINT fk_orden_mesa FOREIGN KEY (mesa_id) REFERENCES mesas (id),
  CONSTRAINT fk_orden_mesero FOREIGN KEY (mesero_id) REFERENCES usuarios (id),
  CONSTRAINT fk_orden_cliente FOREIGN KEY (cliente_id) REFERENCES usuarios (id),
  CONSTRAINT fk_orden_reserva FOREIGN KEY (reserva_id) REFERENCES reservas (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orden_items (
  id              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  orden_id        INT UNSIGNED NOT NULL,
  plato_id        INT UNSIGNED NOT NULL,
  ronda           TINYINT UNSIGNED NOT NULL DEFAULT 1,
  cantidad        SMALLINT UNSIGNED NOT NULL,
  precio_unitario INT UNSIGNED NOT NULL,   -- precio congelado al momento de pedir
  nota            VARCHAR(160) NOT NULL DEFAULT '',
  PRIMARY KEY (id),
  UNIQUE KEY uq_item_orden_plato_nota (orden_id, ronda, plato_id, nota),
  KEY idx_items_plato (plato_id),
  CONSTRAINT fk_item_orden FOREIGN KEY (orden_id) REFERENCES ordenes (id) ON DELETE CASCADE,
  CONSTRAINT fk_item_plato FOREIGN KEY (plato_id) REFERENCES platos (id),
  CONSTRAINT ck_item_cantidad CHECK (cantidad BETWEEN 1 AND 50)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE orden_historial (
  id              BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  orden_id        INT UNSIGNED NOT NULL,
  estado_anterior VARCHAR(20) NULL,
  estado_nuevo    VARCHAR(20) NOT NULL,
  usuario_id      INT UNSIGNED NOT NULL,
  nota            VARCHAR(255) NULL,
  creado_en       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_historial_orden (orden_id, creado_en),
  KEY idx_historial_estado (estado_nuevo, creado_en),
  CONSTRAINT fk_historial_orden FOREIGN KEY (orden_id) REFERENCES ordenes (id) ON DELETE CASCADE,
  CONSTRAINT fk_historial_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE cuentas (
  id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  orden_id     INT UNSIGNED NOT NULL,
  subtotal     INT UNSIGNED NOT NULL,
  impoconsumo  INT UNSIGNED NOT NULL,
  propina      INT UNSIGNED NOT NULL DEFAULT 0,
  total        INT UNSIGNED NOT NULL,
  metodo       ENUM('efectivo','tarjeta_simulada','transferencia_simulada') NOT NULL,
  cerrada_por  INT UNSIGNED NOT NULL,
  creado_en    DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_cuentas_orden (orden_id),
  KEY idx_cuentas_fecha (creado_en),
  CONSTRAINT fk_cuenta_orden FOREIGN KEY (orden_id) REFERENCES ordenes (id),
  CONSTRAINT fk_cuenta_usuario FOREIGN KEY (cerrada_por) REFERENCES usuarios (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Reseñas moderadas (alimentan testimonios e indicadores de la página pública)
-- ---------------------------------------------------------------------
CREATE TABLE resenas (
  id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  orden_id      INT UNSIGNED NOT NULL,
  cliente_id    INT UNSIGNED NOT NULL,
  calificacion  TINYINT UNSIGNED NOT NULL,
  comentario    VARCHAR(400) NOT NULL,
  ciudad        VARCHAR(60) NOT NULL DEFAULT 'Bogotá',
  estado        ENUM('pendiente','aprobada','rechazada') NOT NULL DEFAULT 'pendiente',
  creado_en     DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  UNIQUE KEY uq_resenas_orden (orden_id),
  KEY idx_resenas_estado (estado, creado_en),
  CONSTRAINT fk_resena_orden FOREIGN KEY (orden_id) REFERENCES ordenes (id),
  CONSTRAINT fk_resena_cliente FOREIGN KEY (cliente_id) REFERENCES usuarios (id),
  CONSTRAINT ck_resena_calificacion CHECK (calificacion BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Soporte de seguridad: auditoría e intentos de acceso
-- ---------------------------------------------------------------------
CREATE TABLE auditoria (
  id          BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
  usuario_id  INT UNSIGNED NULL,
  accion      VARCHAR(40) NOT NULL,
  entidad     VARCHAR(30) NOT NULL,
  entidad_id  BIGINT UNSIGNED NULL,
  detalle     VARCHAR(1000) NULL,
  ip          VARCHAR(45) NULL,
  creado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (id),
  KEY idx_auditoria_fecha (creado_en),
  KEY idx_auditoria_entidad (entidad, entidad_id),
  KEY idx_auditoria_usuario (usuario_id, creado_en),
  CONSTRAINT fk_auditoria_usuario FOREIGN KEY (usuario_id) REFERENCES usuarios (id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE intentos_login (
  clave            CHAR(64) NOT NULL,   -- SHA-256 de correo + IP
  intentos         SMALLINT UNSIGNED NOT NULL DEFAULT 0,
  bloqueado_hasta  DATETIME NULL,
  actualizado_en   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (clave),
  KEY idx_intentos_actualizado (actualizado_en)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------
-- Datos de referencia indispensables: roles y matriz de permisos (RF-02)
-- ---------------------------------------------------------------------
INSERT INTO roles (id, codigo, nombre) VALUES
  (1, 'admin',   'Administrador'),
  (2, 'mesero',  'Mesero'),
  (3, 'cocina',  'Cocina'),
  (4, 'cliente', 'Cliente');

-- Administrador: control total del sistema.
INSERT INTO rol_permisos (rol_id, modulo, accion)
SELECT 1, m.modulo, a.accion
FROM (SELECT 'usuarios' modulo UNION SELECT 'categorias' UNION SELECT 'platos' UNION SELECT 'mesas'
      UNION SELECT 'reservas' UNION SELECT 'ordenes' UNION SELECT 'cocina' UNION SELECT 'cuentas'
      UNION SELECT 'resenas') m
CROSS JOIN (SELECT 'ver' accion UNION SELECT 'crear' UNION SELECT 'editar' UNION SELECT 'aprobar' UNION SELECT 'eliminar') a;

INSERT INTO rol_permisos (rol_id, modulo, accion) VALUES
  (1,'reportes','ver'), (1,'auditoria','ver'), (1,'permisos','ver'),
  -- Mesero: atiende reservas y gestiona SUS órdenes y cuentas.
  (2,'platos','ver'), (2,'mesas','ver'),
  (2,'reservas','ver'), (2,'reservas','crear'), (2,'reservas','editar'), (2,'reservas','aprobar'), (2,'reservas','eliminar'),
  (2,'ordenes','ver'), (2,'ordenes','crear'), (2,'ordenes','editar'), (2,'ordenes','eliminar'),
  (2,'cuentas','ver'), (2,'cuentas','crear'),
  -- Cocina: comandas y disponibilidad (agotado) de platos; no ve cuentas ni clientes.
  (3,'cocina','ver'), (3,'cocina','aprobar'), (3,'platos','ver'), (3,'platos','aprobar'),
  -- Cliente: únicamente sus propios registros.
  (4,'reservas','ver'), (4,'reservas','crear'), (4,'reservas','editar'), (4,'reservas','eliminar'),
  (4,'ordenes','ver'), (4,'cuentas','ver'),
  (4,'resenas','ver'), (4,'resenas','crear');
