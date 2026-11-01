
<?php

$esHttps = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] !== "off";

session_set_cookie_params([
    "lifetime" => 0,
    "path" => "/",
    "secure" => $esHttps,
    "httponly" => true,
    "samesite" => "Lax"
]);

session_start();

if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
