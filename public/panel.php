
<?php

// Carga la sesion y la conexion
require_once __DIR__ . "/../app/includes/sesion.php";
require_once __DIR__ . "/../app/config/conexion.php";

// Verifica que el usuario inicio sesion
if (!isset($_SESSION["usuario_id"])) {
    header("Location: index.php");
    exit;
}

// Consulta los datos y el rol del usuario
$sql = "SELECT usuarios.nombre, roles.nombre AS rol
        FROM usuarios
        INNER JOIN roles ON usuarios.rol_id = roles.id
        WHERE usuarios.id = :id AND usuarios.activo = 1";

$stmt = $conexion->prepare($sql);
$stmt->execute(["id" => $_SESSION["usuario_id"]]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

// Verificar que el usuario exista y este activo
if (!$usuario) {
    header("Location: index.php");
    exit;
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel SIGEL</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>

    <h2>Bienvenido a SIGEL</h2>

    <p>Usuario:
        <?php echo htmlspecialchars($usuario["nombre"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
    </p>

    <p>Rol:
        <?php echo htmlspecialchars($usuario["rol"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>
    </p>

    <h3>Panel principal</h3>

    <div class="menu">

        <!-- Opciones del administrador -->
        <?php if ($usuario["rol"] === "Administrador"): ?>

            <p>
                <a href="pages/usuarios.php">Administrar usuarios</a>
            </p>

            <p>
                <a href="pages/categorias.php">Administrar categorias</a>
            </p>

            <p>
                <a href="pages/crearEquipo.php">Registrar equipo</a>
            </p>

            <p>
                <a href="pages/reporteEquipos.php">Reportes de equipos</a>
            </p>

        <?php endif; ?>

        <!-- Opcion para ambos roles -->
        <p>
            <a href="pages/equipos.php">Consultar equipos</a>
        </p>

        <!-- Opciones del operador -->
        <?php if ($usuario["rol"] === "Operador"): ?>

            <p>
                <a href="pages/crearPrestamo.php">Registrar prestamo</a>
            </p>

            <p>
                <a href="pages/prestamos.php">Consultar prestamos</a>
            </p>

        <?php endif; ?>

    </div>

    <!-- Cerrar la sesion -->
    <form action="actions/logout.php" method="POST">

        <input type="hidden" name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">

        <button type="submit">Cerrar sesion</button>

    </form>

</body>
</html>
