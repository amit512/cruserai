<?php
// image.php?file=chair.jpg
$filename = basename($_GET['file']);  // security: only filename
$path = __DIR__ . '/../uploads/' . $filename;

if (file_exists($path)) {
    header("Content-Type: image/jpeg"); // adjust if needed
    readfile($path);
    exit;
} else {
    http_response_code(404);
    echo "Image not found";
}
