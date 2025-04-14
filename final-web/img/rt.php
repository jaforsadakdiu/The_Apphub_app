<?php
include('connect.php');  // Include the database connection

// Handle Insert Message
if (isset($_POST['new_message'])) {
    $new_message = $_POST['new_message'];

    // Sanitize message input to prevent SQL injection
    $new_message = $conn->real_escape_string($new_message);

    // SQL query to insert the new message
    $sql = "INSERT INTO messages (message) VALUES ('$new_message')";

    // Execute the query
    if ($conn->query($sql) === TRUE) {
        echo 'Message inserted successfully!';
    } else {
        echo 'Error: ' . $conn->error;
    }
}

// Handle Update Message (AJAX Request)
if (isset($_POST['id']) && isset($_POST['message'])) {
    $id = $_POST['id'];
    $message = $_POST['message'];

    // Sanitize message input to prevent SQL injection
    $message = $conn->real_escape_string($message);

    // SQL query to update the message
    $sql = "UPDATE messages SET message = '$message' WHERE id = $id";

    // Execute the query
    if ($conn->query($sql) === TRUE) {
        echo 'success';
    } else {
        echo 'error';
    }
    exit();  // Exit after handling the update request
}

// Handle Delete Message
if (isset($_POST['delete_id'])) {
    $delete_id = $_POST['delete_id'];

    // SQL query to delete the message
    $sql = "DELETE FROM messages WHERE id = $delete_id";

    // Execute the query
    if ($conn->query($sql) === TRUE) {
        echo 'Message deleted successfully!';
    } else {
        echo 'Error: ' . $conn->error;
    }
}

// SQL query to retrieve all messages
$sql = "SELECT * FROM messages ORDER BY created_at DESC";
$result = $conn->query($sql);
?>



<!-- video code-->

<?php
// Database connection
$hostname = "localhost";
$username = "root";
$password = "";
$database_name = "test_db";
$connection = mysqli_connect($hostname, $username, $password, $database_name);

if (!$connection) {
    echo "Connection failed!";
    exit();
}

// Handle file upload (video upload)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['video_file'])) {
    $uploaded_video_name = $_FILES['video_file']['name'];
    $temporary_name = $_FILES['video_file']['tmp_name'];
    $upload_error = $_FILES['video_file']['error'];

    if ($upload_error === 0) {
        $file_extension = pathinfo($uploaded_video_name, PATHINFO_EXTENSION);
        $file_extension_lower = strtolower($file_extension);
        $allowed_extensions = array("mp4", 'webm', 'avi', 'flv');

        if (in_array($file_extension_lower, $allowed_extensions)) {
            $unique_video_name = uniqid("video-", true) . '.' . $file_extension_lower;
            $upload_path = 'uploads/' . $unique_video_name;
            move_uploaded_file($temporary_name, $upload_path);

            // Insert video path into the database
            $insert_query = "INSERT INTO video(video_url) VALUES('$unique_video_name')";
            mysqli_query($connection, $insert_query);
        } else {
            $error_message = "You can't upload files of this type";
        }
    }
}

// Handle video update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['video_id']) && isset($_FILES['new_video'])) {
    $video_id = $_POST['video_id'];
    $new_video_file = $_FILES['new_video'];

    // Handle file upload
    $upload_directory = "uploads/";
    $target_video_file = $upload_directory . basename($new_video_file["name"]);
    if (move_uploaded_file($new_video_file["tmp_name"], $target_video_file)) {
        $update_query = "UPDATE video SET video_url = '" . basename($new_video_file["name"]) . "' WHERE id = '$video_id'";
        mysqli_query($connection, $update_query);
    }
}

// Handle video deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['video_id']) && isset($_POST['delete_video'])) {
    $video_id = $_POST['video_id'];

    // Get the video file name
    $select_query = "SELECT video_url FROM video WHERE id = '$video_id'";
    $result_set = mysqli_query($connection, $select_query);
    $video_record = mysqli_fetch_assoc($result_set);

    // Delete the video file from the server
    $file_path = "uploads/" . $video_record['video_url'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Delete the video from the database
    $delete_query = "DELETE FROM video WHERE id = '$video_id'";
    mysqli_query($connection, $delete_query);
}

