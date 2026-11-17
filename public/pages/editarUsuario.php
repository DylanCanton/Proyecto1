
<?php

// Cargar la autenticacion
require_once __DIR__ . "/../../app/includes/auth.php";

// Permitir solo al administrador
exigirRol("Administrador");

// Obtener y validar el ID del usuario
$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id <= 0) {
    header("Location: usuarios.php");
    exit;
}

// Buscar los datos del usuario
$sql = "SELECT id, nombre, correo, rol_id
        FROM usuarios
        WHERE id = :id";

$stmt = $conexion->prepare($sql);
$stmt->execute(["id" => $id]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar que el usuario exista
if (!$usuario) {
    header("Location: usuarios.php");
    exit;
}

// Consultar los roles disponibles
$sql = "SELECT id, nombre FROM roles ORDER BY nombre";

$stmt = $conexion->prepare($sql);
$stmt->execute();

$roles = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Editar usuario - SIGEL</title>
    <link rel="stylesheet" href="../estilos.css">
</head>
<body>

    <h2>Editar usuario</h2>

    <a href="usuarios.php">Volver a usuarios</a>

    <br><br>

    <!-- Formulario para editar usuario -->
    <form action="../actions/actualizarUsuario.php" method="POST">

        <!-- Token de seguridad -->
        <input type="hidden" name="csrf_token"
               value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">

        <!-- ID del usuario que se va a editar -->
        <input type="hidden" name="id"
               value="<?php echo (int) $usuario["id"]; ?>">

        <label>Nombre:</label>
        <input type="text" name="nombre" maxlength="100"
               value="<?php echo htmlspecialchars($usuario["nombre"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>"
               required>

        <br><br>

        <label>Correo electronico:</label>
        <input type="email" name="correo" maxlength="190"
               value="<?php echo htmlspecialchars($usuario["correo"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>"
               required>

        <br><br>

        <label>Rol:</label>
        <select name="rol_id" required>

            <!-- Mostrar los roles de MySQL -->
            <?php foreach ($roles as $rol): ?>

                <option value="<?php echo (int) $rol["id"]; ?>"
                    <?php if ($rol["id"] == $usuario["rol_id"]) echo "selected"; ?>>

                    <?php echo htmlspecialchars($rol["nombre"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>

                </option>

            <?php endforeach; ?>

        </select>

        <br><br>

        <button type="submit">Actualizar usuario</button>

    </form>

</body>
</html>
