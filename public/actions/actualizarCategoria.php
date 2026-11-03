
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

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
$nombre = $_POST["nombre"] ?? "";
$descripcion = $_POST["descripcion"] ?? "";

if (!$id || $id <= 0 || !is_string($nombre) || !is_string($descripcion)) {
    header("Location: ../pages/categorias.php");
    exit;
}

$nombre = trim($nombre);
$descripcion = trim($descripcion);

if ($nombre === "" || strlen($nombre) > 100 || strlen($descripcion) > 255) {
    header("Location: ../pages/editarCategoria.php?id=$id&error=1");
    exit;
}

try {

    $sql = "UPDATE categorias
            SET nombre = :nombre, descripcion = :descripcion
            WHERE id = :id AND eliminado = 0";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "nombre" => $nombre,
        "descripcion" => $descripcion,
        "id" => $id
    ]);

    header("Location: ../pages/categorias.php");
    exit;

} catch (PDOException $e) {
    error_log($e->getMessage());
    header("Location: ../pages/editarCategoria.php?id=$id&error=1");
    exit;
}
