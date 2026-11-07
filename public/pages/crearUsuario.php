
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol("Administrador");

$sql = "SELECT id, nombre FROM roles ORDER BY nombre";

$stmt = $conexion->prepare($sql);
$stmt->execute();

$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar usuario - SIGEL</title>
</head>
<body>

    <h2>Registrar nuevo usuario</h2>

    <a href="usuarios.php">Volver a usuarios</a>

    <br><br>

    <form action="../actions/guardarUsuario.php" method="POST">

        <input type="hidden" name="csrf_token"
               value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">

        <label>Nombre:</label>
        <input type="text" name="nombre" maxlength="100" required>

        <br><br>

        <label>Correo electronico:</label>
        <input type="email" name="correo" maxlength="190" required>

        <br><br>

        <label>Contrasena:</label>
        <input type="password" name="password" minlength="8" required>

        <br><br>

        <label>Rol:</label>
        <select name="rol_id" required>

            <option value="">Seleccione un rol</option>

            <?php foreach ($roles as $rol): ?>

                <option value="<?php echo (int) $rol["id"]; ?>">
                    <?php echo htmlspecialchars($rol["nombre"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
                </option>

            <?php endforeach; ?>

        </select>

        <br><br>

        <button type="submit">Guardar usuario</button>

    </form>

</body>
</html>

