
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

$nombre = $_POST["nombre"] ?? "";
$correo = $_POST["correo"] ?? "";
$password = $_POST["password"] ?? "";
$rol_id = filter_input(INPUT_POST, "rol_id", FILTER_VALIDATE_INT);

if (!is_string($nombre) || !is_string($correo) || !is_string($password)) {
    header("Location: ../pages/crearUsuario.php?error=datos");
    exit;
}

$nombre = trim($nombre);
$correo = trim($correo);

if (
    $nombre === "" ||
    strlen($nombre) > 100 ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    strlen($correo) > 190 ||
    strlen($password) < 8 ||
    strlen($password) > 72 ||
    !$rol_id ||
    $rol_id <= 0
) {
    header("Location: ../pages/crearUsuario.php?error=datos");
    exit;
}

try {

    $sql = "SELECT COUNT(*) FROM roles WHERE id = :id";

    $stmt = $conexion->prepare($sql);
    $stmt->execute(["id" => $rol_id]);

    if ($stmt->fetchColumn() == 0) {
        header("Location: ../pages/crearUsuario.php?error=rol");
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    $sql = "INSERT INTO usuarios
            (nombre, correo, password_hash, rol_id, activo)
            VALUES
            (:nombre, :correo, :password_hash, :rol_id, 1)";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "nombre" => $nombre,
        "correo" => $correo,
        "password_hash" => $passwordHash,
        "rol_id" => $rol_id
    ]);

    header("Location: ../pages/usuarios.php");
    exit;

} catch (PDOException $e) {

    error_log($e->getMessage());

    header("Location: ../pages/crearUsuario.php?error=guardar");
    exit;
}

