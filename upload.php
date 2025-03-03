<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['file'])) {
    $uploadDir = 'uploads/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $file = $_FILES['file'];
    $filename = basename($file['name']);
    $uniqueFilename = uniqid() . '_' . $filename;
    $uploadFilePath = $uploadDir . $uniqueFilename;

    // File restrictions
    $allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'text/plain'];
    $maxSizeInBytes = 5 * 1024 * 1024; // 5 MB

    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid file type. Allowed types: JPG, PNG, PDF, TXT.'
        ]);
        exit;
    }

    if ($file['size'] > $maxSizeInBytes) {
        echo json_encode([
            'status' => 'error',
            'message' => 'File size exceeds the maximum limit of 5 MB.'
        ]);
        exit;
    }

    if ($file['error'] === UPLOAD_ERR_OK) {
        if (move_uploaded_file($file['tmp_name'], $uploadFilePath)) {
            $downloadUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/sandbox/download.php?file=' . urlencode($uniqueFilename);
            echo json_encode([
                'status' => 'success',
                'message' => 'File uploaded successfully!',
                'download_url' => $downloadUrl
            ]);
        } else {
            echo json_encode([
                'status' => 'error',
                'message' => 'Failed to move uploaded file.'
            ]);
        }
    } else {
        echo json_encode([
            'status' => 'error',
            'message' => 'Upload error: ' . $file['error']
        ]);
    }
}
?>
