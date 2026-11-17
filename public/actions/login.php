
<?php

// Carga la conexion y la sesion
require_once __DIR__ . "/../../app/config/conexion.php";
require_once __DIR__ . "/../../app/includes/sesion.php";

// Verifica el metodo POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../index.php");
    exit;
}

// Valida el token CSRF
$token = $_POST["csrf_token"] ?? "";

if (!is_string($token) || !hash_equals($_SESSION["csrf_token"], $token)) {
    http_response_code(403);
    exit("Solicitud no valida.");
}

// Recibir el correo y la contraseña
$correo = $_POST["correo"] ?? "";
$password = $_POST["password"] ?? "";

if (!is_string($correo) || !is_string($password)) {
    header("Location: ../index.php?error=1");
    exit;
}

$correo = trim($correo);

// Valida los datos ingresados
if (!filter_var($correo, FILTER_VALIDATE_EMAIL) || strlen($correo) > 150 || strlen($password) === 0 || strlen($password) > 1024) {
    header("Location: ../index.php?error=1");
    exit;
}

// Busca el usuario en MySQL
$sql = "SELECT id, nombre, rol_id, password_hash, activo,
               intentos_fallidos, bloqueado_hasta
        FROM usuarios
        WHERE correo = :correo";

$stmt = $conexion->prepare($sql);
$stmt->execute(["correo" => $correo]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Comprobar que el usuario este activo
if (!$usuario || (int) $usuario["activo"] !== 1) {
    header("Location: ../index.php?error=1");
    exit;
}

$ahora = time();

$bloqueadoHasta = $usuario["bloqueado_hasta"] !== null
    ? (int) $usuario["bloqueado_hasta"]
    : null;

// Verifica si la cuenta esta bloqueada
if ($bloqueadoHasta !== null && $bloqueadoHasta > $ahora) {
    header("Location: ../index.php?error=1");
    exit;
}

// Comprobar la contraseña
if (!password_verify($password, $usuario["password_hash"])) {

    // Contar los intentos fallidos
    if ($bloqueadoHasta !== null && $bloqueadoHasta <= $ahora) {
        $intentos = 1;
    } else {
        $intentos = (int) $usuario["intentos_fallidos"] + 1;
    }

    $nuevoBloqueo = null;

    // Bloquea la cuenta despues de 5 intentos
    if ($intentos >= 5) {
        $nuevoBloqueo = $ahora + (15 * 60);
    }

    // Actualiza los intentos en MySQL
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

// Reinicia los intentos al ingresar correctamente
$sqlReiniciar = "UPDATE usuarios
                 SET intentos_fallidos = 0,
                     bloqueado_hasta = NULL
                 WHERE id = :id";

$stmtReiniciar = $conexion->prepare($sqlReiniciar);

$stmtReiniciar->execute([
    "id" => $usuario["id"]
]);

// Crea la sesion del usuario
session_regenerate_id(true);

$_SESSION["usuario_id"] = $usuario["id"];
$_SESSION["nombre"] = $usuario["nombre"];
$_SESSION["rol_id"] = $usuario["rol_id"];
$_SESSION["ultimo_acceso"] = time();

// Verifica si marco Recordarme
if (isset($_POST["recordarme"]) && $_POST["recordarme"] === "1") {

    // Generar el selector y validador
    $selector = bin2hex(random_bytes(9));
    $validador = bin2hex(random_bytes(32));

    $hashValidador = hash("sha256", $validador);

    $expiracion = time() + (14 * 24 * 60 * 60);

    // Guarda el token en MySQL
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

    // Crea la cookie de Recordarme
    setcookie("sigel_recordarme", $selector . ":" . $validador, [
        "expires" => $expiracion,
        "path" => "/",
        "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
        "httponly" => true,
        "samesite" => "Lax"
    ]);
}

// Envia al panel principal
header("Location: ../panel.php");
exit;
