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

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Unable to validate file type at this time.'
        ]);
        exit;
    }

    $detectedType = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($detectedType === false) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Could not determine the uploaded file\'s type.'
        ]);
        exit;
    }

    if (!in_array($detectedType, $allowedTypes, true)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid file type detected. Allowed types: JPG, PNG, PDF, TXT.'
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
