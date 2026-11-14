
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol(["Administrador", "Operador"]);

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    http_response_code(404);
    exit("Equipo no encontrado.");
}

$sql = "SELECT equipos.id, equipos.codigo, equipos.nombre,
               equipos.estado, categorias.nombre AS categoria
        FROM equipos
        INNER JOIN categorias ON equipos.categoria_id = categorias.id
        WHERE equipos.id = :id
        AND equipos.eliminado_en IS NULL";

$stmt = $conexion->prepare($sql);
$stmt->execute(["id" => $id]);

$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    http_response_code(404);
    exit("Equipo no encontrado.");
}

$recientes = [$id];

$guardados = $_COOKIE["sigel_recientes"] ?? "";

if (is_string($guardados)) {

    $lista = explode(",", $guardados);

    foreach ($lista as $valor) {

        if (ctype_digit($valor)) {

            $numero = (int) $valor;

            if (
                $numero > 0 &&
                $numero !== $id &&
                !in_array($numero, $recientes, true)
            ) {
                $recientes[] = $numero;
            }
        }

        if (count($recientes) >= 5) {
            break;
        }
    }
}

setcookie("sigel_recientes", implode(",", $recientes), [
    "expires" => time() + (30 * 24 * 60 * 60),
    "path" => "/",
    "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
    "httponly" => true,
    "samesite" => "Lax"
]);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Consultar equipo - SIGEL</title>
</head>
<body>

    <h2>Informacion del equipo</h2>

    <p><strong>Codigo:</strong>
        <?php echo htmlspecialchars($equipo["codigo"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
    </p>

    <p><strong>Nombre:</strong>
        <?php echo htmlspecialchars($equipo["nombre"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
    </p>

    <p><strong>Categoria:</strong>
        <?php echo htmlspecialchars($equipo["categoria"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
    </p>

    <p><strong>Estado:</strong>
        <?php echo htmlspecialchars($equipo["estado"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
    </p>

    <a href="equipos.php">Volver a equipos</a>

</body>
</html>
