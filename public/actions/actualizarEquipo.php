
<?php

// Cargar la autenticacion
require_once __DIR__ . "/../../app/includes/auth.php";

// Permitir solo al administrador
exigirRol("Administrador");

// Verificar el metodo POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/equipos.php");
    exit;
}

// Validar el token CSRF
$token = $_POST["csrf_token"] ?? "";

if (!is_string($token) || !hash_equals($_SESSION["csrf_token"] ?? "", $token)) {
    http_response_code(403);
    exit("Solicitud no valida.");
}

// Recibir los datos del formulario
$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);
$categoria_id = filter_input(INPUT_POST, "categoria_id", FILTER_VALIDATE_INT);
$codigo = $_POST["codigo"] ?? "";
$nombre = $_POST["nombre"] ?? "";
$estado = $_POST["estado"] ?? "";

// Validar el ID y los tipos de datos
if (!$id || $id <= 0 || !is_string($codigo) || !is_string($nombre) || !is_string($estado)) {
    header("Location: ../pages/equipos.php");
    exit;
}

// Eliminar espacios innecesarios
$codigo = trim($codigo);
$nombre = trim($nombre);

// Validar los datos del equipo
if (
    !preg_match('/^[A-Za-z0-9-]{1,30}$/', $codigo) ||
    $nombre === "" ||
    strlen($nombre) > 100 ||
    !$categoria_id ||
    $categoria_id <= 0 ||
    !in_array($estado, ["disponible", "mantenimiento"], true)
) {
    header("Location: ../pages/editarEquipo.php?id=$id&error=1");
    exit;
}

try {

    // Verificar que la categoria exista y no este eliminada
    $sql = "SELECT id FROM categorias
            WHERE id = :id AND eliminado = 0";

    $stmt = $conexion->prepare($sql);
    $stmt->execute(["id" => $categoria_id]);

    if (!$stmt->fetch()) {
        header("Location: ../pages/editarEquipo.php?id=$id&error=1");
        exit;
    }

    // Actualizar los datos del equipo
    $sql = "UPDATE equipos
            SET categoria_id = :categoria_id,
                codigo = :codigo,
                nombre = :nombre,
                estado = :estado,
                version = version + 1
            WHERE id = :id AND eliminado_en IS NULL";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "categoria_id" => $categoria_id,
        "codigo" => $codigo,
        "nombre" => $nombre,
        "estado" => $estado,
        "id" => $id
    ]);

    // Regresar a la lista de equipos
    header("Location: ../pages/equipos.php");
    exit;

} catch (PDOException $e) {

    // Registrar el error en el servidor
    error_log($e->getMessage());

    header("Location: ../pages/editarEquipo.php?id=$id&error=1");
    exit;
}
