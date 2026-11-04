
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

$codigo = $_POST["codigo"] ?? "";
$nombre = $_POST["nombre"] ?? "";
$categoria_id = filter_input(INPUT_POST, "categoria_id", FILTER_VALIDATE_INT);
$estado = $_POST["estado"] ?? "";

if (!is_string($codigo) || !is_string($nombre) || !is_string($estado)) {
    header("Location: ../pages/crearEquipo.php?error=1");
    exit;
}

$codigo = trim($codigo);
$nombre = trim($nombre);

if (
    !preg_match('/^[A-Za-z0-9-]{1,30}$/', $codigo) ||
    $nombre === "" ||
    strlen($nombre) > 100 ||
    !$categoria_id ||
    $categoria_id <= 0 ||
    !in_array($estado, ["disponible", "mantenimiento"], true)
) {
    header("Location: ../pages/crearEquipo.php?error=1");
    exit;
}

try {

    $sql = "SELECT id FROM categorias
            WHERE id = :id AND eliminado = 0";

    $stmt = $conexion->prepare($sql);
    $stmt->execute(["id" => $categoria_id]);

    if (!$stmt->fetch()) {
        header("Location: ../pages/crearEquipo.php?error=1");
        exit;
    }

    $sql = "INSERT INTO equipos (categoria_id, codigo, nombre, estado)
            VALUES (:categoria_id, :codigo, :nombre, :estado)";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "categoria_id" => $categoria_id,
        "codigo" => $codigo,
        "nombre" => $nombre,
        "estado" => $estado
    ]);

    header("Location: ../pages/equipos.php");
    exit;

} catch (PDOException $e) {

    error_log($e->getMessage());

    header("Location: ../pages/crearEquipo.php?error=1");
    exit;
}
