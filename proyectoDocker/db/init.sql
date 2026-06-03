CREATE TABLE IF NOT EXISTS productos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    descripcion TEXT,
    precio DECIMAL(10,2) NOT NULL,
    fecha_creacion TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

INSERT INTO productos (nombre, descripcion, precio) VALUES
('Notebook', 'Equipo portátil para oficina', 650000),
('Mouse inalámbrico', 'Mouse óptico USB', 12000),
('Teclado mecánico', 'Teclado retroiluminado', 35000);
