<?php
$db_file = __DIR__ . '/../db/soccer_federation.db';
$db = new PDO('sqlite:' . $db_file);
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sql = "
-- Tabla de Federaciones
CREATE TABLE IF NOT EXISTS federaciones (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    fecha_fundacion TEXT NOT NULL,
    departamento TEXT NOT NULL,
    municipio TEXT NOT NULL,
    complemento TEXT,
    activo INTEGER NOT NULL DEFAULT 1,
    fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
);

-- Tabla de Equipos
CREATE TABLE IF NOT EXISTS equipos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    federacion_id INTEGER NOT NULL,
    activo INTEGER NOT NULL DEFAULT 1,
    fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (federacion_id) REFERENCES federaciones(id)
);

-- Tabla de Jugadores
CREATE TABLE IF NOT EXISTS jugadores (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    nombre TEXT NOT NULL,
    fecha_nacimiento TEXT NOT NULL,
    genero TEXT NOT NULL CHECK (genero IN ('masculino', 'femenino')),
    equipo_id INTEGER NOT NULL,
    activo INTEGER NOT NULL DEFAULT 1,
    fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_actualizacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (equipo_id) REFERENCES equipos(id)
);

-- Tabla de usuarios para el sistema de login
CREATE TABLE IF NOT EXISTS usuarios (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password_hash TEXT NOT NULL,
    nombre_completo TEXT NOT NULL,
    rol TEXT NOT NULL DEFAULT 'admin' CHECK (rol IN ('super_admin', 'admin')),
    activo INTEGER NOT NULL DEFAULT 1,
    fecha_creacion TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso TEXT
);

-- Insertar datos de ejemplo
INSERT OR IGNORE INTO federaciones (nombre, fecha_fundacion, departamento, municipio, complemento) VALUES
('Federación Madrileña', '1995-04-12', 'Madrid', 'Madrid', 'Calle Principal #123'),
('Federación Catalana', '1965-10-05', 'Cataluña', 'Barcelona', 'Av. Central #456'),
('Federación Andaluza', '2002-11-22', 'Andalucía', 'Sevilla', 'Plaza Mayor #789'),
('Federación Vasca', '1992-08-31', 'País Vasco', 'Bilbao', 'Calle Norte #321'),
('Federación Gallega', '2000-01-19', 'Galicia', 'Santiago de Compostela', 'Rúa Principal #654');

INSERT OR IGNORE INTO equipos (nombre, federacion_id) VALUES
('Lions FC', 1),
('Real Titanes', 1),
('Barcelona B', 2),
('Sevilla Atlético', 3),
('Athletic Bilbao B', 4);

INSERT OR IGNORE INTO jugadores (nombre, fecha_nacimiento, genero, equipo_id) VALUES
('Lionel Messi', '1987-06-24', 'masculino', 1),
('Cristiano Ronaldo', '1985-02-05', 'masculino', 2),
('Alexia Putellas', '1994-02-04', 'femenino', 1),
('Aitana Bonmatí', '1998-01-18', 'femenino', 3);

-- Insertar usuario admin por defecto (contraseña: admin123)
INSERT OR IGNORE INTO usuarios (username, password_hash, nombre_completo, rol) VALUES
('admin', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador Principal', 'super_admin');
";

$db->exec($sql);
echo "Database initialized successfully.";
?>