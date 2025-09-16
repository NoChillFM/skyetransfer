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
    $allowedFileTypes = [
        'jpg' => [
            'label' => 'JPG/JPEG',
            'mime' => ['image/jpeg', 'image/pjpeg', 'image/jpg'],
        ],
        'jpeg' => [
            'label' => 'JPG/JPEG',
            'mime' => ['image/jpeg', 'image/pjpeg', 'image/jpg'],
        ],
        'png' => [
            'label' => 'PNG',
            'mime' => ['image/png'],
        ],
        'pdf' => [
            'label' => 'PDF',
            'mime' => ['application/pdf'],
        ],
        'txt' => [
            'label' => 'TXT',
            'mime' => ['text/plain'],
        ],
        'mp4' => [
            'label' => 'MP4',
            'mime' => ['video/mp4'],
        ],
        'mp3' => [
            'label' => 'MP3',
            'mime' => ['audio/mpeg', 'audio/mp3'],
        ],
        'zip' => [
            'label' => 'ZIP',
            'mime' => ['application/zip', 'application/x-zip-compressed', 'multipart/x-zip'],
        ],
        'rar' => [
            'label' => 'RAR',
            'mime' => ['application/x-rar-compressed', 'application/vnd.rar'],
        ],
    ];
    $allowedTypeLabels = implode(', ', array_unique(array_map(static function ($info) {
        return $info['label'];
    }, $allowedFileTypes)));
    $genericBrowserMimes = ['application/octet-stream'];
    $maxSizeInBytes = 5 * 1024 * 1024; // 5 MB

    $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

    if (!isset($allowedFileTypes[$extension])) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid file type. Allowed types: ' . $allowedTypeLabels . '.',
        ]);
        exit;
    }

    $allowedMimesForExtension = $allowedFileTypes[$extension]['mime'];
    $browserProvidedMime = isset($file['type']) ? $file['type'] : '';

    if ($browserProvidedMime !== '' && !in_array($browserProvidedMime, $genericBrowserMimes, true) && !in_array($browserProvidedMime, $allowedMimesForExtension, true)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid file type. Allowed types: ' . $allowedTypeLabels . '.',
        ]);
        exit;
    }

    $detectedMimeType = false;
    if (class_exists('finfo')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detectedMimeType = $finfo->file($file['tmp_name']);
        }
    } elseif (function_exists('mime_content_type')) {
        $detectedMimeType = mime_content_type($file['tmp_name']);
    }

    if ($detectedMimeType && !in_array($detectedMimeType, $genericBrowserMimes, true) && !in_array($detectedMimeType, $allowedMimesForExtension, true)) {
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid file type. Allowed types: ' . $allowedTypeLabels . '.',
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
