
<?php

// Cargar la autenticacion
require_once __DIR__ . "/../../app/includes/auth.php";

// Permitir solo al operador
exigirRol("Operador");

// Verificar el metodo POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/prestamos.php");
    exit;
}

// Validar el token CSRF
$token = $_POST["csrf_token"] ?? "";

if (
    !is_string($token) ||
    !isset($_SESSION["csrf_token"]) ||
    !is_string($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Solicitud no valida.");
}

// Recibir los datos del formulario
$prestamo_id = filter_input(INPUT_POST, "prestamo_id", FILTER_VALIDATE_INT);
$nuevo_estado = $_POST["nuevo_estado"] ?? "";

// Validar el ID y el nuevo estado
if (
    !$prestamo_id ||
    $prestamo_id <= 0 ||
    !is_string($nuevo_estado)
) {
    header("Location: ../pages/prestamos.php?error=datos");
    exit;
}

// Definir los cambios de estado permitidos
$transiciones = [
    "solicitado" => ["aprobado", "cancelado"],
    "aprobado" => ["entregado", "cancelado"],
    "entregado" => ["devuelto"],
    "devuelto" => [],
    "cancelado" => []
];

try {

    // Iniciar una transaccion
    $conexion->beginTransaction();

    // Consultar el estado actual del prestamo
    $sql = "SELECT estado
            FROM prestamos
            WHERE id = :id
            FOR UPDATE";

    $stmt = $conexion->prepare($sql);
    $stmt->execute(["id" => $prestamo_id]);

    $estado_actual = $stmt->fetchColumn();
    $stmt->closeCursor();

    // Verificar que el prestamo exista
    if ($estado_actual === false) {
        throw new DomainException("prestamo");
    }

    // Comprobar que el cambio este permitido
    $permitidos = $transiciones[$estado_actual] ?? [];

    if (!in_array($nuevo_estado, $permitidos, true)) {
        throw new DomainException("estado");
    }

    // Actualizar el estado del prestamo
    $sql = "UPDATE prestamos
            SET estado = :estado
            WHERE id = :id";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "estado" => $nuevo_estado,
        "id" => $prestamo_id
    ]);

    // Registrar la fecha cuando se devuelve el equipo
    if ($nuevo_estado === "devuelto") {

        $sql = "UPDATE prestamo_detalle
                SET fecha_devolucion = NOW()
                WHERE prestamo_id = :prestamo_id";

        $stmt = $conexion->prepare($sql);

        $stmt->execute([
            "prestamo_id" => $prestamo_id
        ]);
    }

    // Confirmar los cambios en MySQL
    $conexion->commit();

    header("Location: ../pages/prestamos.php?ok=1");
    exit;

} catch (DomainException $e) {

    // Deshacer los cambios si falla una validacion
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    header("Location: ../pages/prestamos.php?error=" . $e->getMessage());
    exit;

} catch (Throwable $e) {

    // Deshacer los cambios si ocurre otro error
    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    // Registrar el error en el servidor
    error_log($e->getMessage());

    header("Location: ../pages/prestamos.php?error=guardar");
    exit;
}
