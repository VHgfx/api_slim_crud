<?php
$file = $_GET['file'];
$base_dir = getenv('ROOT_API') . 'documents/';
$allowed_folders = ['login_history'];

$type = isset($_GET['type']) ? basename($_GET['type']) : ''; // Sanitize folder name
$fileName = isset($_GET['file']) ? basename($_GET['file']) : '';

if (!in_array($type, $allowed_folders)) {
    http_response_code(403); // Forbidden if folder is not allowed
    echo "Accès interdit";
    exit;
}

$filePath = $base_dir . $type .'/'. $fileName;

if (file_exists($filePath)) {
    $customFileName = isset($_GET['custom_name']) ? $_GET['custom_name'] : basename($file); 

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename="' . basename($customFileName) . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
} else {
    http_response_code(404);
    echo "Fichier introuvable";
}
