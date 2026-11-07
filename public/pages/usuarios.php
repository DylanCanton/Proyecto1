

<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol("Administrador");

$sql = "SELECT usuarios.id, usuarios.nombre,
        usuarios.correo, usuarios.activo,
        roles.nombre AS rol
        FROM usuarios
        INNER JOIN roles ON usuarios.rol_id = roles.id
        ORDER BY usuarios.id DESC";

$stmt = $conexion->prepare($sql);
$stmt->execute();

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Administrar usuarios - SIGEL</title>
</head>
<body>

    <h2>Administracion de usuarios</h2>

    <a href="../panel.php">Volver al panel</a>

    <br><br>

    <a href="crearUsuario.php">Registrar usuario</a>

    <br><br>

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
                    <a href="editarUsuario.php?id=<?php echo (int) $usuario["id"]; ?>">Editar</a>

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

</body>
</html>

