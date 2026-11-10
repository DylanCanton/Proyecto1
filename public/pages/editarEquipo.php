
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol("Administrador");

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: equipos.php");
    exit;
}

$sql = "SELECT id, categoria_id, codigo, nombre, estado
        FROM equipos
        WHERE id = :id AND eliminado_en IS NULL";

$stmt = $conexion->prepare($sql);
$stmt->execute(["id" => $id]);

$equipo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$equipo) {
    header("Location: equipos.php");
    exit;
}

$sql = "SELECT id, nombre
        FROM categorias
        WHERE eliminado = 0
        ORDER BY nombre ASC";

$stmt = $conexion->prepare($sql);
$stmt->execute();

$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);


$sqlImagenes = "SELECT id
                FROM equipo_imagenes
                WHERE equipo_id = :equipo_id
                ORDER BY id DESC";

$stmtImagenes = $conexion->prepare($sqlImagenes);

$stmtImagenes->execute([
    "equipo_id" => $equipo["id"]
]);

$imagenes = $stmtImagenes->fetchAll(PDO::FETCH_ASSOC);


?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar Equipo - SIGEL</title>
</head>
<body>

    <h2>Editar equipo</h2>

    <?php if (isset($_GET["error"])): ?>
        <p>No se pudo actualizar el equipo. Verifique los datos.</p>
    <?php endif; ?>


    <form action="../actions/actualizarEquipo.php" method="POST">

        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">

        <input type="hidden" name="id" value="<?php echo (int) $equipo["id"]; ?>">

        <label>Codigo:</label>
        <input type="text" name="codigo" maxlength="30" value="<?php echo htmlspecialchars($equipo["codigo"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" required>

        <br><br>

        <label>Nombre:</label>
        <input type="text" name="nombre" maxlength="100" value="<?php echo htmlspecialchars($equipo["nombre"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>" required>

        <br><br>

        <label>Categoria:</label>
        <select name="categoria_id" required>

            <?php foreach ($categorias as $categoria): ?>

                <option value="<?php echo (int) $categoria["id"]; ?>"
                    <?php if ($categoria["id"] == $equipo["categoria_id"]) echo "selected"; ?>>

                    <?php echo htmlspecialchars($categoria["nombre"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>

                </option>

            <?php endforeach; ?>

        </select>

        <br><br>

        <label>Estado:</label>
        <select name="estado" required>
            <option value="disponible" <?php if ($equipo["estado"] === "disponible") echo "selected"; ?>>Disponible</option>
            <option value="mantenimiento" <?php if ($equipo["estado"] === "mantenimiento") echo "selected"; ?>>Mantenimiento</option>
        </select>

        <br><br>

        <button type="submit">Guardar cambios</button>

    </form>

    <br>

    <a href="equipos.php">Volver a equipos</a>

</body>


<h3>Imagenes del equipo</h3>

<form action="../actions/guardarImagenEquipo.php"
      method="POST"
      enctype="multipart/form-data">

    <input type="hidden" name="csrf_token"
           value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">

    <input type="hidden" name="equipo_id"
           value="<?php echo (int) $equipo["id"]; ?>">

    <label>Seleccionar imagenes:</label>

    <input type="file"
           name="imagenes[]"
           accept=".jpg,.jpeg,.png,.webp"
           multiple
           required>

    <br><br>

    <button type="submit">Subir imagenes</button>

</form>


<h3>Fotografias registradas</h3>

<?php if (empty($imagenes)): ?>

    <p>Este equipo no tiene fotografias registradas.</p>

<?php else: ?>

    <?php foreach ($imagenes as $imagen): ?>

        <img
            src="../actions/verImagenEquipo.php?id=<?php echo (int) $imagen["id"]; ?>"
            alt="Fotografia del equipo"
            width="200">

    <?php endforeach; ?>

<?php endif; ?>


</html>
