
<?php

if (
    !empty($_COOKIE["sigel_recordarme"]) &&
    is_string($_COOKIE["sigel_recordarme"])
) {

    $cookie = $_COOKIE["sigel_recordarme"];
    $tokenValido = false;

    if (preg_match('/^[a-f0-9]{18}:[a-f0-9]{64}$/', $cookie)) {

        $partes = explode(":", $cookie);

        $selector = $partes[0];
        $validador = $partes[1];

        require_once __DIR__ . "/../config/conexion.php";

        $sql = "SELECT
                    t.id AS token_id,
                    t.validador_hash,
                    t.expira_en,
                    u.id AS usuario_id,
                    u.nombre,
                    u.rol_id
                FROM auth_tokens t
                INNER JOIN usuarios u ON t.usuario_id = u.id
                WHERE t.selector = :selector
                  AND u.activo = 1
                  AND (
                      u.bloqueado_hasta IS NULL
                      OR u.bloqueado_hasta <= :ahora
                  )";

        $stmt = $conexion->prepare($sql);

        $stmt->execute([
            "selector" => $selector,
            "ahora" => time()
        ]);

        $datos = $stmt->fetch(PDO::FETCH_ASSOC);

        if (
            $datos &&
            strtotime($datos["expira_en"]) > time() &&
            hash_equals(
                $datos["validador_hash"],
                hash("sha256", $validador)
            )
        ) {

            $nuevoSelector = bin2hex(random_bytes(9));
            $nuevoValidador = bin2hex(random_bytes(32));

            $expiracion = time() + (14 * 24 * 60 * 60);

            $sqlActualizar = "UPDATE auth_tokens
                              SET selector = :nuevo_selector,
                                  validador_hash = :nuevo_hash,
                                  expira_en = :expira_en
                              WHERE id = :id
                                AND selector = :selector";

            $stmtActualizar = $conexion->prepare($sqlActualizar);

            $stmtActualizar->execute([
                "nuevo_selector" => $nuevoSelector,
                "nuevo_hash" => hash("sha256", $nuevoValidador),
                "expira_en" => date("Y-m-d H:i:s", $expiracion),
                "id" => $datos["token_id"],
                "selector" => $selector
            ]);

            if ($stmtActualizar->rowCount() === 1) {

                session_regenerate_id(true);

                $_SESSION["usuario_id"] = $datos["usuario_id"];
                $_SESSION["nombre"] = $datos["nombre"];
                $_SESSION["rol_id"] = $datos["rol_id"];
                $_SESSION["ultimo_acceso"] = time();
                $_SESSION["csrf_token"] = bin2hex(random_bytes(32));

                setcookie("sigel_recordarme", $nuevoSelector . ":" . $nuevoValidador, [
                    "expires" => $expiracion,
                    "path" => "/",
                    "secure" => $esHttps,
                    "httponly" => true,
                    "samesite" => "Lax"
                ]);

                $tokenValido = true;
            }
        }
    }

    if (!$tokenValido) {

        setcookie("sigel_recordarme", "", [
            "expires" => time() - 3600,
            "path" => "/",
            "secure" => $esHttps,
            "httponly" => true,
            "samesite" => "Lax"
        ]);
    }
}
