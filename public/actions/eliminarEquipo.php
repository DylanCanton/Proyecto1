
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol("Administrador");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/equipos.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";

if (!is_string($token) || !hash_equals($_SESSION["csrf_token"] ?? "", $token)) {
    http_response_code(403);
    exit("Solicitud no valida.");
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: ../pages/equipos.php");
    exit;
}

try {

    $sql = "UPDATE equipos
            SET eliminado_en = NOW(),
                version = version + 1
            WHERE id = :id AND eliminado_en IS NULL";

    $stmt = $conexion->prepare($sql);
    $stmt->execute(["id" => $id]);

    header("Location: ../pages/equipos.php");
    exit;

} catch (PDOException $e) {

    error_log($e->getMessage());

    header("Location: ../pages/equipos.php?error=1");
    exit;
}