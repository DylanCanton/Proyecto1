
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol("Administrador");

$sql = "SELECT id, nombre
        FROM categorias
        WHERE eliminado = 0
        ORDER BY nombre ASC";

$stmt = $conexion->prepare($sql);
$stmt->execute();

$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Equipo - SIGEL</title>
</head>
<body>

    <h2>Registrar nuevo equipo</h2>

    <?php if (isset($_GET["error"])): ?>
        <p>No se pudo guardar el equipo. Verifique los datos o utilice otro codigo.</p>
    <?php endif; ?>


    <form action="../actions/guardarEquipo.php" method="POST">

        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">

        <label>Codigo:</label>
        <input type="text" name="codigo" maxlength="30" required>

        <br><br>

        <label>Nombre:</label>
        <input type="text" name="nombre" maxlength="100" required>

        <br><br>

        <label>Categoria:</label>
        <select name="categoria_id" required>

            <option value="">Seleccione una categoria</option>

            <?php foreach ($categorias as $categoria): ?>

                <option value="<?php echo (int) $categoria["id"]; ?>">
                    <?php echo htmlspecialchars($categoria["nombre"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
                </option>

            <?php endforeach; ?>

        </select>

        <br><br>

        <label>Estado:</label>
        <select name="estado" required>
            <option value="disponible">Disponible</option>
            <option value="mantenimiento">Mantenimiento</option>
        </select>

        <br><br>

        <button type="submit">Guardar equipo</button>

    </form>

    <br>

    <a href="equipos.php">Volver a equipos</a>

</body>
</html>
