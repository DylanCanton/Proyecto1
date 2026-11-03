
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol("Administrador");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/categorias.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";

if (!is_string($token) || !hash_equals($_SESSION["csrf_token"] ?? "", $token)) {
    http_response_code(403);
    exit("Solicitud no valida.");
}

$nombre = $_POST["nombre"] ?? "";
$descripcion = $_POST["descripcion"] ?? "";

if (!is_string($nombre) || !is_string($descripcion)) {
    header("Location: ../pages/crearCategoria.php?error=1");
    exit;
}

$nombre = trim($nombre);
$descripcion = trim($descripcion);

if ($nombre === "" || strlen($nombre) > 100 || strlen($descripcion) > 255) {
    header("Location: ../pages/crearCategoria.php?error=1");
    exit;
}

try {
    $sql = "INSERT INTO categorias (nombre, descripcion)
            VALUES (:nombre, :descripcion)";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "nombre" => $nombre,
        "descripcion" => $descripcion
    ]);

    header("Location: ../pages/categorias.php");
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());
    header("Location: ../pages/crearCategoria.php?error=2");
    exit;
}

