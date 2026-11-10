
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol("Administrador");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/equipos.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";

if (
    !is_string($token) ||
    !isset($_SESSION["csrf_token"]) ||
    !is_string($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Solicitud no valida.");
}

$equipo_id = filter_input(INPUT_POST, "equipo_id", FILTER_VALIDATE_INT);
$archivos = $_FILES["imagenes"] ?? null;

if (
    !$equipo_id ||
    $equipo_id <= 0 ||
    !is_array($archivos) ||
    !isset($archivos["name"], $archivos["tmp_name"],
           $archivos["error"], $archivos["size"]) ||
    !is_array($archivos["name"]) ||
    !is_array($archivos["tmp_name"]) ||
    !is_array($archivos["error"]) ||
    !is_array($archivos["size"])
) {
    header("Location: ../pages/equipos.php?error=datos");
    exit;
}

$total = count($archivos["name"]);

if (
    $total < 1 ||
    $total > 5 ||
    count($archivos["tmp_name"]) !== $total ||
    count($archivos["error"]) !== $total ||
    count($archivos["size"]) !== $total
) {
    header("Location: ../pages/editarEquipo.php?id=" . $equipo_id . "&error=imagenes");
    exit;
}

$tiposPermitidos = [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/webp" => "webp"
];

$tamanoMaximo = 5 * 1024 * 1024;

$carpeta = __DIR__ . "/../../app/storage/equipos";

if (!is_dir($carpeta) || !is_writable($carpeta)) {
    error_log("La carpeta de imagenes no existe o no permite escritura.");
    header("Location: ../pages/editarEquipo.php?id=" . $equipo_id . "&error=carpeta");
    exit;
}

$detector = new finfo(FILEINFO_MIME_TYPE);
$imagenesValidadas = [];

foreach ($archivos["name"] as $i => $nombreOriginal) {

    $error = $archivos["error"][$i] ?? UPLOAD_ERR_NO_FILE;
    $temporal = $archivos["tmp_name"][$i] ?? "";
    $tamano = $archivos["size"][$i] ?? 0;

    if (
        $error !== UPLOAD_ERR_OK ||
        !is_string($temporal) ||
        !is_uploaded_file($temporal) ||
        !is_int($tamano) ||
        $tamano <= 0 ||
        $tamano > $tamanoMaximo
    ) {
        header("Location: ../pages/editarEquipo.php?id=" . $equipo_id . "&error=imagenes");
        exit;
    }

    $mime = $detector->file($temporal);

    if (!is_string($mime) || !isset($tiposPermitidos[$mime])) {
        header("Location: ../pages/editarEquipo.php?id=" . $equipo_id . "&error=formato");
        exit;
    }

    if (getimagesize($temporal) === false) {
        header("Location: ../pages/editarEquipo.php?id=" . $equipo_id . "&error=formato");
        exit;
    }

    $imagenesValidadas[] = [
        "temporal" => $temporal,
        "mime" => $mime
    ];
}

$archivosGuardados = [];

try {

    $conexion->beginTransaction();

    $sql = "SELECT id
            FROM equipos
            WHERE id = :id
            AND eliminado_en IS NULL
            FOR UPDATE";

    $stmt = $conexion->prepare($sql);
    $stmt->execute(["id" => $equipo_id]);

    $existe = $stmt->fetchColumn();
    $stmt->closeCursor();

    if (!$existe) {
        throw new RuntimeException("El equipo no existe.");
    }

    $sql = "INSERT INTO equipo_imagenes
            (equipo_id, nombre_archivo, tipo_mime)
            VALUES (:equipo_id, :nombre_archivo, :tipo_mime)";

    $stmt = $conexion->prepare($sql);

    foreach ($imagenesValidadas as $imagen) {

        $extension = $tiposPermitidos[$imagen["mime"]];

        $nombreNuevo = bin2hex(random_bytes(16)) . "." . $extension;

        $ruta = $carpeta . DIRECTORY_SEPARATOR . $nombreNuevo;

        if (!move_uploaded_file($imagen["temporal"], $ruta)) {
            throw new RuntimeException("No fue posible guardar la imagen.");
        }

        $archivosGuardados[] = $ruta;

        $stmt->execute([
            "equipo_id" => $equipo_id,
            "nombre_archivo" => $nombreNuevo,
            "tipo_mime" => $imagen["mime"]
        ]);
    }

    $conexion->commit();

    header("Location: ../pages/editarEquipo.php?id=" . $equipo_id . "&imagenes=ok");
    exit;

} catch (Throwable $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    foreach ($archivosGuardados as $ruta) {
        if (is_file($ruta)) {
            unlink($ruta);
        }
    }

    error_log($e->getMessage());

    header("Location: ../pages/editarEquipo.php?id=" . $equipo_id . "&error=subida");
    exit;
}
