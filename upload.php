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
            $mappingFile = 'file_mapping.json';
            $mappings = [];

            if (file_exists($mappingFile)) {
                $mappingContents = file_get_contents($mappingFile);
                if ($mappingContents !== false && trim($mappingContents) !== '') {
                    $decodedMappings = json_decode($mappingContents, true);
                    if (is_array($decodedMappings)) {
                        $mappings = $decodedMappings;
                    }
                }
            }

            $mappings[$uniqueFilename] = $uploadFilePath;
            $encodedMappings = json_encode($mappings, JSON_PRETTY_PRINT);

            if ($encodedMappings === false || file_put_contents($mappingFile, $encodedMappings, LOCK_EX) === false) {
                unlink($uploadFilePath);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Failed to update file mapping.'
                ]);
                exit;
            }

            $downloadUrl = $_SERVER['REQUEST_SCHEME'] . '://' . $_SERVER['HTTP_HOST'] . '/sandbox/download.php?file=' . urlencode($uniqueFilename);
            echo json_encode([
                'status' => 'success',
                'message' => 'File uploaded successfully!',
                'download_url' => $downloadUrl,
                'file_id' => $uniqueFilename
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
