
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
$nombre = $_POST["nombre"] ?? "";
$correo = $_POST["correo"] ?? "";
$password = $_POST["password"] ?? "";
$rol_id = filter_input(INPUT_POST, "rol_id", FILTER_VALIDATE_INT);

// Comprobar los tipos de datos
if (!is_string($nombre) || !is_string($correo) || !is_string($password)) {
    header("Location: ../pages/crearUsuario.php?error=datos");
    exit;
}

// Eliminar espacios innecesarios
$nombre = trim($nombre);
$correo = trim($correo);

// Validar los datos ingresados
if (
    $nombre === "" ||
    strlen($nombre) > 100 ||
    !filter_var($correo, FILTER_VALIDATE_EMAIL) ||
    strlen($correo) > 190 ||
    strlen($password) < 8 ||
    strlen($password) > 72 ||
    !$rol_id ||
    $rol_id <= 0
) {
    header("Location: ../pages/crearUsuario.php?error=datos");
    exit;
}

try {

    // Comprobar que el rol exista
    $sql = "SELECT COUNT(*) FROM roles WHERE id = :id";

    $stmt = $conexion->prepare($sql);
    $stmt->execute(["id" => $rol_id]);

    if ($stmt->fetchColumn() == 0) {
        header("Location: ../pages/crearUsuario.php?error=rol");
        exit;
    }

    // Proteger la contraseña con un hash
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);

    // Registrar el nuevo usuario en MySQL
    $sql = "INSERT INTO usuarios
            (nombre, correo, password_hash, rol_id, activo)
            VALUES
            (:nombre, :correo, :password_hash, :rol_id, 1)";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "nombre" => $nombre,
        "correo" => $correo,
        "password_hash" => $passwordHash,
        "rol_id" => $rol_id
    ]);

    // Regresar a la lista de usuarios
    header("Location: ../pages/usuarios.php");
    exit;

} catch (PDOException $e) {

    // Registrar el error en el servidor
    error_log($e->getMessage());

    header("Location: ../pages/crearUsuario.php?error=guardar");
    exit;
}
