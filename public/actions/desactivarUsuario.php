
<?php

// Carga la autenticacion
require_once __DIR__ . "/../../app/includes/auth.php";

// Permitir solo al administrador
exigirRol("Administrador");

// Verifica el metodo POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/usuarios.php");
    exit;
}

// Valida el token CSRF
$token = $_POST["csrf_token"] ?? "";

if (!is_string($token) || !hash_equals($_SESSION["csrf_token"] ?? "", $token)) {
    http_response_code(403);
    exit("Solicitud no valida.");
}

// Obtener y validar el ID del usuario
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: ../pages/usuarios.php?error=datos");
    exit;
}

// Evita que el administrador se desactive a si mismo
if ($id === (int) $_SESSION["usuario_id"]) {
    header("Location: ../pages/usuarios.php?error=usuario_propio");
    exit;
}

try {

    // Desactiva el usuario en MySQL
    $sql = "UPDATE usuarios
            SET activo = 0
            WHERE id = :id AND activo = 1";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "id" => $id
    ]);

    // Regresa a la lista de usuarios
    header("Location: ../pages/usuarios.php?desactivado=1");
    exit;

} catch (PDOException $e) {

    // Registra el error en el servidor
    error_log($e->getMessage());

    header("Location: ../pages/usuarios.php?error=guardar");
    exit;
}
