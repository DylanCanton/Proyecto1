
<?php

// Cargar la sesion y la conexion
require_once __DIR__ . "/../../app/includes/sesion.php";
require_once __DIR__ . "/../../app/config/conexion.php";

// Verificar el metodo POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

// Validar el token CSRF
$token = $_POST["csrf_token"] ?? "";

if (!is_string($token) || !hash_equals($_SESSION["csrf_token"] ?? "", $token)) {
    http_response_code(403);
    exit("Solicitud no valida.");
}

// Comprobar si existe la cookie Recordarme
if (
    isset($_SESSION["usuario_id"]) &&
    isset($_COOKIE["sigel_recordarme"]) &&
    is_string($_COOKIE["sigel_recordarme"])
) {

    $cookie = $_COOKIE["sigel_recordarme"];

    // Validar el formato de la cookie
    if (preg_match('/^[a-f0-9]{18}:[a-f0-9]{64}$/', $cookie)) {

        // Separar el selector y el validador
        $partes = explode(":", $cookie);

        $selector = $partes[0];
        $validador = $partes[1];

        // Eliminar el token de MySQL
        $sql = "DELETE FROM auth_tokens
                WHERE usuario_id = :usuario_id
                  AND selector = :selector
                  AND validador_hash = :validador_hash";

        $stmt = $conexion->prepare($sql);

        $stmt->execute([
            "usuario_id" => $_SESSION["usuario_id"],
            "selector" => $selector,
            "validador_hash" => hash("sha256", $validador)
        ]);
    }
}

// Comprobar si se utiliza HTTPS
$esHttps = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";

// Eliminar la cookie Recordarme
setcookie("sigel_recordarme", "", [
    "expires" => time() - 3600,
    "path" => "/",
    "secure" => $esHttps,
    "httponly" => true,
    "samesite" => "Lax"
]);

// Limpiar los datos de la sesion
$_SESSION = [];

$parametros = session_get_cookie_params();

// Eliminar la cookie de sesion
setcookie(session_name(), "", [
    "expires" => time() - 3600,
    "path" => $parametros["path"],
    "domain" => $parametros["domain"],
    "secure" => $parametros["secure"],
    "httponly" => $parametros["httponly"],
    "samesite" => $parametros["samesite"] ?? "Lax"
]);

// Destruir la sesion
session_destroy();

// Regresar al inicio de sesion
header("Location: ../index.php");
exit;
