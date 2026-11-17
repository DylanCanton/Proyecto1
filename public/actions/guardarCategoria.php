
<?php

// Carga la autenticacion
require_once __DIR__ . "/../../app/includes/auth.php";

// Permitir solo al administrador
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

// Recibir los datos del formulario
$nombre = $_POST["nombre"] ?? "";
$descripcion = $_POST["descripcion"] ?? "";

// Comprobar los tipos de datos
if (!is_string($nombre) || !is_string($descripcion)) {
    header("Location: ../pages/crearCategoria.php?error=1");
    exit;
}

// Elimina espacios innecesarios
$nombre = trim($nombre);
$descripcion = trim($descripcion);

// Valida el nombre y la descripcion
if ($nombre === "" || strlen($nombre) > 100 || strlen($descripcion) > 255) {
    header("Location: ../pages/crearCategoria.php?error=1");
    exit;
}

try {

    // Guarda la categoria en MySQL
    $sql = "INSERT INTO categorias (nombre, descripcion)
            VALUES (:nombre, :descripcion)";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "nombre" => $nombre,
        "descripcion" => $descripcion
    ]);

    // Regresa a la lista de categorias
    header("Location: ../pages/categorias.php");
    exit;

} catch (PDOException $e) {

    // Registra el error en el servidor
    error_log($e->getMessage());

    header("Location: ../pages/crearCategoria.php?error=2");
    exit;
}
