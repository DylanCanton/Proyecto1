
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

if (!$id || $id <= 0) {
    header("Location: ../pages/usuarios.php?error=datos");
    exit;
}

if ($id === (int) $_SESSION["usuario_id"]) {
    header("Location: ../pages/usuarios.php?error=usuario_propio");
    exit;
}

try {

    $sql = "UPDATE usuarios
            SET activo = 0
            WHERE id = :id AND activo = 1";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "id" => $id
    ]);

    header("Location: ../pages/usuarios.php?desactivado=1");
    exit;

} catch (PDOException $e) {

    error_log($e->getMessage());

    header("Location: ../pages/usuarios.php?error=guardar");
    exit;
}
