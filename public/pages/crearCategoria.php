
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol("Administrador");

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Crear Categoria - SIGEL</title>
</head>
<body>

    <h2>Registrar nueva categoria</h2>

    
    <?php if (isset($_GET["error"])): ?>
        <p>No se pudo guardar la categoria. Verifique los datos o utilice otro nombre.</p>
    <?php endif; ?>


    <form action="../actions/guardarCategoria.php" method="POST">

        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION["csrf_token"], ENT_QUOTES | ENT_SUBSTITUTE, "UTF-8"); ?>">

        <label>Nombre:</label>
        <input type="text" name="nombre" maxlength="100" required>

        <br><br>

        <label>Descripcion:</label>
        <textarea name="descripcion" maxlength="255"></textarea>

        <br><br>

        <button type="submit">Guardar categoria</button>

    </form>

    <br>

    <a href="categorias.php">Volver a categorias</a>

</body>
</html>
