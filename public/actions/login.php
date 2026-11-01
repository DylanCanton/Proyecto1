
<?php

require_once __DIR__ . "/../../app/config/conexion.php";
require_once __DIR__ . "/../../app/includes/sesion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";

if (!is_string($token) || !hash_equals($_SESSION["csrf_token"], $token)) {
    http_response_code(403);
    exit("Solicitud no valida.");
}

$correo = $_POST["correo"] ?? "";
$password = $_POST["password"] ?? "";

if (!is_string($correo) || !is_string($password)) {
    header("Location: ../index.php?error=1");
    exit;
}

$correo = trim($correo);

if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 150 || strlen($password) === 0 || strlen($password) > 1024) {
    header("Location: ../index.php?error=1");
    exit;
}

$sql = "SELECT id, nombre, rol_id, password_hash, activo
        FROM usuarios
        WHERE correo = :correo";

$stmt = $conexion->prepare($sql);
$stmt->execute(["correo" => $correo]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$usuario || $usuario["activo"] != 1 || !password_verify($password, $usuario["password_hash"])) {
    header("Location: ../index.php?error=1");
    exit;
}

session_regenerate_id(true);

$_SESSION["usuario_id"] = $usuario["id"];
$_SESSION["nombre"] = $usuario["nombre"];
$_SESSION["rol_id"] = $usuario["rol_id"];
$_SESSION["ultimo_acceso"] = time();

header("Location: ../panel.php");
exit;