// Fetch videos for display
$fetch_query = "SELECT * FROM video ORDER BY id DESC";
$videos_result = mysqli_query($connection, $fetch_query);
?>






<!-- image-->


<?php
// Database connection
$conn = mysqli_connect("localhost", "root", "", "upload");

if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Handle image deletion
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $result = mysqli_query($conn, "SELECT image FROM tb_uploadi WHERE id = $id");
    $row = mysqli_fetch_assoc($result);
    $image = $row['image'];

    // Delete image file from the server
    if (file_exists("img/" . $image)) {
        unlink("img/" . $image);
    }

    // Delete record from the database
    mysqli_query($conn, "DELETE FROM tb_uploadi WHERE id = $id");

    // Reload the page after deletion
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle image update
if (isset($_POST['update'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];

    // Check if a new image is uploaded
    if ($_FILES['image']['error'] == 0) {
        $imageTmp = $_FILES['image']['tmp_name'];
        $imageName = $_FILES['image']['name'];
        $imagePath = "img/" . $imageName;

        // Delete old image if it exists
        $result = mysqli_query($conn, "SELECT image FROM tb_uploadi WHERE id = $id");
        $row = mysqli_fetch_assoc($result);
        $oldImage = $row['image'];

        if (file_exists("img/" . $oldImage)) {
            unlink("img/" . $oldImage);
        }

        // Move the uploaded image to the "img" directory
        move_uploaded_file($imageTmp, $imagePath);

        // Update the database with the new name and image
        $updateQuery = "UPDATE tb_uploadi SET name = '$name', image = '$imageName' WHERE id = $id";
    } else {
        // If no image is uploaded, just update the name
        $updateQuery = "UPDATE tb_uploadi SET name = '$name' WHERE id = $id";
    }

    // Execute the update query
    mysqli_query($conn, $updateQuery);

    // Reload the page after updating
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Handle image upload
if (isset($_POST["submit"])) {
    $name = $_POST["name"];
    if ($_FILES["image"]["error"] == 4) {
        echo "<script> alert('Image Does Not Exist'); </script>";
    } else {
        $fileName = $_FILES["image"]["name"];
        $fileSize = $_FILES["image"]["size"];
        $tmpName = $_FILES["image"]["tmp_name"];

        $validImageExtension = ['jpg', 'jpeg', 'png'];
        $imageExtension = explode('.', $fileName);
        $imageExtension = strtolower(end($imageExtension));

        if (!in_array($imageExtension, $validImageExtension)) {
            echo "<script> alert('Invalid Image Extension'); </script>";
        } else if ($fileSize > 1000000) {
            echo "<script> alert('Image Size Is Too Large'); </script>";
        } else {
            $newImageName = uniqid() . '.' . $imageExtension;
            move_uploaded_file($tmpName, 'img/' . $newImageName);
            $query = "INSERT INTO tb_uploadi (name, image) VALUES ('$name', '$newImageName')";
            mysqli_query($conn, $query);
            echo "<script> alert('Successfully Added'); document.location.href = '" . $_SERVER['PHP_SELF'] . "'; </script>";
        }
    }
}

// Display the data (image table)
$rows = mysqli_query($conn, "SELECT * FROM tb_uploadi ORDER BY id DESC");
?>


<?php
// Handle edit
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $result = mysqli_query($conn, "SELECT * FROM tb_uploadi WHERE id = $id");
    $row = mysqli_fetch_assoc($result);
    ?>
    <!-- Update Form -->
    <h2>Update Data</h2>
    <form method="POST" action="" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $row['id']; ?>">
        <label for="name">Name:</label>
        <input type="text" id="name" name="name" value="<?php echo $row['name']; ?>" required>
        <br><br>

        <label for="image">Update Image:</label>
        <input type="file" id="image" name="image">
        <br><br>

        <img src="img/<?php echo $row['image']; ?>" width="200" title="<?php echo $row['image']; ?>">
        <br><br>

        <input type="submit" name="update" value="Update">
    </form>
    <?php
}
?>

<!--file-->

<?php
// Database connection details
$server = "localhost";
$username = "root";
$password = "";
$database = "fileuploaddownload";

$connection = new mysqli($server, $username, $password, $database);

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

// File upload functionality
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["uploaded_file"])) {
    if (isset($_FILES["uploaded_file"]) && $_FILES["uploaded_file"]["error"] == 0) {
        $directory = "uploads/"; // Desired directory for uploaded files
        $file_name = basename($_FILES["uploaded_file"]["name"]);
        
        // Sanitize filename by replacing spaces with underscores and removing special characters
        $cleaned_filename = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', $file_name);
        $destination_file = $directory . $cleaned_filename;
        $file_extension = strtolower(pathinfo($destination_file, PATHINFO_EXTENSION));

        // Check if the file is allowed
        $allowed_extensions = array("jpg", "jpeg", "png", "gif", "pdf");
        if (!in_array($file_extension, $allowed_extensions)) {
            echo "Sorry, only JPG, JPEG, PNG, GIF, and PDF files are allowed.";
        } else {
            // Move the uploaded file to the specified directory
            if (move_uploaded_file($_FILES["uploaded_file"]["tmp_name"], $destination_file)) {
                // File upload success, now store information in the database
                $stored_filename = $cleaned_filename;
                $stored_filesize = $_FILES["uploaded_file"]["size"];
                $stored_filetype = $_FILES["uploaded_file"]["type"];

                // Insert the file information into the database
                $stmt = $connection->prepare("INSERT INTO filess (filename, filesize, filetype) VALUES (?, ?, ?)");
                $stmt->bind_param("sis", $stored_filename, $stored_filesize, $stored_filetype); // "s" for string, "i" for integer
                if ($stmt->execute()) {
                    echo "The file " . basename($_FILES["uploaded_file"]["name"]) . " has been uploaded and the information has been stored in the database.";
                } else {
                    echo "Sorry, there was an error uploading your file and storing information in the database: " . $connection->error;
                }

                // Close the statement
                $stmt->close();
            } else {
                echo "Sorry, there was an error uploading your file.";
            }
        }
    } else {
        echo "No file was uploaded.";
    }
}

