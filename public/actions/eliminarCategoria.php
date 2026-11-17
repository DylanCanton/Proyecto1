
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

// Obtener y validar el ID de la categoria
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: ../pages/categorias.php");
    exit;
}

try {

    // Marca la categoria como eliminada
    $sql = "UPDATE categorias
            SET eliminado = 1
            WHERE id = :id AND eliminado = 0";

    $stmt = $conexion->prepare($sql);
    $stmt->execute(["id" => $id]);

    // Regresa a la lista de categorias
    header("Location: ../pages/categorias.php");
    exit;

} catch (PDOException $e) {

    // Registra el error en el servidor
    error_log($e->getMessage());

    header("Location: ../pages/categorias.php?error=1");
    exit;
}
