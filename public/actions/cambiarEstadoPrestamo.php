
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol("Operador");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/prestamos.php");
    exit;
}

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

$prestamo_id = filter_input(INPUT_POST, "prestamo_id", FILTER_VALIDATE_INT);
$nuevo_estado = $_POST["nuevo_estado"] ?? "";

if (
    !$prestamo_id ||
    $prestamo_id <= 0 ||
    !is_string($nuevo_estado)
) {
    header("Location: ../pages/prestamos.php?error=datos");
    exit;
}

$transiciones = [
    "solicitado" => ["aprobado", "cancelado"],
    "aprobado" => ["entregado", "cancelado"],
    "entregado" => ["devuelto"],
    "devuelto" => [],
    "cancelado" => []
];

try {

    $conexion->beginTransaction();

    $sql = "SELECT estado
            FROM prestamos
            WHERE id = :id
            FOR UPDATE";

    $stmt = $conexion->prepare($sql);
    $stmt->execute(["id" => $prestamo_id]);

    $estado_actual = $stmt->fetchColumn();
    $stmt->closeCursor();

    if ($estado_actual === false) {
        throw new DomainException("prestamo");
    }

    $permitidos = $transiciones[$estado_actual] ?? [];

    if (!in_array($nuevo_estado, $permitidos, true)) {
        throw new DomainException("estado");
    }

    $sql = "UPDATE prestamos
            SET estado = :estado
            WHERE id = :id";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "estado" => $nuevo_estado,
        "id" => $prestamo_id
    ]);

    if ($nuevo_estado === "devuelto") {

        $sql = "UPDATE prestamo_detalle
                SET fecha_devolucion = NOW()
                WHERE prestamo_id = :prestamo_id";

        $stmt = $conexion->prepare($sql);

        $stmt->execute([
            "prestamo_id" => $prestamo_id
        ]);
    }

    $conexion->commit();

    header("Location: ../pages/prestamos.php?ok=1");
    exit;

} catch (DomainException $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    header("Location: ../pages/prestamos.php?error=" . $e->getMessage());
    exit;

} catch (Throwable $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    error_log($e->getMessage());

    header("Location: ../pages/prestamos.php?error=guardar");
    exit;
}
