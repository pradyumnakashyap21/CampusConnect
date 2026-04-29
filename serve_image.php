<?php
$image_path = __DIR__ . '/uploads/posters/image.png';

if (file_exists($image_path)) {
    header('Content-Type: image/png');
    header('Content-Length: ' . filesize($image_path));
    readfile($image_path);
} else {
    http_response_code(404);
    echo "Image not found";
}
?>
