
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol("Administrador");

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

        setcookie("sigel_categorias", (string) $porPagina, [
            "expires" => time() + 60 * 60 * 24 * 30,
            "path" => "/",
            "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
            "httponly" => true,
            "samesite" => "Lax"
        ]);
    }

} elseif (isset($_COOKIE["sigel_categorias"])) {

    $valor = filter_var($_COOKIE["sigel_categorias"], FILTER_VALIDATE_INT);

    if ($valor !== false && in_array($valor, $opciones, true)) {
        $porPagina = $valor;
    }
}


$sql = "SELECT COUNT(*) FROM categorias
        WHERE eliminado = 0
        AND (nombre LIKE :nombre OR descripcion LIKE :descripcion)";

$stmt = $conexion->prepare($sql);

$stmt->execute([
    "nombre" => $texto,
    "descripcion" => $texto
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

$sql = "SELECT id, nombre, descripcion
        FROM categorias
        WHERE eliminado = 0
        AND (nombre LIKE :nombre OR descripcion LIKE :descripcion)
        ORDER BY id DESC
        LIMIT :limite OFFSET :inicio";

$stmt = $conexion->prepare($sql);

$stmt->bindValue(":nombre", $texto, PDO::PARAM_STR);
$stmt->bindValue(":descripcion", $texto, PDO::PARAM_STR);
$stmt->bindValue(":limite", $porPagina, PDO::PARAM_INT);
$stmt->bindValue(":inicio", $inicio, PDO::PARAM_INT);

$stmt->execute();

$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Categorias - SIGEL</title>
</head>
<body>

    <h2>Listado de categorias</h2>

    <a href="../panel.php">Volver al panel</a>

    <br><br>

    <a href="crearCategoria.php">Crear nueva categoria</a>

    <br><br>

    <form action="categorias.php" method="GET">

        <input type="text" name="buscar" maxlength="100"
               value="<?php echo htmlspecialchars($buscar, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>"
               placeholder="Buscar categoria">

        <button type="submit">Buscar</button>

        <a href="categorias.php">Mostrar todas</a>

        <label>Registros por pagina:</label>

        <select name="porPagina">
            <option value="5" <?php if ($porPagina == 5) echo "selected"; ?>>5</option>
            <option value="10" <?php if ($porPagina == 10) echo "selected"; ?>>10</option>
            <option value="20" <?php if ($porPagina == 20) echo "selected"; ?>>20</option>
        </select>


    </form>

    <br><br>

    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Descripcion</th>
            <th>Acciones</th>
        </tr>

        <?php foreach ($categorias as $categoria): ?>

        <tr>
            <td><?php echo (int) $categoria["id"]; ?></td>
            <td><?php echo htmlspecialchars($categoria["nombre"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></td>
            <td><?php echo htmlspecialchars($categoria["descripcion"] ?? "", ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></td>
            <td>
                <a href="editarCategoria.php?id=<?php echo (int) $categoria["id"]; ?>">Editar</a>

                <form action="../actions/eliminarCategoria.php" method="POST">

                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">

                    <input type="hidden" name="id" value="<?php echo (int) $categoria["id"]; ?>">

                    <button type="submit">Eliminar</button>

                </form>
            </td>
        </tr>

        <?php endforeach; ?>

    </table>

    <br>

    <?php if ($totalPaginas > 1): ?>

        <p>Pagina <?php echo $pagina; ?> de <?php echo $totalPaginas; ?></p>

        <?php if ($pagina > 1): ?>
            <a href="categorias.php?pagina=<?php echo $pagina - 1; ?>&amp;buscar=<?php echo urlencode($buscar); ?>">Anterior</a>
        <?php endif; ?>

        <?php if ($pagina < $totalPaginas): ?>
            <a href="categorias.php?pagina=<?php echo $pagina + 1; ?>&amp;buscar=<?php echo urlencode($buscar); ?>">Siguiente</a>
        <?php endif; ?>

    <?php endif; ?>

</body>
</html>
