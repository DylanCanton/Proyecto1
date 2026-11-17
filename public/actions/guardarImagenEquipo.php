
<?php

// Cargar la autenticacion
require_once __DIR__ . "/../../app/includes/auth.php";

// Permitir solo al administrador
exigirRol("Administrador");

// Verificar el metodo POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/equipos.php");
    exit;
}

// Validar el token CSRF
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

// Recibir el ID del equipo y las imagenes
$equipo_id = filter_input(INPUT_POST, "equipo_id", FILTER_VALIDATE_INT);
$archivos = $_FILES["imagenes"] ?? null;

// Validar los datos recibidos
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

// Permitir entre 1 y 5 imagenes por subida
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

// Definir los formatos permitidos
$tiposPermitidos = [
    "image/jpeg" => "jpg",
    "image/png" => "png",
    "image/webp" => "webp"
];

// Establecer un tamaño maximo de 5 MB
$tamanoMaximo = 5 * 1024 * 1024;

// Definir la carpeta donde se guardan las imagenes
$carpeta = __DIR__ . "/../../app/storage/equipos";

// Verificar que la carpeta permita guardar archivos
if (!is_dir($carpeta) || !is_writable($carpeta)) {
    error_log("La carpeta de imagenes no existe o no permite escritura.");
    header("Location: ../pages/editarEquipo.php?id=" . $equipo_id . "&error=carpeta");
    exit;
}

// Preparar la validacion de los archivos
$detector = new finfo(FILEINFO_MIME_TYPE);
$imagenesValidadas = [];

foreach ($archivos["name"] as $i => $nombreOriginal) {

    // Obtener los datos de cada imagen
    $error = $archivos["error"][$i] ?? UPLOAD_ERR_NO_FILE;
    $temporal = $archivos["tmp_name"][$i] ?? "";
    $tamano = $archivos["size"][$i] ?? 0;

    // Comprobar que la imagen se haya recibido correctamente
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

    // Comprobar el formato real del archivo
    $mime = $detector->file($temporal);

    if (!is_string($mime) || !isset($tiposPermitidos[$mime])) {
        header("Location: ../pages/editarEquipo.php?id=" . $equipo_id . "&error=formato");
        exit;
    }

    // Verificar que el archivo sea una imagen
    if (getimagesize($temporal) === false) {
        header("Location: ../pages/editarEquipo.php?id=" . $equipo_id . "&error=formato");
        exit;
    }

    // Guardar las imagenes que pasaron la validacion
    $imagenesValidadas[] = [
        "temporal" => $temporal,
        "mime" => $mime
    ];
}

$archivosGuardados = [];

try {

    // Iniciar una transaccion en MySQL
    $conexion->beginTransaction();

    // Verificar que el equipo exista
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

    // Preparar el registro de las imagenes
    $sql = "INSERT INTO equipo_imagenes
            (equipo_id, nombre_archivo, tipo_mime)
            VALUES (:equipo_id, :nombre_archivo, :tipo_mime)";

    $stmt = $conexion->prepare($sql);

    foreach ($imagenesValidadas as $imagen) {

        $extension = $tiposPermitidos[$imagen["mime"]];

        // Generar un nombre unico para la imagen
        $nombreNuevo = bin2hex(random_bytes(16)) . "." . $extension;

        $ruta = $carpeta . DIRECTORY_SEPARATOR . $nombreNuevo;

        // Mover la imagen a la carpeta del servidor
        if (!move_uploaded_file($imagen["temporal"], $ruta)) {
            throw new RuntimeException("No fue posible guardar la imagen.");
        }

        $archivosGuardados[] = $ruta;

        // Registrar la imagen en MySQL
        $stmt->execute([
            "equipo_id" => $equipo_id,
            "nombre_archivo" => $nombreNuevo,
            "tipo_mime" => $imagen["mime"]
        ]);
    }

    // Confirmar los cambios en MySQL
    $conexion->commit();

    header("Location: ../pages/editarEquipo.php?id=" . $equipo_id . "&imagenes=ok");
    exit;

} catch (Throwable $e) {

    // Deshacer la transaccion si ocurre un error
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    // Eliminar los archivos guardados si falla el proceso
    foreach ($archivosGuardados as $ruta) {
        if (is_file($ruta)) {
            unlink($ruta);
        }
    }

    // Registrar el error en el servidor
    error_log($e->getMessage());

    header("Location: ../pages/editarEquipo.php?id=" . $equipo_id . "&error=subida");
    exit;
}
