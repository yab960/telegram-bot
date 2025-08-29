<?php
// router.php

// Requested URL path
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// If the requested file exists, serve it as-is.
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;  // Serve the requested resource as static file
}

// Otherwise, route all other requests to your script
require_once __DIR__ . '/get_number.php';
