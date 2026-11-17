
<?php

// Cargar la autenticacion
require_once __DIR__ . "/../../app/includes/auth.php";

// Permitir acceso al administrador y operador
exigirRol(["Administrador", "Operador"]);

// Consultar el rol del usuario
$sqlRol = "SELECT roles.nombre
           FROM usuarios
           INNER JOIN roles ON usuarios.rol_id = roles.id
           WHERE usuarios.id = :id AND usuarios.activo = 1";

$stmtRol = $conexion->prepare($sqlRol);
$stmtRol->execute(["id" => $_SESSION["usuario_id"]]);

$esAdministrador = $stmtRol->fetchColumn() === "Administrador";

// Obtener el texto de busqueda
$buscar = $_GET["buscar"] ?? "";

if (!is_string($buscar)) {
    $buscar = "";
}

$buscar = trim($buscar);

if (strlen($buscar) > 100) {
    $buscar = "";
}

$texto = "%" . $buscar . "%";

// Configurar registros por pagina
$opciones = [5, 10, 20];

$porPagina = 10;

// Guardar la preferencia en una cookie
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

    // Recuperar la preferencia guardada
    $valor = filter_var($_COOKIE["sigel_equipos"], FILTER_VALIDATE_INT);

    if ($valor !== false && in_array($valor, $opciones, true)) {
        $porPagina = $valor;
    }
}

// Contar los equipos encontrados
$sql = "SELECT COUNT(*) FROM equipos
        WHERE eliminado_en IS NULL
        AND (codigo LIKE :codigo OR nombre LIKE :nombre)";

$stmt = $conexion->prepare($sql);

$stmt->execute([
    "codigo" => $texto,
    "nombre" => $texto
]);

$totalRegistros = (int) $stmt->fetchColumn();

// Calcular el total de paginas
$totalPaginas = max(1, (int) ceil($totalRegistros / $porPagina));

// Validar la pagina actual
$pagina = filter_input(INPUT_GET, "pagina", FILTER_VALIDATE_INT);

if (!$pagina || $pagina < 1) {
    $pagina = 1;
}

if ($pagina > $totalPaginas) {
    $pagina = $totalPaginas;
}

$inicio = ($pagina - 1) * $porPagina;

// Consultar los equipos en MySQL
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

// Preparar la lista de equipos recientes
$recientes = [];
$idsVistos = [];

// Obtener los equipos guardados en la cookie
$cookieRecientes = $_COOKIE["sigel_recientes"] ?? "";

if (is_string($cookieRecientes) && $cookieRecientes !== "") {

    $ids = explode(",", $cookieRecientes);

    // Consultar los equipos recientes
    $sqlRecientes = "SELECT id, codigo, nombre
                     FROM equipos
                     WHERE id = :id
                     AND eliminado_en IS NULL";

    $stmtRecientes = $conexion->prepare($sqlRecientes);

    foreach ($ids as $idReciente) {

        // Validar el ID del equipo
        if (!ctype_digit($idReciente)) {
            continue;
        }

        $idReciente = (int) $idReciente;

        // Evitar IDs repetidos o invalidos
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

        // Mostrar un maximo de 5 equipos
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
    <link rel="stylesheet" href="../estilos.css">
</head>

<body>

    <h2>Listado de equipos</h2>

    <a href="../panel.php">Volver al panel</a>

    <!-- Mostrar los ultimos equipos consultados -->
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

    <!-- Formulario de busqueda -->
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

    <!-- Tabla de equipos -->
    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th>
            <th>Codigo</th>
            <th>Nombre</th>
            <th>Categoria</th>
            <th>Estado</th>
            <th>Consultar</th>

            <!-- Acciones exclusivas del administrador -->
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

            <!-- Consultar los detalles del equipo -->
            <td>
                <a href="verEquipo.php?id=<?php echo (int) $equipo["id"]; ?>">
                    Ver
                </a>
            </td>

            <!-- Editar o eliminar equipos -->
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

    <!-- Navegacion entre paginas -->
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
