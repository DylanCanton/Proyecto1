
<?php

// Cargar la autenticacion
require_once __DIR__ . "/../../app/includes/auth.php";

// Permitir solo al administrador
exigirRol("Administrador");

// Verificar el metodo POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/usuarios.php");
    exit;
}

// Validar el token CSRF
$token = $_POST["csrf_token"] ?? "";

if (!is_string($token) || !hash_equals($_SESSION["csrf_token"] ?? "", $token)) {
    http_response_code(403);
    exit("Solicitud no valida.");
}

// Recibir los datos del formulario
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
$nombre = $_POST["nombre"] ?? "";
$correo = $_POST["correo"] ?? "";
$rol_id = filter_input(INPUT_POST, "rol_id", FILTER_VALIDATE_INT);

// Comprobar los tipos de datos
if (!is_string($nombre) || !is_string($correo)) {
    header("Location: ../pages/usuarios.php?error=datos");
    exit;
}

// Eliminar espacios innecesarios
$nombre = trim($nombre);
$correo = trim($correo);

// Validar los datos ingresados
if (
    !$id ||
    $id <= 0 ||
    $nombre === "" ||
    strlen($nombre) > 100 ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    strlen($correo) > 190 ||
    !$rol_id ||
    $rol_id <= 0
) {
    header("Location: ../pages/usuarios.php?error=datos");
    exit;
}

try {

    // Comprobar que el rol exista
    $sql = "SELECT nombre FROM roles WHERE id = :id";

    $stmt = $conexion->prepare($sql);
    $stmt->execute(["id" => $rol_id]);

    $nombreRol = $stmt->fetchColumn();

    if ($nombreRol === false) {
        header("Location: ../pages/usuarios.php?error=rol");
        exit;
    }

    // Evitar que el administrador cambie su propio rol
    if (
        $id === (int) $_SESSION["usuario_id"] &&
        $nombreRol !== "Administrador"
    ) {
        header("Location: ../pages/usuarios.php?error=rol_propio");
        exit;
    }

    // Verificar que el correo no este registrado por otro usuario
    $sql = "SELECT COUNT(*) FROM usuarios
            WHERE correo = :correo AND id != :id";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "correo" => $correo,
        "id" => $id
    ]);

    if ($stmt->fetchColumn() > 0) {
        header("Location: ../pages/usuarios.php?error=correo");
        exit;
    }

    // Actualizar los datos del usuario
    $sql = "UPDATE usuarios
            SET nombre = :nombre,
                correo = :correo,
                rol_id = :rol_id
            WHERE id = :id";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "nombre" => $nombre,
        "correo" => $correo,
        "rol_id" => $rol_id,
        "id" => $id
    ]);

    // Regresar a la lista de usuarios
    header("Location: ../pages/usuarios.php?actualizado=1");
    exit;

} catch (PDOException $e) {

    // Registrar el error en el servidor
    error_log($e->getMessage());
    header("Location: ../pages/usuarios.php?error=guardar");
    exit;
}
