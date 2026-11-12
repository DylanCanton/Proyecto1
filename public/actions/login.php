
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

$sql = "SELECT id, nombre, rol_id, password_hash, activo,
               intentos_fallidos, bloqueado_hasta
        FROM usuarios
        WHERE correo = :correo";

$stmt = $conexion->prepare($sql);
$stmt->execute(["correo" => $correo]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);


if (!$usuario || (int) $usuario["activo"] !== 1) {
    header("Location: ../index.php?error=1");
    exit;
}

$ahora = time();

$bloqueadoHasta = $usuario["bloqueado_hasta"] !== null
    ? (int) $usuario["bloqueado_hasta"]
    : null;

if ($bloqueadoHasta !== null && $bloqueadoHasta > $ahora) {
    header("Location: ../index.php?error=1");
    exit;
}

if (!password_verify($password, $usuario["password_hash"])) {

    if ($bloqueadoHasta !== null && $bloqueadoHasta <= $ahora) {
        $intentos = 1;
    } else {
        $intentos = (int) $usuario["intentos_fallidos"] + 1;
    }

    $nuevoBloqueo = null;

    if ($intentos >= 5) {
        $nuevoBloqueo = $ahora + (15 * 60);
    }

    $sqlActualizar = "UPDATE usuarios
                      SET intentos_fallidos = :intentos,
                          bloqueado_hasta = :bloqueado_hasta
                      WHERE id = :id";

    $stmtActualizar = $conexion->prepare($sqlActualizar);

    $stmtActualizar->execute([
        "intentos" => $intentos,
        "bloqueado_hasta" => $nuevoBloqueo,
        "id" => $usuario["id"]
    ]);

    header("Location: ../index.php?error=1");
    exit;
}

$sqlReiniciar = "UPDATE usuarios
                 SET intentos_fallidos = 0,
                     bloqueado_hasta = NULL
                 WHERE id = :id";

$stmtReiniciar = $conexion->prepare($sqlReiniciar);

$stmtReiniciar->execute([
    "id" => $usuario["id"]
]);


session_regenerate_id(true);

$_SESSION["usuario_id"] = $usuario["id"];
$_SESSION["nombre"] = $usuario["nombre"];
$_SESSION["rol_id"] = $usuario["rol_id"];
$_SESSION["ultimo_acceso"] = time();


if (isset($_POST["recordarme"]) && $_POST["recordarme"] === "1") {

    $selector = bin2hex(random_bytes(9));
    $validador = bin2hex(random_bytes(32));

    $hashValidador = hash("sha256", $validador);

    $expiracion = time() + (14 * 24 * 60 * 60);

    $sqlToken = "INSERT INTO auth_tokens
                 (usuario_id, selector, validador_hash, expira_en)
                 VALUES
                 (:usuario_id, :selector, :validador_hash, :expira_en)";

    $stmtToken = $conexion->prepare($sqlToken);

    $stmtToken->execute([
        "usuario_id" => $usuario["id"],
        "selector" => $selector,
        "validador_hash" => $hashValidador,
        "expira_en" => date("Y-m-d H:i:s", $expiracion)
    ]);

    setcookie("sigel_recordarme", $selector . ":" . $validador, [
        "expires" => $expiracion,
        "path" => "/",
        "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "httponly" => true,
        "samesite" => "Lax"
    ]);
}


header("Location: ../panel.php");
exit;