// Delete operation
if (isset($_GET['remove'])) {
    $file_id = $_GET['remove'];

    // Ensure the file_id is numeric and valid
    if (is_numeric($file_id)) {
        // Fetch the filename from the database to delete the file
        $query = "SELECT filename FROM filess WHERE id = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("i", $file_id);
        $stmt->execute();
        $stmt->bind_result($filename_to_remove);
        $stmt->fetch();
        $stmt->close();

        // Delete the file from the server
        $file_path = "uploads/" . $filename_to_remove;
        if (file_exists($file_path)) {
            unlink($file_path);  // Remove the old file
        }

        // Delete the record from the database
        $query = "DELETE FROM filess WHERE id = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("i", $file_id);
        $stmt->execute();
        $stmt->close();
    }

    // Refresh the page after deletion
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Update operation (upload new file)
if (isset($_POST['modify'])) {
    $file_id = $_POST['file_id'];
    $new_file_name = $_POST['filename'];
    $new_file_type = $_POST['filetype'];

    // Check if a new file is uploaded
    if ($_FILES['uploaded_file']['error'] == 0) {
        // Delete old file from the server
        $query = "SELECT filename FROM filess WHERE id = ?";
        $stmt = $connection->prepare($query);
        $stmt->bind_param("i", $file_id);
        $stmt->execute();
        $stmt->bind_result($old_file_name);
        $stmt->fetch();
        $stmt->close();

        $old_file_path = "uploads/" . $old_file_name;
        if (file_exists($old_file_path)) {
            unlink($old_file_path);  // Remove old file
        }

        // Process new file upload
        $new_file = $_FILES['uploaded_file'];
        $new_file_path = "uploads/" . basename($new_file['name']);
        move_uploaded_file($new_file['tmp_name'], $new_file_path);
        $new_file_name = $new_file['name'];
    }

    // Update the database with the new file name and type
    $query = "UPDATE filess SET filename = ?, filetype = ? WHERE id = ?";
    $stmt = $connection->prepare($query);
    $stmt->bind_param("ssi", $new_file_name, $new_file_type, $file_id);
    $stmt->execute();
    $stmt->close();

    // Refresh the page after update
    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

// Fetch the uploaded files from the database
$query = "SELECT * FROM filess";
$file_results = $connection->query($query);
?>





<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BLOGS</title>
    <link rel="stylesheet" href="styles.css">
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>




    <style>
        body {
            display: flex;
            justify-content: center;
            align-items: center;
            flex-direction: column;
            min-height: 100vh;
        }
        input {
            font-size: 2rem;
        }
        a {
            text-decoration: none;
            color: #006CFF;
            font-size: 1.5rem;
            margin: 10px;
        }
        button {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 10px;
            cursor: pointer;
            font-size: 1rem;
        }
        button:hover {
            background-color: #d32f2f;
        }
        .update-button {
            background-color: #007bff;
        }
        .update-button:hover {
            background-color: #0056b3;
        }
        .alb {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }
        .video-item {
            margin: 20px;
            text-align: center;
        }
        video {
            width: 640px;
            height: 360px;
        }
    </style>
    <script>
        // JavaScript function to show the update form when "Update" button is clicked
        function showUpdateForm(videoId) {
            var form = document.getElementById("update-form-" + videoId);
            form.style.display = "block";  // Show the form
        }
    </script>






</head>
<body>

<header>
        <nav>
            <!-- Link to show messages -->
            <a href="http://localhost/lgsg/see.php" class="btns">Show Messages</a>
        </nav>

 <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.9.1/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">




    </header>

    <div class="messages-container">
        <h2>BLOGS</h2>

        <!-- Insert New Message Form -->
        <form method="POST" action="" class="insert-form">
            <textarea name="new_message" placeholder="Enter your writings..." required></textarea><br>
            <button type="submit">Submit</button>
        </form>

        <?php if ($result->num_rows > 0): ?>
            <div class="messages-list">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="message" id="message-<?php echo $row['id']; ?>">
                        <p class="message-text" id="message-text-<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['message']); ?></p>
                        <small class="message-time"><?php echo $row['created_at']; ?></small>

                        <!-- Edit Button -->
                        <button class="edit-button" onclick="editMessage(<?php echo $row['id']; ?>)">Edit</button>
                        
                        <!-- Delete Button -->
                        <form method="POST" action="" style="display:inline;">
                            <input type="hidden" name="delete_id" value="<?php echo $row['id']; ?>">
                            <input type="submit" value="Delete" class="delete-button" onclick="return confirm('Are you sure you want to delete this message?');">
                        </form>
                    </div>

                    <!-- Edit Message Form (hidden initially) -->
                    <div class="edit-form" id="edit-form-<?php echo $row['id']; ?>" style="display: none;">
                        <textarea id="edit-message-<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['message']); ?></textarea><br>
                        <button onclick="updateMessage(<?php echo $row['id']; ?>)">Update</button>
                        <button onclick="cancelEdit(<?php echo $row['id']; ?>)">Cancel</button>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p>No messages found.</p>
        <?php endif; ?>
    </div>

    <script>
        // Show edit form when 'Edit' button is clicked
        function editMessage(id) {
            document.getElementById('message-text-' + id).style.display = 'none';
            document.getElementById('edit-form-' + id).style.display = 'block';
        }

        // Cancel the editing and revert to the original message
        function cancelEdit(id) {
            document.getElementById('edit-form-' + id).style.display = 'none';
            document.getElementById('message-text-' + id).style.display = 'block';
        }

        // Update message via AJAX
        function updateMessage(id) {
            var updatedMessage = document.getElementById('edit-message-' + id).value;

            // Send the updated message using AJAX
            $.ajax({
                url: '',  // The current page
                type: 'POST',
                data: { id: id, message: updatedMessage },
                success: function(response) {
                    // If update is successful, update the message on the page without reloading
                    if(response === 'success') {
                        document.getElementById('message-text-' + id).innerHTML = updatedMessage;
                        cancelEdit(id);
                    } else {
                        alert('Error updating message');
                    }
                }
            });
        }
    </script>
    <style>/* Add this to your existing CSS file */

header nav {
    text-align: center;  /* This will center the contents of the navigation (like the button) */
    margin-top: 20px;     /* Optional: Adds some space at the top */
}

.btns {
    display: inline-block;
    background-color: #4CAF50; /* Green background color */
    color: white; /* White text color */
    padding: 12px 25px; /* Vertical and horizontal padding */
    font-size: 16px;
    text-align: center;
    text-decoration: none; /* Remove underline from the link */
    border-radius: 8px; /* Rounded corners */
    border: 2px solid #4CAF50; /* Border color matching the background */
    transition: all 0.3s ease; /* Smooth transition effect */
    cursor: pointer;
    margin: 0 auto;  /* Horizontally center the button */
}

.btns:hover {
    background-color: #45a049; /* Darker green background on hover */
    border-color: #45a049; /* Darker border color on hover */
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2); /* Shadow effect on hover */
}

.btns:focus {
    outline: none; /* Remove default outline on focus */
}

.btns:active {
    background-color: #388e3c; /* Even darker green when button is clicked */
    border-color: #388e3c; /* Darker border when clicked */
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2); /* Smaller shadow when clicked */
}
</style>





