
<?php

require_once __DIR__ . "/../app/includes/sesion.php";
require_once __DIR__ . "/../app/config/conexion.php";

if (!isset($_SESSION["usuario_id"])) {
    header("Location: index.php");
    exit;
}

$sql = "SELECT usuarios.nombre, roles.nombre AS rol
        FROM usuarios
        INNER JOIN roles ON usuarios.rol_id = roles.id
        WHERE usuarios.id = :id AND usuarios.activo = 1";

$stmt = $conexion->prepare($sql);
$stmt->execute(["id" => $_SESSION["usuario_id"]]);

$usuario = $stmt->fetch(PDO::FETCH_ASSOC);

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

    
    <?php if ($usuario["rol"] === "Administrador"): ?>

        <p>
            <a href="pages/usuarios.php">Administrar usuarios</a>
            
        </p>
        
        <p>
            <a href="pages/categorias.php">Administrar categorias</a>
        </p>


    <?php endif; ?>


    
    <?php if ($usuario["rol"] === "Administrador"): ?>

        <p>
            <a href="pages/usuarios.php">Administrar usuarios</a>
        </p>

    <?php endif; ?>


    <p>Sistema de Gestión de Préstamo de Equipos de Laboratorio</p>

    
<form action="actions/logout.php" method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">

    <button type="submit">Cerrar sesión</button>
</form>


</body>
</html>
