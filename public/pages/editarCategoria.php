
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol("Administrador");

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: categorias.php");
    exit;
}

$sql = "SELECT id, nombre, descripcion
        FROM categorias
        WHERE id = :id AND eliminado = 0";

$stmt = $conexion->prepare($sql);
$stmt->execute(["id" => $id]);

$categoria = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$categoria) {
    header("Location: categorias.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Categoria - SIGEL</title>
</head>
<body>

    <h2>Editar categoria</h2>

    <form action="../actions/actualizarCategoria.php" method="POST">

        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">

        <input type="hidden" name="id" value="<?php echo (int) $categoria["id"]; ?>">

        <label>Nombre:</label>
        <input type="text" name="nombre" maxlength="100" value="<?php echo htmlspecialchars($categoria["nombre"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" required>

        <br><br>

        <label>Descripcion:</label>
        <textarea name="descripcion" maxlength="255"><?php echo htmlspecialchars($categoria["descripcion"] ?? "", ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></textarea>

        <br><br>

        <button type="submit">Guardar cambios</button>

    </form>

    <br>

    <a href="categorias.php">Volver a categorias</a>

</body>
</html>

