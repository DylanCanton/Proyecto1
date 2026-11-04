
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol(["Administrador", "Operador"]);

$sql = "SELECT equipos.id, equipos.codigo, equipos.nombre,
        equipos.estado, categorias.nombre AS categoria
        FROM equipos
        INNER JOIN categorias ON equipos.categoria_id = categorias.id
        WHERE equipos.eliminado_en IS NULL
        ORDER BY equipos.id DESC";

$stmt = $conexion->prepare($sql);
$stmt->execute();

$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Equipos - SIGEL</title>
</head>
<body>

    <h2>Listado de equipos</h2>

    <a href="../panel.php">Volver al panel</a>

    <br><br>

    <table border="1" cellpadding="8">
        <tr>
            <th>ID</th>
            <th>Codigo</th>
            <th>Nombre</th>
            <th>Categoria</th>
            <th>Estado</th>
        </tr>

        <?php foreach ($equipos as $equipo): ?>

        <tr>
            <td><?php echo (int) $equipo["id"]; ?></td>
            <td><?php echo htmlspecialchars($equipo["codigo"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></td>
            <td><?php echo htmlspecialchars($equipo["nombre"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></td>
            <td><?php echo htmlspecialchars($equipo["categoria"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></td>
            <td><?php echo htmlspecialchars($equipo["estado"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></td>
        </tr>

        <?php endforeach; ?>

    </table>

</body>
</html>
