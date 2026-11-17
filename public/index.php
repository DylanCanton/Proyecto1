
<?php

// Cargar la sesion
require_once __DIR__ . "/../app/includes/sesion.php";

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login SIGEL</title>
    <link rel="stylesheet" href="estilos.css">
</head>
<body>

    <h2>Iniciar sesión - SIGEL</h2>

    <!-- Formulario de inicio de sesion -->
    <form action="actions/login.php" method="POST">

        <label>Correo:</label>
        <input type="email" name="correo" required>

        <br><br>

        <label>Contraseña:</label>
        <input type="password" name="password" required>

        <br><br>

        <!-- Token de seguridad CSRF -->
        <input type="hidden" name="csrf_token"
            value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">

        <!-- Opcion para recordar el acceso -->
        <label>
            <input type="checkbox" name="recordarme" value="1">
            Recordarme
        </label>

        <br><br>

        <button type="submit">Ingresar</button>

    </form>

</body>
</html>
