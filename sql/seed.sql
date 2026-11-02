USE proyecto1_db;

INSERT INTO roles (nombre)
VALUES
('Administrador'),
('Operador');


INSERT INTO usuarios (rol_id, nombre, correo, password_hash, activo, intentos_fallidos)
VALUES (
    (SELECT id FROM roles WHERE nombre = 'Administrador'),
    'Dylan',
    'admin@gmail.com',
    '$2y$10$P4eabLaqYomwYbdHba0YOOnPNOEMMB44BD5tbS2NM9jip81iqc5tm',
    1,
    0
);


INSERT INTO usuarios (rol_id, nombre, correo, password_hash, activo, intentos_fallidos)
VALUES (
    (SELECT id FROM roles WHERE nombre = 'Operador'),
    'Andrey',
    'operador@gmail.com',
    '$2y$10$tzv55JePbrOq0FWRjRFMBOt1JefjRjH40Af4OvxsMQYED2NwacPjy',
    1,
    0
);


INSERT INTO categorias (nombre, descripcion)
VALUES
('Computadoras', 'Computadoras del laboratorio'),
('Proyectores', 'Proyectores para presentaciones'),
('Herramientas', 'Herramientas para practicas de laboratorio');

