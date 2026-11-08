
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol("Operador");

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../pages/crearPrestamo.php");
    exit;
}

$token = $_POST["csrf_token"] ?? "";

if (
    !is_string($token) ||
    !isset($_SESSION["csrf_token"]) ||
    !hash_equals($_SESSION["csrf_token"], $token)
) {
    http_response_code(403);
    exit("Solicitud no valida.");
}

$usuario_id = filter_input(INPUT_POST, "usuario_id", FILTER_VALIDATE_INT);

$fecha_inicio = $_POST["fecha_inicio"] ?? "";
$fecha_fin = $_POST["fecha_fin"] ?? "";
$equipos = $_POST["equipos"] ?? [];


if (
    !$usuario_id ||
    $usuario_id <= 0 ||
    !is_string($fecha_inicio) ||
    !is_string($fecha_fin) ||
    !is_array($equipos) ||
    count($equipos) < 1 ||
    count($equipos) > 50
) {
    header("Location: ../pages/crearPrestamo.php?error=datos");
    exit;
}

$inicio = DateTimeImmutable::createFromFormat("!Y-m-d\TH:i", $fecha_inicio);
$fin = DateTimeImmutable::createFromFormat("!Y-m-d\TH:i", $fecha_fin);


if (
    !$inicio ||
    !$fin ||
    $inicio->format("Y-m-d\TH:i") !== $fecha_inicio ||
    $fin->format("Y-m-d\TH:i") !== $fecha_fin ||
    $fin <= $inicio
) {
    header("Location: ../pages/crearPrestamo.php?error=fechas");
    exit;
}

$equiposIds = [];

foreach ($equipos as $valor) {

    if (!is_string($valor) && !is_int($valor)) {
        header("Location: ../pages/crearPrestamo.php?error=datos");
        exit;
    }

    $id = filter_var($valor, FILTER_VALIDATE_INT);

    if ($id === false || $id <= 0) {
        header("Location: ../pages/crearPrestamo.php?error=datos");
        exit;
    }

    $equiposIds[] = $id;
}

if (count($equiposIds) !== count(array_unique($equiposIds))) {
    header("Location: ../pages/crearPrestamo.php?error=datos");
    exit;
}

sort($equiposIds, SORT_NUMERIC);

$fechaInicioSQL = $inicio->format("Y-m-d H:i:s");
$fechaFinSQL = $fin->format("Y-m-d H:i:s");


try {

    $conexion->beginTransaction();

    $sql = "SELECT valor
            FROM configuracion
            WHERE clave = 'max_prestamos_activos'
            FOR UPDATE";

    $stmt = $conexion->prepare($sql);
    $stmt->execute();

    $limite = $stmt->fetchColumn();

    if ($limite === false || (int) $limite < 1) {
        throw new DomainException("configuracion");
    }

    $sql = "SELECT id
            FROM usuarios
            WHERE id = :id AND activo = 1
            FOR UPDATE";

    $stmt = $conexion->prepare($sql);
    $stmt->execute(["id" => $usuario_id]);

    if (!$stmt->fetchColumn()) {
        throw new DomainException("usuario");
    }

    $sql = "SELECT COUNT(*)
            FROM prestamos
            WHERE usuario_id = :usuario_id
            AND estado IN ('solicitado', 'aprobado', 'entregado')";

    $stmt = $conexion->prepare($sql);
    $stmt->execute(["usuario_id" => $usuario_id]);

    $activos = (int) $stmt->fetchColumn();

    if ($activos >= (int) $limite) {
        throw new DomainException("limite");
    }

    
    $sql = "SELECT id
            FROM equipos
            WHERE id = :id
            AND estado = 'disponible'
            AND eliminado_en IS NULL
            FOR UPDATE";

    $stmtEquipo = $conexion->prepare($sql);

    $sql = "SELECT prestamos.id
            FROM prestamos
            INNER JOIN prestamo_detalle
            ON prestamos.id = prestamo_detalle.prestamo_id
            WHERE prestamo_detalle.equipo_id = :equipo_id
            AND prestamos.estado IN ('solicitado', 'aprobado', 'entregado')
            AND prestamos.fecha_inicio < :fecha_fin
            AND prestamos.fecha_fin > :fecha_inicio
            LIMIT 1
            FOR UPDATE";

    $stmtOcupado = $conexion->prepare($sql);

    foreach ($equiposIds as $equipo_id) {

        $stmtEquipo->execute([
            "id" => $equipo_id
        ]);

        if (!$stmtEquipo->fetchColumn()) {
            throw new DomainException("equipo");
        }

        $stmtOcupado->execute([
            "equipo_id" => $equipo_id,
            "fecha_inicio" => $fechaInicioSQL,
            "fecha_fin" => $fechaFinSQL
        ]);

        if ($stmtOcupado->fetchColumn()) {
            throw new DomainException("ocupado");
        }
    }


    $sql = "INSERT INTO prestamos
            (usuario_id, operador_id, fecha_inicio, fecha_fin, estado)
            VALUES
            (:usuario_id, :operador_id, :fecha_inicio, :fecha_fin, 'solicitado')";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "usuario_id" => $usuario_id,
        "operador_id" => $_SESSION["usuario_id"],
        "fecha_inicio" => $fechaInicioSQL,
        "fecha_fin" => $fechaFinSQL
    ]);

    $prestamo_id = (int) $conexion->lastInsertId();

    $sql = "INSERT INTO prestamo_detalle
            (prestamo_id, equipo_id)
            VALUES (:prestamo_id, :equipo_id)";

    $stmt = $conexion->prepare($sql);

    foreach ($equiposIds as $equipo_id) {

        $stmt->execute([
            "prestamo_id" => $prestamo_id,
            "equipo_id" => $equipo_id
        ]);
    }

    $conexion->commit();

    header("Location: ../pages/crearPrestamo.php?ok=1");
    exit;


} catch (DomainException $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    header("Location: ../pages/crearPrestamo.php?error=" . $e->getMessage());
    exit;

} catch (Throwable $e) {

    if ($conexion->inTransaction()) {
        $conexion->rollBack();
    }

    error_log($e->getMessage());

    header("Location: ../pages/crearPrestamo.php?error=guardar");
    exit;
}
