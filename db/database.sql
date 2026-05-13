-- Crear la base de datos
CREATE DATABASE IF NOT EXISTS federaciones_football
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE federaciones_football;

-- Tabla de Federaciones
CREATE TABLE federaciones (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(200) NOT NULL,
    fecha_fundacion DATE NOT NULL,
    departamento VARCHAR(100) NOT NULL,
    municipio VARCHAR(100) NOT NULL,
    complemento VARCHAR(255),
    usuario_admin_id INT DEFAULT NULL,
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
    federacion_id INT DEFAULT NULL,
    FOREIGN KEY (federacion_id) REFERENCES federaciones(id) ON DELETE SET NULL ON UPDATE CASCADE,
    INDEX idx_federacion (federacion_id),
    INDEX idx_username (username)
) ENGINE=InnoDB;

-- Agregar foreign key para usuario_admin_id en federaciones
ALTER TABLE federaciones ADD CONSTRAINT fk_usuario_admin FOREIGN KEY (usuario_admin_id) REFERENCES usuarios(id) ON DELETE SET NULL ON UPDATE CASCADE;

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

-- Insertar admins para las federaciones existentes
-- (contraseña para todos: password)

INSERT INTO usuarios (username, password_hash, nombre_completo, rol, federacion_id) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador Principal', 'super_admin',NULL),
('fed_madrilena_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Federación Madrileña', 'admin', 1),
('fed_catalana_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Federación Catalana', 'admin', 2),
('fed_andaluza_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Federación Andaluza', 'admin', 3),
('fed_vasca_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Federación Vasca', 'admin', 4),
('fed_gallega_admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Admin Federación Gallega', 'admin', 5);

-- Finalmente actualizar las federaciones con el usuario_admin_id
UPDATE federaciones SET usuario_admin_id = 2 WHERE id = 1;
UPDATE federaciones SET usuario_admin_id = 3 WHERE id = 2;
UPDATE federaciones SET usuario_admin_id = 4 WHERE id = 3;
UPDATE federaciones SET usuario_admin_id = 5 WHERE id = 4;
UPDATE federaciones SET usuario_admin_id = 6 WHERE id = 5;