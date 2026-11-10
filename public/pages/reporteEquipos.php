
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol("Administrador");

$fechaInicio = $_GET["inicio"] ?? "";
$fechaFin = $_GET["fin"] ?? "";

$resultados = [];
$error = "";

if (isset($_GET["inicio"], $_GET["fin"])) {

    if (!is_string($fechaInicio) || !is_string($fechaFin)) {
        $error = "Las fechas no son validas.";
        $fechaInicio = "";
        $fechaFin = "";
    } else {

        $inicio = DateTimeImmutable::createFromFormat("!Y-m-d", $fechaInicio);
        $fin = DateTimeImmutable::createFromFormat("!Y-m-d", $fechaFin);

        if (
            !$inicio ||
            !$fin ||
            $inicio->format("Y-m-d") !== $fechaInicio ||
            $fin->format("Y-m-d") !== $fechaFin ||
            $fin < $inicio
        ) {
            $error = "Seleccione un rango de fechas valido.";
        } else {

            $sql = "SELECT
                        equipos.codigo,
                        equipos.nombre,
                        COUNT(*) AS total_prestamos
                    FROM prestamo_detalle
                    INNER JOIN prestamos
                        ON prestamo_detalle.prestamo_id = prestamos.id
                    INNER JOIN equipos
                        ON prestamo_detalle.equipo_id = equipos.id
                    WHERE prestamos.estado IN ('entregado', 'devuelto')
                    AND prestamos.fecha_inicio >= :inicio
                    AND prestamos.fecha_inicio < DATE_ADD(:fin, INTERVAL 1 DAY)
                    GROUP BY equipos.id, equipos.codigo, equipos.nombre
                    ORDER BY total_prestamos DESC, equipos.codigo ASC";

            $stmt = $conexion->prepare($sql);

            $stmt->execute([
                "inicio" => $fechaInicio,
                "fin" => $fechaFin
            ]);

            $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de equipos - SIGEL</title>
</head>
<body>

    <h2>Equipos mas prestados</h2>

    <a href="../panel.php">Volver al panel</a>

    <br><br>

    <form action="reporteEquipos.php" method="GET">

        <label>Fecha inicial:</label>
        <input type="date" name="inicio"
               value="<?php echo htmlspecialchars($fechaInicio, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>"
               required>

        <br><br>

        <label>Fecha final:</label>
        <input type="date" name="fin"
               value="<?php echo htmlspecialchars($fechaFin, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>"
               required>

        <br><br>

        <button type="submit">Consultar reporte</button>

    </form>

    <?php if ($error !== ""): ?>

        <p><?php echo htmlspecialchars($error, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?></p>

    <?php elseif (isset($_GET["inicio"], $_GET["fin"])): ?>

        <h3>Resultados del reporte</h3>

        <?php if (empty($resultados)): ?>

            <p>No se encontraron prestamos en ese rango de fechas.</p>

        <?php else: ?>


        <form action="../actions/exportarReporteCSV.php" method="GET">

            <input type="hidden" name="inicio"
                value="<?php echo htmlspecialchars($fechaInicio, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">

            <input type="hidden" name="fin"
                value="<?php echo htmlspecialchars($fechaFin, ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">

            <button type="submit">Exportar CSV</button>

        </form>

        <br>

            <table border="1" cellpadding="10">

                <tr>
                    <th>Codigo</th>
                    <th>Equipo</th>
                    <th>Total de prestamos</th>
                </tr>

                <?php foreach ($resultados as $equipo): ?>

                    <tr>
                        <td>
                            <?php echo htmlspecialchars($equipo["codigo"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($equipo["nombre"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
                        </td>

                        <td>
                            <?php echo (int) $equipo["total_prestamos"]; ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

            </table>

        <?php endif; ?>

    <?php endif; ?>

</body>
</html>
