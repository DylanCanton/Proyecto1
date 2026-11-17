
<?php

// Iniciar el almacenamiento temporal de salida
ob_start();

// Cargar la autenticacion
require_once __DIR__ . "/../../app/includes/auth.php";

// Permitir acceso al administrador y operador
exigirRol(["Administrador", "Operador"]);

// Obtener y validar el ID de la imagen
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(404);
    exit("Imagen no encontrada.");
}

// Consultar los datos de la imagen
$sql = "SELECT equipo_imagenes.nombre_archivo,
               equipo_imagenes.tipo_mime
        FROM equipo_imagenes
        INNER JOIN equipos
        ON equipo_imagenes.equipo_id = equipos.id
        WHERE equipo_imagenes.id = :id
        AND equipos.eliminado_en IS NULL";

$stmt = $conexion->prepare($sql);
$stmt->execute(["id" => $id]);

$imagen = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar que la imagen exista
if (!$imagen) {
    http_response_code(404);
    exit("Imagen no encontrada.");
}

// Definir los formatos permitidos
$tiposPermitidos = [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/webp" => "webp"
];

$tipo = $imagen["tipo_mime"];
$nombre = $imagen["nombre_archivo"];

// Validar el formato y nombre del archivo
if (
    !isset($tiposPermitidos[$tipo]) ||
    !is_string($nombre) ||
    !preg_match('/\A[a-f0-9]{32}\.' . $tiposPermitidos[$tipo] . '\z/', $nombre)
) {
    http_response_code(404);
    exit("Imagen no encontrada.");
}

// Buscar la imagen en la carpeta del servidor
$ruta = __DIR__ . "/../../app/storage/equipos/" . $nombre;

// Verificar que el archivo exista
if (!is_file($ruta)) {
    http_response_code(404);
    exit("Archivo no encontrado.");
}

// Limpiar cualquier salida anterior
while (ob_get_level() > 0) {
    ob_end_clean();
}

// Configurar las cabeceras de seguridad
header("Content-Type: " . $tipo);
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, no-store");
header("Content-Length: " . filesize($ruta));

// Mostrar la imagen al navegador
readfile($ruta);
exit;
