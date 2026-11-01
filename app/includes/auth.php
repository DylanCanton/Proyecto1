
<?php

require_once __DIR__ . "/sesion.php";
require_once __DIR__ . "/../config/conexion.php";

function exigirRol($rol) {

    global $conexion;

    if (!isset($_SESSION["usuario_id"])) {
        header("Location: /index.php");
        exit;
    }

    $sql = "SELECT roles.nombre
            FROM usuarios
            INNER JOIN roles ON usuarios.rol_id = roles.id
            WHERE usuarios.id = :id AND usuarios.activo = 1";

    $stmt = $conexion->prepare($sql);
    $stmt->execute(["id" => $_SESSION["usuario_id"]]);

    $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$usuario || $usuario["nombre"] !== $rol) {
        http_response_code(403);
        exit("Acceso denegado.");
    }
}
