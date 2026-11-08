

<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol("Operador");

$sql = "SELECT id, nombre, correo
        FROM usuarios
        WHERE activo = 1
        ORDER BY nombre";

$stmt = $conexion->prepare($sql);
$stmt->execute();

$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT id, codigo, nombre
        FROM equipos
        WHERE eliminado_en IS NULL
        AND estado = 'disponible'
        ORDER BY nombre";

$stmt = $conexion->prepare($sql);
$stmt->execute();

$equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Registrar prestamo - SIGEL</title>
</head>
<body>

    <h2>Registrar prestamo</h2>

    
<?php

$mensajes = [
    "datos" => "Revise los datos ingresados.",
    "fechas" => "Las fechas del prestamo no son validas.",
    "configuracion" => "No se encontro el limite de prestamos.",
    "usuario" => "El usuario no esta activo.",
    "limite" => "El usuario alcanzo el limite de prestamos activos.",
    "equipo" => "Uno de los equipos no esta disponible.",
    "ocupado" => "Uno de los equipos ya esta reservado en esas fechas.",
    "guardar" => "Ocurrio un error al guardar el prestamo."
];

$error = $_GET["error"] ?? "";

if (is_string($error) && isset($mensajes[$error])) {
    echo "<p>" . htmlspecialchars($mensajes[$error], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8") . "</p>";
}

if (($_GET["ok"] ?? "") === "1") {
    echo "<p>Prestamo registrado correctamente.</p>";
}

?>


    <a href="../panel.php">Volver al panel</a>

    <br><br>

    <form action="../actions/guardarPrestamo.php" method="POST">

        <input type="hidden" name="csrf_token"
               value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">

        <label>Usuario:</label>

        <select name="usuario_id" required>
            <option value="">Seleccione un usuario</option>

            <?php foreach ($usuarios as $usuario): ?>

                <option value="<?php echo (int) $usuario["id"]; ?>">
                    <?php echo htmlspecialchars($usuario["nombre"] . " - " . $usuario["correo"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
                </option>

            <?php endforeach; ?>

        </select>

        <br><br>

        <label>Fecha de inicio:</label>
        <input type="datetime-local" name="fecha_inicio" required>

        <br><br>

        <label>Fecha de finalizacion:</label>
        <input type="datetime-local" name="fecha_fin" required>

        <br><br>

        <h3>Seleccionar equipos</h3>

        <?php if (empty($equipos)): ?>

            <p>No hay equipos disponibles.</p>

        <?php else: ?>

            <?php foreach ($equipos as $equipo): ?>

                <label>
                    <input type="checkbox" name="equipos[]"
                           value="<?php echo (int) $equipo["id"]; ?>">

                    <?php echo htmlspecialchars($equipo["codigo"] . " - " . $equipo["nombre"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
                </label>

                <br>

            <?php endforeach; ?>

        <?php endif; ?>

        <br>

        <button type="submit" <?php if (empty($equipos)) echo "disabled"; ?>>
            Registrar prestamo
        </button>

    </form>

</body>
</html>