<script>
        // JavaScript function to show the update form when "Update" button is clicked
        function toggleUpdateForm(videoId) {
            var form = document.getElementById("update-form-" + videoId);
            form.style.display = "block";  // Show the form
        }
    </script>
</head>
<body>
    <a href="#upload-section">UPLOAD</a>
    <div id="upload-section">
        <form action="" method="post" enctype="multipart/form-data">
            <input type="file" name="video_file" required>
            <input type="submit" name="upload" value="Upload">
        </form>
    </div>

    <?php if (isset($error_message)) { ?>
        <p style="color: red;"><?= $error_message ?></p>
    <?php } ?>

    <div class="gallery">
        <?php
        if (mysqli_num_rows($videos_result) > 0) {
            while ($video_record = mysqli_fetch_assoc($videos_result)) {
        ?>
                <div class="video-container">
                    <video src="uploads/<?= $video_record['video_url'] ?>" controls></video>
                    <div>
                        <!-- Trigger the update form display with JS -->
                        <a href="javascript:void(0);" class="update-button" onclick="toggleUpdateForm(<?= $video_record['id'] ?>)">Update</a>
                        <form action="" method="POST" style="display: inline;">
                            <input type="hidden" name="video_id" value="<?= $video_record['id'] ?>">
                            <input type="hidden" name="delete_video" value="1">
                            <button type="submit">Delete</button>
                        </form>
                    </div>
                </div>

                <!-- Update Form (hidden by default) -->
                <div id="update-form-<?= $video_record['id'] ?>" style="display:none;">
                    <h2>Update Video</h2>
                    <form action="" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="video_id" value="<?= $video_record['id'] ?>">
                        <input type="file" name="new_video" required>
                        <input type="submit" value="Update Video">
                    </form>
                </div>

        <?php
            }
        } else {
            echo "<h1>No videos available.</h1>";
        }
        ?>
    </div>



    <!-- Image Upload Form -->
    <h2>Upload Image</h2>
    <form action="" method="post" autocomplete="off" enctype="multipart/form-data">
        <label for="name">Name: </label>
        <input type="text" name="name" required><br>
        <label for="image">Image: </label>
        <input type="file" name="image" accept=".jpg, .jpeg, .png"><br><br>
        <button type="submit" name="submit">Submit</button>
    </form>

    <hr>

    <!-- Displaying Uploaded Images -->
    <h2>Uploaded Images</h2>
    <table border="1" cellspacing="0" cellpadding="10">
        <tr>
            <td>#</td>
            <td>Name</td>
            <td>Image</td>
            <td>Actions</td>
        </tr>
        <?php
        $i = 1;
        while ($row = mysqli_fetch_assoc($rows)) : ?>
            <tr>
                <td><?php echo $i++; ?></td>
                <td><?php echo $row["name"]; ?></td>
                <td><img src="img/<?php echo $row["image"]; ?>" width="200" title="<?php echo $row['image']; ?>"></td>
                <td>
                    <a href="?delete=<?php echo $row['id']; ?>" onclick="return confirm('Are you sure you want to delete this item?');">Delete</a>
                    <a href="?edit=<?php echo $row['id']; ?>">Edit</a>
                </td>
            </tr>
        <?php endwhile; ?>
    </table>






    <div class="container mt-5">
    <h2>Upload a file</h2>
    <form action="" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="uploaded_file" class="form-label">Select file</label>
            <input type="file" class="form-control" name="uploaded_file" id="uploaded_file">
        </div>
        <button type="submit" class="btn btn-primary">Upload file</button>
    </form>
