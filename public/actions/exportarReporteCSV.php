
<?php

// Cargar la autenticacion
require_once __DIR__ . "/../../app/includes/auth.php";

// Permitir solo al administrador
exigirRol("Administrador");

// Obtener las fechas del reporte
$fechaInicio = $_GET["inicio"] ?? "";
$fechaFin = $_GET["fin"] ?? "";

// Validar el formato de las fechas
if (
    !is_string($fechaInicio) ||
    !is_string($fechaFin) ||
    !preg_match("/^\d{4}-\d{2}-\d{2}$/", $fechaInicio) ||
    !preg_match("/^\d{4}-\d{2}-\d{2}$/", $fechaFin)
) {
    http_response_code(400);
    exit("Fechas no validas.");
}

// Convertir las fechas recibidas
$inicio = DateTimeImmutable::createFromFormat("!Y-m-d", $fechaInicio);
$fin = DateTimeImmutable::createFromFormat("!Y-m-d", $fechaFin);

// Comprobar que el rango sea valido
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

    // Consultar los equipos mas prestados
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

    // Obtener los resultados de MySQL
    $resultados = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    // Registrar el error en el servidor
    error_log($e->getMessage());

    http_response_code(500);
    exit("No fue posible generar el reporte.");
}

// Configurar la descarga del archivo CSV
header("Content-Type: text/csv; charset=UTF-8");
header("Content-Disposition: attachment; filename=reporte_equipos.csv");
header("Cache-Control: no-store");

// Abrir la salida del archivo
$archivo = fopen("php://output", "w");

// Agregar compatibilidad con caracteres especiales
fwrite($archivo, "\xEF\xBB\xBF");

// Escribir los encabezados del reporte
fputcsv($archivo, [
    "Codigo",
    "Equipo",
    "Total de prestamos"
], ",", '"', "");

// Recorrer los resultados del reporte
foreach ($resultados as $equipo) {

    $codigo = $equipo["codigo"];
    $nombre = $equipo["nombre"];

    // Evitar formulas en el codigo al abrirlo en Excel
    if (preg_match('/^[\x00-\x20]*[=+\-@]/', $codigo)) {
        $codigo = "'" . $codigo;
    }

    // Evitar formulas en el nombre al abrirlo en Excel
    if (preg_match('/^[\x00-\x20]*[=+\-@]/', $nombre)) {
        $nombre = "'" . $nombre;
    }

    // Escribir los datos del equipo en el CSV
    fputcsv($archivo, [
        $codigo,
        $nombre,
        (int) $equipo["total_prestamos"]
    ], ",", '"', "");
}

// Cerrar el archivo
fclose($archivo);
exit;
