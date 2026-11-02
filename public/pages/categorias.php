
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol("Administrador");

$sql = "SELECT id, nombre, descripcion
        FROM categorias
        WHERE eliminado = 0
        ORDER BY id DESC";

$stmt = $conexion->prepare($sql);
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

    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th>
            <th>Nombre</th>
            <th>Descripcion</th>
        </tr>

        <?php foreach ($categorias as $categoria): ?>

        <tr>
            <td><?php echo (int) $categoria["id"]; ?></td>
            <td><?php echo htmlspecialchars($categoria["nombre"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></td>
            <td><?php echo htmlspecialchars($categoria["descripcion"] ?? "", ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></td>
        </tr>

        <?php endforeach; ?>

    </table>

</body>
</html>
