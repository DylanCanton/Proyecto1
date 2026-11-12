
<?php

$esHttps = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";

session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/",
    "secure" => $esHttps,
    "httponly" => true,
    "samesite" => "Lax"
]);

session_start();


if (isset($_SESSION["usuario_id"])) {

    $tiempoLimite = 15 * 60;

    $ultimoAcceso = $_SESSION["ultimo_acceso"] ?? time();

    if (
        !is_int($ultimoAcceso) ||
        time() - $ultimoAcceso > $tiempoLimite
    ) {

        $_SESSION = [];

        if (ini_get("session.use_cookies")) {

            $parametros = session_get_cookie_params();

            setcookie(session_name(), "", [
                "expires" => time() - 3600,
                "path" => $parametros["path"],
                "domain" => $parametros["domain"],
                "secure" => $parametros["secure"],
                "httponly" => $parametros["httponly"],
                "samesite" => $parametros["samesite"] ?? "Lax"
            ]);
        }

        session_destroy();

        header("Location: /index.php");
        exit;
    }

    $_SESSION["ultimo_acceso"] = time();
}


if (!isset($_SESSION["usuario_id"])) {
    require_once __DIR__ . "/recordarme.php";
}


if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