</div>

<div class="container mt-5">
    <h2>Uploaded Files</h2>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>File Name</th>
                <th>File Size</th>
                <th>File Type</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php
            if ($file_results->num_rows > 0) {
                while ($row = $file_results->fetch_assoc()) {
                    $file_path = "uploads/" . $row['filename'];
                    ?>
                    <tr>
                        <td><?php echo $row['filename']; ?></td>
                        <td><?php echo $row['filesize']; ?> bytes</td>
                        <td><?php echo $row['filetype']; ?></td>
                        <td>
                            <a href="<?php echo $file_path; ?>" class="btn btn-primary" download>Download</a>
                            <?php if ($row['filetype'] == 'application/pdf'): ?>
                                <a href="<?php echo $file_path; ?>" class="btn btn-secondary" target="_blank">View PDF</a>
                            <?php endif; ?>
                            <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#updateModal<?php echo $row['id']; ?>">Update</button>
                            <a href="?remove=<?php echo $row['id']; ?>" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this file?');">Delete</a>
                        </td>
                    </tr>

                    <!-- Update Modal -->
                    <div class="modal fade" id="updateModal<?php echo $row['id']; ?>" tabindex="-1" aria-labelledby="updateModalLabel<?php echo $row['id']; ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="updateModalLabel<?php echo $row['id']; ?>">Update File Details</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <form method="POST" action="" enctype="multipart/form-data">
                                        <input type="hidden" name="file_id" value="<?php echo $row['id']; ?>">
                                        <div class="mb-3">
                                            <label for="filename" class="form-label">File Name</label>
                                            <input type="text" class="form-control" id="filename" name="filename" value="<?php echo $row['filename']; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="filetype" class="form-label">File Type</label>
                                            <input type="text" class="form-control" id="filetype" name="filetype" value="<?php echo $row['filetype']; ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label for="uploaded_file" class="form-label">Upload New File</label>
                                            <input type="file" class="form-control" id="uploaded_file" name="uploaded_file">
                                        </div>
                                        <button type="submit" name="modify" class="btn btn-success">Update</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php
                }
            } else {
                ?>
                <tr>
                    <td colspan="4">No files uploaded yet.</td>
                </tr>
                <?php
            }
            ?>
        </tbody>
    </table>
</div>







</body>
</html>





<?php
// Close the database connection
$conn->close();
?>
