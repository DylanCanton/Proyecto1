
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol("Administrador");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/usuarios.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";

if (!is_string($token) || !hash_equals($_SESSION["csrf_token"] ?? "", $token)) {
    http_response_code(403);
    exit("Solicitud no valida.");
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
$nombre = $_POST["nombre"] ?? "";
$correo = $_POST["correo"] ?? "";
$rol_id = filter_input(INPUT_POST, "rol_id", FILTER_VALIDATE_INT);

if (!is_string($nombre) || !is_string($correo)) {
    header("Location: ../pages/usuarios.php?error=datos");
    exit;
}

$nombre = trim($nombre);
$correo = trim($correo);

if (
    !$id ||
    $id <= 0 ||
    $nombre === "" ||
    strlen($nombre) > 100 ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    strlen($correo) > 190 ||
    !$rol_id ||
    $rol_id <= 0
) {
    header("Location: ../pages/usuarios.php?error=datos");
    exit;
}

try {

    $sql = "SELECT nombre FROM roles WHERE id = :id";

    $stmt = $conexion->prepare($sql);
    $stmt->execute(["id" => $rol_id]);

    $nombreRol = $stmt->fetchColumn();

    if ($nombreRol === false) {
        header("Location: ../pages/usuarios.php?error=rol");
        exit;
    }

    if (
        $id === (int) $_SESSION["usuario_id"] &&
        $nombreRol !== "Administrador"
    ) {
        header("Location: ../pages/usuarios.php?error=rol_propio");
        exit;
    }

    $sql = "SELECT COUNT(*) FROM usuarios
            WHERE correo = :correo AND id != :id";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "correo" => $correo,
        "id" => $id
    ]);

    if ($stmt->fetchColumn() > 0) {
        header("Location: ../pages/usuarios.php?error=correo");
        exit;
    }

    $sql = "UPDATE usuarios
            SET nombre = :nombre,
                correo = :correo,
                rol_id = :rol_id
            WHERE id = :id";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "nombre" => $nombre,
        "correo" => $correo,
        "rol_id" => $rol_id,
        "id" => $id
    ]);

    header("Location: ../pages/usuarios.php?actualizado=1");
    exit;

} catch (PDOException $e) {

    error_log($e->getMessage());

    header("Location: ../pages/usuarios.php?error=guardar");
    exit;
}
