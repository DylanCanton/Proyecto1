
<?php

// Comprobar si se utiliza HTTPS
$esHttps = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";

// Configura la cookie de sesion
session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/",
    "secure" => $esHttps,
    "httponly" => true,
    "samesite" => "Lax"
]);

// Iniciar la sesion
session_start();

// Verifica la actividad del usuario
if (isset($_SESSION["usuario_id"])) {

    $tiempoLimite = 15 * 60;

    $ultimoAcceso = $_SESSION["ultimo_acceso"] ?? time();

    // Comprueba si la sesion vencio
    if (
        !is_int($ultimoAcceso) ||
        time() - $ultimoAcceso > $tiempoLimite
    ) {

        $_SESSION = [];

        // Elimina la cookie de sesion
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

        // Destruye la sesion y regresar al login
        session_destroy();

        header("Location: /index.php");
        exit;
    }

    // Actualiza la ultima actividad
    $_SESSION["ultimo_acceso"] = time();
}

// Recupera la sesion con Recordarme

if (!isset($_SESSION["usuario_id"])) {
    require_once __DIR__ . "/recordarme.php";
}

// Crear el token de seguridad

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
