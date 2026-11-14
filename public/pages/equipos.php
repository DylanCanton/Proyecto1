
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol(["Administrador", "Operador"]);


$sqlRol = "SELECT roles.nombre
           FROM usuarios
           INNER JOIN roles ON usuarios.rol_id = roles.id
           WHERE usuarios.id = :id AND usuarios.activo = 1";

$stmtRol = $conexion->prepare($sqlRol);
$stmtRol->execute(["id" => $_SESSION["usuario_id"]]);

$esAdministrador = $stmtRol->fetchColumn() === "Administrador";



$buscar = $_GET["buscar"] ?? "";

if (!is_string($buscar)) {
    $buscar = "";
}

$buscar = trim($buscar);

if (strlen($buscar) > 100) {
    $buscar = "";
}


$texto = "%" . $buscar . "%";


$opciones = [5, 10, 20];

$porPagina = 10;

if (isset($_GET["porPagina"])) {

    $valor = filter_input(INPUT_GET, "porPagina", FILTER_VALIDATE_INT);

    if ($valor !== false && $valor !== null && in_array($valor, $opciones, true)) {

        $porPagina = $valor;

        setcookie("sigel_equipos", (string) $porPagina, [
            "expires" => time() + 60 * 60 * 24 * 30,
            "path" => "/",
            "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
            "httponly" => true,
            "samesite" => "Lax"
        ]);
    }

} elseif (isset($_COOKIE["sigel_equipos"]) && is_string($_COOKIE["sigel_equipos"])) {

    $valor = filter_var($_COOKIE["sigel_equipos"], FILTER_VALIDATE_INT);

    if ($valor !== false && in_array($valor, $opciones, true)) {
        $porPagina = $valor;
    }
}


$sql = "SELECT COUNT(*) FROM equipos
        WHERE eliminado_en IS NULL
        AND (codigo LIKE :codigo OR nombre LIKE :nombre)";

$stmt = $conexion->prepare($sql);

$stmt->execute([
    "codigo" => $texto,
    "nombre" => $texto
]);

$totalRegistros = (int) $stmt->fetchColumn();

$totalPaginas = max(1, (int) ceil($totalRegistros / $porPagina));

$pagina = filter_input(INPUT_GET, "pagina", FILTER_VALIDATE_INT);

if (!$pagina || $pagina < 1) {
    $pagina = 1;
}

if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
}

$inicio = ($pagina - 1) * $porPagina;

$sql = "SELECT equipos.id, equipos.codigo, equipos.nombre,
        equipos.estado, categorias.nombre AS categoria
        FROM equipos
        INNER JOIN categorias ON equipos.categoria_id = categorias.id
        WHERE equipos.eliminado_en IS NULL
        AND (equipos.codigo LIKE :codigo OR equipos.nombre LIKE :nombre)
        ORDER BY equipos.id DESC
        LIMIT :limite OFFSET :inicio";

$stmt = $conexion->prepare($sql);

$stmt->bindValue(":codigo", $texto, PDO::PARAM_STR);
$stmt->bindValue(":nombre", $texto, PDO::PARAM_STR);
$stmt->bindValue(":limite", $porPagina, PDO::PARAM_INT);
$stmt->bindValue(":inicio", $inicio, PDO::PARAM_INT);

$stmt->execute();

$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);


$recientes = [];
$idsVistos = [];

$cookieRecientes = $_COOKIE["sigel_recientes"] ?? "";

