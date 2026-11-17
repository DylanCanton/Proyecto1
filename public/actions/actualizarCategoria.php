
<?php

// Carga la autenticacion
require_once __DIR__ . "/../../app/includes/auth.php";

// Permiti solo al administrador
exigirRol("Administrador");

// Verifica el metodo POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/categorias.php");
    exit;
}

// Valida el token CSRF
$token = $_POST["csrf_token"] ?? "";

if (!is_string($token) || !hash_equals($_SESSION["csrf_token"] ?? "", $token)) {
    http_response_code(403);
    exit("Solicitud no valida.");
}

// Recibe los datos del formulario
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
$nombre = $_POST["nombre"] ?? "";
$descripcion = $_POST["descripcion"] ?? "";

// Valida el ID y los tipos de datos
if (!$id || $id <= 0 || !is_string($nombre) || !is_string($descripcion)) {
    header("Location: ../pages/categorias.php");
    exit;
}

// Elimina espacios innecesarios
$nombre = trim($nombre);
$descripcion = trim($descripcion);

// Valida el nombre y la descripcion
if ($nombre === "" || strlen($nombre) > 100 || strlen($descripcion) > 255) {
    header("Location: ../pages/editarCategoria.php?id=$id&error=1");
    exit;
}

try {

    // Actualiza la categoria en MySQL
    $sql = "UPDATE categorias
            SET nombre = :nombre, descripcion = :descripcion
            WHERE id = :id AND eliminado = 0";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "nombre" => $nombre,
        "descripcion" => $descripcion,
        "id" => $id
    ]);

    // Regresa a la lista de categorias
    header("Location: ../pages/categorias.php");
    exit;

} catch (PDOException $e) {

    // Registra el error en el servidor
    error_log($e->getMessage());

    header("Location: ../pages/editarCategoria.php?id=$id&error=1");
    exit;
}
