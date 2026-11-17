
<?php

// Cargar la autenticacion
require_once __DIR__ . "/../../app/includes/auth.php";

// Permitir solo al administrador
exigirRol("Administrador");

// Consultar las categorias disponibles
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
    <link rel="stylesheet" href="../estilos.css">
</head>
<body>

    <h2>Registrar nuevo equipo</h2>

    <!-- Mostrar mensaje si ocurre un error -->
    <?php if (isset($_GET["error"])): ?>
        <p>No se pudo guardar el equipo. Verifique los datos o utilice otro codigo.</p>
    <?php endif; ?>

    <!-- Formulario para registrar equipos -->
    <form action="../actions/guardarEquipo.php" method="POST">

        <!-- Token de seguridad CSRF -->
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

            <!-- Mostrar las categorias de MySQL -->
            <?php foreach ($categorias as $categoria): ?>

                <option value="<?php echo (int) $categoria["id"]; ?>">
                    <?php echo htmlspecialchars($categoria["nombre"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
                </option>

            <?php endforeach; ?>

        </select>

        <br><br>

        <!-- Seleccionar el estado inicial del equipo -->
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
