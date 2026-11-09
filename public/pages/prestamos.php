
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol("Operador");

$sql = "SELECT
            prestamos.id,
            usuarios.nombre AS usuario,
            prestamos.fecha_inicio,
            prestamos.fecha_fin,
            prestamos.estado,
            GROUP_CONCAT(
                CONCAT(equipos.codigo, ' - ', equipos.nombre)
                ORDER BY equipos.nombre
                SEPARATOR ', '
            ) AS equipos
        FROM prestamos
        INNER JOIN usuarios
            ON prestamos.usuario_id = usuarios.id
        INNER JOIN prestamo_detalle
            ON prestamos.id = prestamo_detalle.prestamo_id
        INNER JOIN equipos
            ON prestamo_detalle.equipo_id = equipos.id
        GROUP BY
            prestamos.id,
            usuarios.nombre,
            prestamos.fecha_inicio,
            prestamos.fecha_fin,
            prestamos.estado
        ORDER BY prestamos.id DESC";

$stmt = $conexion->prepare($sql);
$stmt->execute();

$prestamos = $stmt->fetchAll(PDO::FETCH_ASSOC);


$opcionesEstados = [
    "solicitado" => ["aprobado", "cancelado"],
    "aprobado" => ["entregado", "cancelado"],
    "entregado" => ["devuelto"],
    "devuelto" => [],
    "cancelado" => []
];

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de prestamos - SIGEL</title>
</head>
<body>

    <h2>Listado de prestamos</h2>

    <a href="../panel.php">Volver al panel</a>

    <br><br>

    <a href="crearPrestamo.php">Registrar nuevo prestamo</a>

    <br><br>

    <?php if (empty($prestamos)): ?>

        <p>No hay prestamos registrados.</p>

    <?php else: ?>

        <table border="1" cellpadding="10">

            <tr>
                <th>ID</th>
                <th>Usuario</th>
                <th>Equipos</th>
                <th>Fecha de inicio</th>
                <th>Fecha de finalizacion</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>

            <?php foreach ($prestamos as $prestamo): ?>

                <tr>
                    <td><?php echo (int) $prestamo["id"]; ?></td>
                    
                    <td>
                        <?php echo htmlspecialchars($prestamo["usuario"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($prestamo["equipos"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($prestamo["fecha_inicio"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($prestamo["fecha_fin"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
                    </td>

                    <td>
                        <?php echo htmlspecialchars($prestamo["estado"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
                    </td>
                    
<td>
    <?php $opciones = $opcionesEstados[$prestamo["estado"]] ?? []; ?>

    <?php if (!empty($opciones)): ?>

        <form action="../actions/cambiarEstadoPrestamo.php" method="POST">

            <input type="hidden" name="csrf_token"
                   value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">

            <input type="hidden" name="prestamo_id"
                   value="<?php echo (int) $prestamo["id"]; ?>">

            <select name="nuevo_estado" required>

                <?php foreach ($opciones as $estado): ?>

                    <option value="<?php echo htmlspecialchars($estado, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">
                        <?php echo htmlspecialchars(ucfirst($estado), ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
                    </option>

                <?php endforeach; ?>

            </select>

            <button type="submit">Cambiar estado</button>

        </form>

    <?php else: ?>

        Sin acciones disponibles

    <?php endif; ?>
</td>

                </tr>

            <?php endforeach; ?>

        </table>

    <?php endif; ?>

</body>
</html>
