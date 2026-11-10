

<?php

ob_start();

require_once __DIR__ . "/../../app/includes/auth.php";


exigirRol(["Administrador", "Operador"]);

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(404);
    exit("Imagen no encontrada.");
}

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

if (!$imagen) {
    http_response_code(404);
    exit("Imagen no encontrada.");
}

$tiposPermitidos = [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/webp" => "webp"
];

$tipo = $imagen["tipo_mime"];
$nombre = $imagen["nombre_archivo"];

if (
    !isset($tiposPermitidos[$tipo]) ||
    !is_string($nombre) ||
    !preg_match('/\A[a-f0-9]{32}\.' . $tiposPermitidos[$tipo] . '\z/', $nombre)
) {
    http_response_code(404);
    exit("Imagen no encontrada.");
}

$ruta = __DIR__ . "/../../app/storage/equipos/" . $nombre;

if (!is_file($ruta)) {
    http_response_code(404);
    exit("Archivo no encontrado.");
}


while (ob_get_level() > 0) {
    ob_end_clean();
}

header("Content-Type: " . $tipo);
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, no-store");
header("Content-Length: " . filesize($ruta));

readfile($ruta);
exit;

