<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SKYEtransfer</title>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/styles.css">
    <link rel="shortcut icon" href="logo.png">
    <style>
        /* Progress bar styles */
        .progress-container {
            width: 100%;
            margin-top: 20px;
            display: none; /* Hidden by default */
        }

        .progress-bar-container {
            width: 100%;
            background: #1a1c27;
            border-radius: 5px;
            overflow: hidden;
            margin-top: 10px;
        }

        .progress-bar {
            height: 10px;
            width: 0;
            background: linear-gradient(135deg, #ff007f, #00ffff);
            transition: width 0.2s ease;
        }

        .progress-info {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-top: 5px;
        }

        .cancel-btn {
            margin-top: 10px;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            background: linear-gradient(135deg, #ff5f5f, #ff007f);
            color: #ffffff;
            cursor: pointer;
        }

        .cancel-btn:hover {
            background: linear-gradient(135deg, #ff007f, #ff5f5f);
        }

        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #1a1c27;
            color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.5);
            z-index: 1000;
        }

        .modal-header {
            font-size: 18px;
            margin-bottom: 10px;
        }

        .modal-body {
            font-size: 14px;
            margin-bottom: 20px;
        }

        .modal-footer {
            text-align: right;
        }

        .modal-footer button {
            padding: 10px 15px;
            border: none;
            border-radius: 5px;
            background: linear-gradient(135deg, #ff007f, #00ffff);
            color: #1a1c27;
            cursor: pointer;
        }

        .modal-footer button:hover {
            background: linear-gradient(135deg, #00ffff, #ff007f);
        }

        /* Modal backdrop */
        .modal-backdrop {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 999;
        }
    </style>
</head>
<body>
    <div class="background-art"></div>
    <div class="grid-overlay"></div>

    <div class="upload-container">
        <h2>Upload Your File</h2>
        <p>Drag and drop a file or tap below to select one.</p>
        <!-- <p>Accepted file types are .jpg, .png, .pdf, .txt</p> 
        File restriction message.-->
        <div id="drop-area" class="drop-area">Drop your file here</div>
        <form id="upload-form" method="post" enctype="multipart/form-data">
            <!-- File restrictions enabled -->
            <input type="file" name="file" id="file" accept=".jpg,.png,.pdf,.txt" required>
           <!-- File restrictions disabled -->
     <!--   <input type="file" name="file" id="file" style="display: none;" required> -->
        </form>
        <div class="progress-container" id="progress-container" style="display: none;">
            <span id="file-name" class="file-name"></span>
            <div class="progress-bar-container">
                <div class="progress-bar" id="progress-bar"></div>
            </div>
            <div class="progress-info">
                <span id="upload-percentage">0%</span>
                <span id="time-remaining">Time Remaining: Calculating...</span>
            </div>
            <button class="cancel-btn" id="cancel-btn">Cancel Upload</button>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal" id="upload-modal">
        <div class="modal-header">File Uploaded Successfully</div>
        <div class="modal-body" id="modal-body">
            Your download link is ready.
        </div>
        <div class="modal-footer">
            <button id="close-modal">Close</button>
        </div>
    </div>
    <div class="modal-backdrop" id="modal-backdrop"></div>

    <script>
    const dropArea = document.getElementById('drop-area');
    const fileInput = document.getElementById('file');
    const form = document.getElementById('upload-form');
    const modal = document.getElementById('upload-modal');
    const modalBackdrop = document.getElementById('modal-backdrop');
    const modalBody = document.getElementById('modal-body');
    const closeModalButton = document.getElementById('close-modal');
    const progressBar = document.getElementById('progress-bar');
    const fileNameSpan = document.getElementById('file-name');
    const uploadPercentageSpan = document.getElementById('upload-percentage');
    const timeRemainingSpan = document.getElementById('time-remaining');
    const progressContainer = document.getElementById('progress-container');
    const cancelBtn = document.getElementById('cancel-btn');

    let xhr = null; // For canceling the upload
    let startTime;

    // Drag-and-drop events
    dropArea.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropArea.classList.add('dragover');
    });

    dropArea.addEventListener('dragleave', () => {
        dropArea.classList.remove('dragover');
    });

    dropArea.addEventListener('drop', (e) => {
        e.preventDefault();
        dropArea.classList.remove('dragover');
        fileInput.files = e.dataTransfer.files; // Assign dropped files to input
        autoUpload();
    });

    dropArea.addEventListener('click', () => {
        fileInput.click();
    });

    fileInput.addEventListener('change', () => {
        autoUpload();
    });

    // File restrictions
    fileInput.addEventListener('change', () => {
    const file = fileInput.files[0];
    const allowedTypes = ['image/jpeg', 'image/png', 'application/pdf', 'text/plain']; // MIME types
    const maxSizeInMB = 5; // Maximum file size in MB
    const maxSizeInBytes = maxSizeInMB * 1024 * 1024;

    if (!allowedTypes.includes(file.type)) {
        alert('Invalid file type. Allowed types: JPG, PNG, PDF, TXT.');
        fileInput.value = ''; // Clear the input
        return;
    }

    if (file.size > maxSizeInBytes) {
        alert(`File size exceeds ${maxSizeInMB} MB.`);
        fileInput.value = ''; // Clear the input
        return;
    }

    autoUpload(); // Proceed with upload if validation passes
});

    // Auto upload functionality
    function autoUpload() {
        const file = fileInput.files[0];
        if (!file) return;

        fileNameSpan.textContent = `Uploading: ${file.name}`;
        progressContainer.style.display = 'block'; // Show progress container
        progressBar.style.width = '0%'; // Reset progress bar
        uploadPercentageSpan.textContent = '0%';
        timeRemainingSpan.textContent = 'Time Remaining: Calculating...';

        startTime = Date.now();

        xhr = new XMLHttpRequest();
        xhr.open('POST', 'upload.php', true);

        // Handle progress events
        xhr.upload.onprogress = function (event) {
            if (event.lengthComputable) {
                const elapsedTime = (Date.now() - startTime) / 1000; // in seconds
                const percentComplete = (event.loaded / event.total) * 100;
                const uploadSpeed = event.loaded / elapsedTime; // bytes per second
                const timeRemaining = (event.total - event.loaded) / uploadSpeed; // in seconds

                // Update progress bar and text
                progressBar.style.width = `${percentComplete}%`;
                uploadPercentageSpan.textContent = `${Math.round(percentComplete)}%`;
                timeRemainingSpan.textContent = `Time Remaining: ${Math.max(1, Math.round(timeRemaining))}s`;
            }
        };

        // Handle completion
        xhr.onload = function () {
            if (xhr.status === 200) {
                const response = JSON.parse(xhr.responseText);
                if (response.status === 'success') {
                    showModal(response.download_url);
                    copyToClipboard(response.download_url);
                } else {
                    alert(`Error: ${response.message}`);
                }
            }
            resetProgress();
        };

        // Handle errors
        xhr.onerror = function () {
            alert('An error occurred during the upload.');
            resetProgress();
        };

        xhr.onabort = function () {
            alert('Upload canceled.');
            resetProgress();
        };

        // Send the file
        const formData = new FormData(form);
        xhr.send(formData);

        // Cancel button functionality
        cancelBtn.addEventListener('click', () => {
            if (xhr) {
                xhr.abort();
            }
        });
    }

    function showModal(downloadUrl) {
        modalBody.innerHTML = `
            Your file has been uploaded successfully.<br>
            <a href="${downloadUrl}" target="_blank" class="download-link">${downloadUrl}</a><br>
            <small>(The link has been copied to your clipboard)</small>
        `;
        modal.style.display = 'block';
        modalBackdrop.style.display = 'block';
    }

    function hideProgressBar() {
        progressContainer.style.display = 'none'; // Hide progress container
    }

    function resetProgress() {
        progressContainer.style.display = 'none';
        progressBar.style.width = '0%';
        uploadPercentageSpan.textContent = '0%';
        timeRemainingSpan.textContent = '';
    }

    function copyToClipboard(text) {
        navigator.clipboard.writeText(text).then(() => {
            console.log('Download link copied to clipboard.');
        }).catch((err) => {
            console.error('Could not copy text: ', err);
        });
    }

    closeModalButton.addEventListener('click', () => {
        modal.style.display = 'none';
        modalBackdrop.style.display = 'none';
    });

    modalBackdrop.addEventListener('click', () => {
        modal.style.display = 'none';
        modalBackdrop.style.display = 'none';
    });

    </script>
</body>
</html>
