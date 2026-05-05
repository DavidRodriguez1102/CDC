-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS soccer_federation
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE soccer_federation;

-- Tabla de Federaciones
CREATE TABLE federaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    fecha_fundacion DATE NOT NULL,
    departamento VARCHAR(100) NOT NULL,
    municipio VARCHAR(100) NOT NULL,
    complemento VARCHAR(255),
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_nombre (nombre)
) ENGINE=InnoDB;

-- Tabla de Equipos
CREATE TABLE equipos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    federacion_id INT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (federacion_id) REFERENCES federaciones(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_nombre (nombre),
    INDEX idx_federacion (federacion_id)
) ENGINE=InnoDB;

-- Tabla de Jugadores
CREATE TABLE jugadores (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    fecha_nacimiento DATE NOT NULL,
    genero ENUM('masculino', 'femenino') NOT NULL,
    equipo_id INT NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_nombre (nombre),
    INDEX idx_equipo (equipo_id)
) ENGINE=InnoDB;

-- Tabla de usuarios para el sistema de login
CREATE TABLE usuarios (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    nombre_completo VARCHAR(100) NOT NULL,
    rol ENUM('super_admin', 'admin') NOT NULL DEFAULT 'admin',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    fecha_creacion DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username)
) ENGINE=InnoDB;

-- Insertar datos de ejemplo
INSERT INTO federaciones (nombre, fecha_fundacion, departamento, municipio, complemento) VALUES
('Federación Madrileña', '1995-04-12', 'Madrid', 'Madrid', 'Calle Principal #123'),
('Federación Catalana', '1965-10-05', 'Cataluña', 'Barcelona', 'Av. Central #456'),
('Federación Andaluza', '2002-11-22', 'Andalucía', 'Sevilla', 'Plaza Mayor #789'),
('Federación Vasca', '1992-08-31', 'País Vasco', 'Bilbao', 'Calle Norte #321'),
('Federación Gallega', '2000-01-19', 'Galicia', 'Santiago de Compostela', 'Rúa Principal #654');

INSERT INTO equipos (nombre, federacion_id) VALUES
('Lions FC', 1),
('Real Titanes', 1),
('Barcelona B', 2),
('Sevilla Atlético', 3),
('Athletic Bilbao B', 4);

INSERT INTO jugadores (nombre, fecha_nacimiento, genero, equipo_id) VALUES
('Lionel Messi', '1987-06-24', 'masculino', 1),
('Cristiano Ronaldo', '1985-02-05', 'masculino', 2),
('Alexia Putellas', '1994-02-04', 'femenino', 1),
('Aitana Bonmatí', '1998-01-18', 'femenino', 3);

-- Insertar usuario admin por defecto (contraseña: password)
INSERT INTO usuarios (username, password_hash, nombre_completo, rol) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador Principal', 'super_admin');