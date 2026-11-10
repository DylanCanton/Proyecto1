
CREATE DATABASE IF NOT EXISTS proyecto1_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE proyecto1_db;

CREATE TABLE IF NOT EXISTS roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    rol_id INT NOT NULL,
    nombre VARCHAR(100) NOT NULL,
    correo VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    intentos_fallidos INT NOT NULL DEFAULT 0,
    FOREIGN KEY (rol_id) REFERENCES roles(id)
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS categorias (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255),
    eliminado TINYINT(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    codigo VARCHAR(30) NOT NULL UNIQUE,
    nombre VARCHAR(100) NOT NULL,
    estado VARCHAR(30) NOT NULL DEFAULT 'disponible',
    version INT NOT NULL DEFAULT 1,
    eliminado_en DATETIME DEFAULT NULL,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id)
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS configuracion (
    clave VARCHAR(100) PRIMARY KEY,
    valor INT NOT NULL,
    CHECK (valor >= 1)
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS prestamos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id INT NOT NULL,
    operador_id INT NOT NULL,
    fecha_inicio DATETIME NOT NULL,
    fecha_fin DATETIME NOT NULL,
    estado ENUM(
        'solicitado',
        'aprobado',
        'entregado',
        'devuelto',
        'cancelado'
    ) NOT NULL DEFAULT 'solicitado',
    creado_en TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    FOREIGN KEY (operador_id) REFERENCES usuarios(id),

    CHECK (fecha_fin > fecha_inicio)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS prestamo_detalle (
    id INT AUTO_INCREMENT PRIMARY KEY,
    prestamo_id INT NOT NULL,
    equipo_id INT NOT NULL,
    fecha_devolucion DATETIME NULL,

    UNIQUE (prestamo_id, equipo_id),

    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id),
    FOREIGN KEY (equipo_id) REFERENCES equipos(id)
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS equipo_imagenes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    equipo_id INT NOT NULL,
    nombre_archivo VARCHAR(100) NOT NULL UNIQUE,
    tipo_mime VARCHAR(30) NOT NULL,
    fecha_subida TIMESTAMP DEFAULT CURRENT_TIMESTAMP,

    FOREIGN KEY (equipo_id) REFERENCES equipos(id)
) ENGINE=InnoDB;
