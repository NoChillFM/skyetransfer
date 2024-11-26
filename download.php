<?php
if (isset($_GET['file'])) {
    $fileId = $_GET['file']; // Get the unique file ID

    // Load the file mapping
    $mappingFile = 'file_mapping.json';
    if (file_exists($mappingFile)) {
        $mappings = json_decode(file_get_contents($mappingFile), true);

        // Resolve file ID to file path
        if (isset($mappings[$fileId])) {
            $filePath = $mappings[$fileId];
            $fileName = basename($filePath);

            if (file_exists($filePath)) {
                // Set headers to force download
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $fileName . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($filePath));

                // Read the file and send it to the user
                readfile($filePath);
                exit;
            } else {
                echo "File not found.";
            }
        } else {
            echo "Invalid file identifier.";
        }
    } else {
        echo "File mapping not found.";
    }
} else {
    echo "No file specified.";
}
?>
