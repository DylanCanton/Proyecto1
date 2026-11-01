
<!DOCTYPE html>

<?php

require_once __DIR__ . "/../app/includes/sesion.php";

?>

<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Login SIGEL</title>
</head>
<body>

    <h2>Iniciar sesión - SIGEL</h2>

    <form action="actions/login.php" method="POST">

        <label>Correo:</label>
        <input type="email" name="correo" required>

        <br><br>

        <label>Contraseña:</label>
        <input type="password" name="password" required>

        <br><br>
        
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">

        <button type="submit">Ingresar</button>

    </form>

</body>
</html>
