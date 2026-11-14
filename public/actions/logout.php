
<?php

require_once __DIR__ . "/../../app/includes/sesion.php";
require_once __DIR__ . "/../../app/config/conexion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";

if (!is_string($token) || !hash_equals($_SESSION["csrf_token"] ?? "", $token)) {
    http_response_code(403);
    exit("Solicitud no valida.");
}

if (
    isset($_SESSION["usuario_id"]) &&
    isset($_COOKIE["sigel_recordarme"]) &&
    is_string($_COOKIE["sigel_recordarme"])
) {

    $cookie = $_COOKIE["sigel_recordarme"];

    if (preg_match('/^[a-f0-9]{18}:[a-f0-9]{64}$/', $cookie)) {

        $partes = explode(":", $cookie);

        $selector = $partes[0];
        $validador = $partes[1];

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

$esHttps = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";

setcookie("sigel_recordarme", "", [
    "expires" => time() - 3600,
    "path" => "/",
    "secure" => $esHttps,
    "httponly" => true,
    "samesite" => "Lax"
]);

$_SESSION = [];

$parametros = session_get_cookie_params();

setcookie(session_name(), "", [
    "expires" => time() - 3600,
    "path" => $parametros["path"],
    "domain" => $parametros["domain"],
    "secure" => $parametros["secure"],
    "httponly" => $parametros["httponly"],
    "samesite" => $parametros["samesite"] ?? "Lax"
]);

session_destroy();

header("Location: ../index.php");
exit;
