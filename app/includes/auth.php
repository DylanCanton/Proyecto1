
<?php

// Carga la sesion y la conexion
require_once __DIR__ . "/sesion.php";
require_once __DIR__ . "/../config/conexion.php";

// Verifica los permisos del usuario
function exigirRol($rol) {

    global $conexion;

    // Comprueba que inicio sesion
    if (!isset($_SESSION["usuario_id"])) {
        header("Location: /index.php");
        exit;
    }

    // Consulta el rol del usuario
    $sql = "SELECT roles.nombre
            FROM usuarios
            INNER JOIN roles ON usuarios.rol_id = roles.id
            WHERE usuarios.id = :id AND usuarios.activo = 1";

    $stmt = $conexion->prepare($sql);
    $stmt->execute(["id" => $_SESSION["usuario_id"]]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    // Denegar el acceso si no tiene permiso
    if (!$usuario || !in_array($usuario["nombre"], (array) $rol, true)) {
        http_response_code(403);
        exit("Acceso denegado.");
    }

}
