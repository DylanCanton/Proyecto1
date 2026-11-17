
<?php

// Cargar la autenticacion
require_once __DIR__ . "/../../app/includes/auth.php";

// Permitir solo al administrador
exigirRol("Administrador");

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

        setcookie("sigel_usuarios", (string) $porPagina, [
            "expires" => time() + 60 * 60 * 24 * 30,
            "path" => "/",
            "secure" => !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off",
            "httponly" => true,
            "samesite" => "Lax"
        ]);
    }

} elseif (isset($_COOKIE["sigel_usuarios"]) && is_string($_COOKIE["sigel_usuarios"])) {

    // Recuperar la preferencia guardada
    $valor = filter_var($_COOKIE["sigel_usuarios"], FILTER_VALIDATE_INT);

    if ($valor !== false && in_array($valor, $opciones, true)) {
        $porPagina = $valor;
    }
}

// Contar los usuarios encontrados
$sql = "SELECT COUNT(*)
        FROM usuarios
        WHERE nombre LIKE :nombre
        OR correo LIKE :correo";

$stmt = $conexion->prepare($sql);

$stmt->execute([
    "nombre" => $texto,
    "correo" => $texto
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

// Consultar los usuarios en MySQL
$sql = "SELECT usuarios.id, usuarios.nombre,
               usuarios.correo, usuarios.activo,
               roles.nombre AS rol
        FROM usuarios
        INNER JOIN roles ON usuarios.rol_id = roles.id
        WHERE usuarios.nombre LIKE :nombre
        OR usuarios.correo LIKE :correo
        ORDER BY usuarios.id DESC
        LIMIT :limite OFFSET :inicio";

$stmt = $conexion->prepare($sql);

$stmt->bindValue(":nombre", $texto, PDO::PARAM_STR);
$stmt->bindValue(":correo", $texto, PDO::PARAM_STR);
$stmt->bindValue(":limite", $porPagina, PDO::PARAM_INT);
$stmt->bindValue(":inicio", $inicio, PDO::PARAM_INT);

$stmt->execute();

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar usuarios - SIGEL</title>
    <link rel="stylesheet" href="../estilos.css">
</head>
<body>

    <h2>Administracion de usuarios</h2>

    <a href="../panel.php">Volver al panel</a>

    <br><br>

    <a href="crearUsuario.php">Registrar usuario</a>

    <br><br>

    <!-- Formulario de busqueda -->
    <form action="usuarios.php" method="GET">

        <input type="text" name="buscar" maxlength="100"
            value="<?php echo htmlspecialchars($buscar, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>"
            placeholder="Buscar por nombre o correo">

        <label>Registros por pagina:</label>

        <select name="porPagina">
            <option value="5" <?php if ($porPagina == 5) echo "selected"; ?>>5</option>
            <option value="10" <?php if ($porPagina == 10) echo "selected"; ?>>10</option>
            <option value="20" <?php if ($porPagina == 20) echo "selected"; ?>>20</option>
        </select>

        <button type="submit">Buscar</button>

        <a href="usuarios.php">Mostrar todos</a>

    </form>

    <br><br>

    <!-- Tabla de usuarios -->
    <table border="1" cellpadding="10">

        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Correo</th>
            <th>Rol</th>
            <th>Estado</th>
            <th>Acciones</th>
        </tr>

        <?php foreach ($usuarios as $usuario): ?>

            <tr>
                <td><?php echo (int) $usuario["id"]; ?></td>

                <td><?php echo htmlspecialchars($usuario["nombre"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></td>

                <td><?php echo htmlspecialchars($usuario["correo"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></td>

                <td><?php echo htmlspecialchars($usuario["rol"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></td>

                <td>
                    <?php echo $usuario["activo"] == 1 ? "Activo" : "Inactivo"; ?>
                </td>

                <td>
                    <!-- Editar usuario -->
                    <a href="editarUsuario.php?id=<?php echo (int) $usuario["id"]; ?>">Editar</a>

                    <!-- Desactivar usuario -->
                    <?php if ($usuario["activo"] == 1 && (int) $usuario["id"] !== (int) $_SESSION["usuario_id"]): ?>

                        <form action="../actions/desactivarUsuario.php" method="POST">

                            <input type="hidden" name="csrf_token"
                                value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">

                            <input type="hidden" name="id"
                                value="<?php echo (int) $usuario["id"]; ?>">

                            <button type="submit">Desactivar</button>

                        </form>

                    <?php endif; ?>
                </td>

            </tr>

        <?php endforeach; ?>

    </table>

    <br>

    <!-- Navegacion entre paginas -->
    <?php if ($totalPaginas > 1): ?>

        <p>Pagina <?php echo $pagina; ?> de <?php echo $totalPaginas; ?></p>

        <?php if ($pagina > 1): ?>
            <a href="usuarios.php?pagina=<?php echo $pagina - 1; ?>&amp;buscar=<?php echo urlencode($buscar); ?>&amp;porPagina=<?php echo $porPagina; ?>">
                Anterior
            </a>
        <?php endif; ?>

        <?php if ($pagina < $totalPaginas): ?>
            <a href="usuarios.php?pagina=<?php echo $pagina + 1; ?>&amp;buscar=<?php echo urlencode($buscar); ?>&amp;porPagina=<?php echo $porPagina; ?>">
                Siguiente
            </a>
        <?php endif; ?>

    <?php endif; ?>

</body>
</html>
