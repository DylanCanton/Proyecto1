
<?php

require_once __DIR__ . "/../../app/includes/auth.php";

exigirRol("Administrador");

$fechaInicio = $_GET["inicio"] ?? "";
$fechaFin = $_GET["fin"] ?? "";

if (
    !is_string($fechaInicio) ||
    !is_string($fechaFin) ||
    !preg_match("/^\d{4}-\d{2}-\d{2}$/", $fechaInicio) ||
    !preg_match("/^\d{4}-\d{2}-\d{2}$/", $fechaFin)
) {
    http_response_code(400);
    exit("Fechas no validas.");
}

$inicio = DateTimeImmutable::createFromFormat("!Y-m-d", $fechaInicio);
$fin = DateTimeImmutable::createFromFormat("!Y-m-d", $fechaFin);

if (
    !$inicio ||
    !$fin ||
    $inicio->format("Y-m-d") !== $fechaInicio ||
    $fin->format("Y-m-d") !== $fechaFin ||
    $fin < $inicio
) {
    http_response_code(400);
    exit("Rango de fechas no valido.");
}

try {

    $sql = "SELECT
                equipos.codigo,
                equipos.nombre,
                COUNT(*) AS total_prestamos
            FROM prestamo_detalle
            INNER JOIN prestamos
                ON prestamo_detalle.prestamo_id = prestamos.id
            INNER JOIN equipos
                ON prestamo_detalle.equipo_id = equipos.id
            WHERE prestamos.estado IN ('entregado', 'devuelto')
            AND prestamos.fecha_inicio >= :inicio
            AND prestamos.fecha_inicio < DATE_ADD(:fin, INTERVAL 1 DAY)
            GROUP BY equipos.id, equipos.codigo, equipos.nombre
            ORDER BY total_prestamos DESC, equipos.codigo ASC";

    $stmt = $conexion->prepare($sql);

    $stmt->execute([
        "inicio" => $fechaInicio,
        "fin" => $fechaFin
    ]);

    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log($e->getMessage());

    http_response_code(500);
    exit("No fue posible generar el reporte.");
}

header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=reporte_equipos.csv");
header("Cache-Control: no-store");

$archivo = fopen("php://output", "w");

fwrite($archivo, "\xEF\xBB\xBF");

fputcsv($archivo, [
    "Codigo",
    "Equipo",
    "Total de prestamos"
], ",", '"', "");

foreach ($resultados as $equipo) {

    $codigo = $equipo["codigo"];
    $nombre = $equipo["nombre"];

    if (preg_match('/^[\x00-\x20]*[=+\-@]/', $codigo)) {
        $codigo = "'" . $codigo;
    }

    if (preg_match('/^[\x00-\x20]*[=+\-@]/', $nombre)) {
        $nombre = "'" . $nombre;
    }

    fputcsv($archivo, [
        $codigo,
        $nombre,
        (int) $equipo["total_prestamos"]
    ], ",", '"', "");
}

fclose($archivo);
exit;