if (is_string($cookieRecientes) && $cookieRecientes !== "") {

    $ids = explode(",", $cookieRecientes);

    $sqlRecientes = "SELECT id, codigo, nombre
                     FROM equipos
                     WHERE id = :id
                     AND eliminado_en IS NULL";

    $stmtRecientes = $conexion->prepare($sqlRecientes);

    foreach ($ids as $idReciente) {

        if (!ctype_digit($idReciente)) {
            continue;
        }

        $idReciente = (int) $idReciente;

        if ($idReciente <= 0 || in_array($idReciente, $idsVistos, true)) {
            continue;
        }

        $idsVistos[] = $idReciente;

        $stmtRecientes->execute([
            "id" => $idReciente
        ]);

        $equipoReciente = $stmtRecientes->fetch(PDO::FETCH_ASSOC);

        if ($equipoReciente) {
            $recientes[] = $equipoReciente;
        }

        if (count($recientes) >= 5) {
            break;
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Equipos - SIGEL</title>
</head>
<body>

    <h2>Listado de equipos</h2>

    <a href="../panel.php">Volver al panel</a>


<h3>Ultimos equipos consultados</h3>

<?php if (empty($recientes)): ?>

    <p>Todavia no has consultado equipos.</p>

<?php else: ?>

    <ul>
        <?php foreach ($recientes as $reciente): ?>

            <li>
                <a href="verEquipo.php?id=<?php echo (int) $reciente["id"]; ?>">
                    <?php echo htmlspecialchars($reciente["codigo"] . " - " . $reciente["nombre"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
                </a>
            </li>

        <?php endforeach; ?>
    </ul>

<?php endif; ?>


    
<br><br>

<form action="equipos.php" method="GET">

    <input type="text" name="buscar" maxlength="100"
        value="<?php echo htmlspecialchars($buscar, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>"
        placeholder="Buscar equipo">

    
<label>Registros por pagina:</label>

<select name="porPagina">
    <option value="5" <?php if ($porPagina == 5) echo "selected"; ?>>5</option>
    <option value="10" <?php if ($porPagina == 10) echo "selected"; ?>>10</option>
    <option value="20" <?php if ($porPagina == 20) echo "selected"; ?>>20</option>
</select>
 
    <button type="submit">Buscar</button>

    <a href="equipos.php">Mostrar todos</a>

</form>


    <br><br>

    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th>
            <th>Codigo</th>
            <th>Nombre</th>
            <th>Categoria</th>
            <th>Estado</th>
            <th>Consultar</th>

            <?php if ($esAdministrador): ?>
                <th>Acciones</th>
            <?php endif; ?>


        </tr>

        <?php foreach ($equipos as $equipo): ?>
    
        <tr>
            <td><?php echo (int) $equipo["id"]; ?></td>
            <td><?php echo htmlspecialchars($equipo["codigo"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></td>
            <td><?php echo htmlspecialchars($equipo["nombre"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></td>
            <td><?php echo htmlspecialchars($equipo["categoria"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></td>
            <td><?php echo htmlspecialchars($equipo["estado"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></td>

            <td>
                <a href="verEquipo.php?id=<?php echo (int) $equipo["id"]; ?>">
                    Ver
                </a>
            </td>

        
        <?php if ($esAdministrador): ?>
            <td>
                <a href="editarEquipo.php?id=<?php echo (int) $equipo["id"]; ?>">Editar</a>

                <form action="../actions/eliminarEquipo.php" method="POST">

                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">

                    <input type="hidden" name="id" value="<?php echo (int) $equipo["id"]; ?>">

                    <button type="submit">Eliminar</button>

                </form>
            </td>
        <?php endif; ?>

        </tr>

        <?php endforeach; ?>

    </table>

    
<br>

<?php if ($totalPaginas > 1): ?>

    <p>Pagina <?php echo $pagina; ?> de <?php echo $totalPaginas; ?></p>

    <?php if ($pagina > 1): ?>
        <a href="equipos.php?pagina=<?php echo $pagina - 1; ?>&amp;buscar=<?php echo urlencode($buscar); ?>">Anterior</a>
    <?php endif; ?>

    <?php if ($pagina < $totalPaginas): ?>
        <a href="equipos.php?pagina=<?php echo $pagina + 1; ?>&amp;buscar=<?php echo urlencode($buscar); ?>">Siguiente</a>
    <?php endif; ?>

<?php endif; ?>


</body>
</html>
