
<?php

require_once __DIR__ . "/../../app/includes/sesion.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";

if (!is_string($token) || !hash_equals($_SESSION["csrf_token"] ?? "", $token)) {
    http_response_code(403);
    exit("Solicitud no valida.");
}

$_SESSION = [];

$parametros = session_get_cookie_params();

setcookie(session_name(), "", [
    "expires" => time() - 3600,
    "path" => $parametros["path"],
    "domain" => $parametros["domain"],
    "secure" => $parametros["secure"],
    "httponly" => $parametros["httponly"],
    "samesite" => $parametros["samesite"]
]);

session_destroy();

header("Location: ../index.php");
exit;
