<?php
// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "media_uploads"; // Database name

$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle Insert Media (Message + Files)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $message = $_POST['message']; // Quill editor content (HTML)

    // File upload handling (image, video, file)
    $image_path = $video_path = $file_path = '';

    // Handle Image Upload
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        $image_name = basename($_FILES['image']['name']);
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_dir = 'uploads/images/';
        $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        $image_path = $image_dir . uniqid() . '.' . $image_extension;

        if (in_array($image_extension, ['jpg', 'jpeg', 'png'])) {
            move_uploaded_file($image_tmp_name, $image_path);
        } else {
            echo "Invalid image type!";
            exit();
        }
    }

    // Handle Video Upload
    if (isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
        $video_name = basename($_FILES['video']['name']);
        $video_tmp_name = $_FILES['video']['tmp_name'];
        $video_dir = 'uploads/videos/';
        $video_extension = strtolower(pathinfo($video_name, PATHINFO_EXTENSION));
        $video_path = $video_dir . uniqid() . '.' . $video_extension;

        if (in_array($video_extension, ['mp4', 'webm', 'avi', 'flv'])) {
            move_uploaded_file($video_tmp_name, $video_path);
        } else {
            echo "Invalid video type!";
            exit();
        }
    }

    // Handle File Upload (Documents, etc.)
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        $file_name = basename($_FILES['file']['name']);
        $file_tmp_name = $_FILES['file']['tmp_name'];
        $file_dir = 'uploads/files/';
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_path = $file_dir . uniqid() . '.' . $file_extension;

        if (in_array($file_extension, ['pdf', 'doc', 'docx', 'txt'])) {
            move_uploaded_file($file_tmp_name, $file_path);
        } else {
            echo "Invalid file type!";
            exit();
        }
    }

    // Insert data into the database
    $stmt = $conn->prepare("INSERT INTO media_messages (message, image, video, file, created_at) VALUES (?, ?, ?, ?, NOW())");
    $stmt->bind_param("ssss", $message, $image_path, $video_path, $file_path);

    if ($stmt->execute()) {
        echo "Data inserted successfully!";
    } else {
        echo "Error inserting data: " . $conn->error;
    }

    $stmt->close();
}

// Handle Update Media (Message + Files)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update'])) {
    $id = $_POST['id'];
    $message = $_POST['message']; // Quill editor content (HTML)

    // Fetch current data from the database
    $stmt = $conn->prepare("SELECT image, video, file FROM media_messages WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($current_image, $current_video, $current_file);
    $stmt->fetch();
    $stmt->close();

    // File upload handling (image, video, file)
    $image_path = $current_image;
    $video_path = $current_video;
    $file_path = $current_file;

    // Handle Image Update
    if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
        // Delete old image if exists
        if (file_exists($current_image)) {
            unlink($current_image);
        }

        $image_name = basename($_FILES['image']['name']);
        $image_tmp_name = $_FILES['image']['tmp_name'];
        $image_dir = 'uploads/images/';
        $image_extension = strtolower(pathinfo($image_name, PATHINFO_EXTENSION));
        $image_path = $image_dir . uniqid() . '.' . $image_extension;

        if (in_array($image_extension, ['jpg', 'jpeg', 'png'])) {
            move_uploaded_file($image_tmp_name, $image_path);
        } else {
            echo "Invalid image type!";
            exit();
        }
    }

    // Handle Video Update
    if (isset($_FILES['video']) && $_FILES['video']['error'] == 0) {
        // Delete old video if exists
        if (file_exists($current_video)) {
            unlink($current_video);
        }

        $video_name = basename($_FILES['video']['name']);
        $video_tmp_name = $_FILES['video']['tmp_name'];
        $video_dir = 'uploads/videos/';
        $video_extension = strtolower(pathinfo($video_name, PATHINFO_EXTENSION));
        $video_path = $video_dir . uniqid() . '.' . $video_extension;

        if (in_array($video_extension, ['mp4', 'webm', 'avi', 'flv'])) {
            move_uploaded_file($video_tmp_name, $video_path);
        } else {
            echo "Invalid video type!";
            exit();
        }
    }

    // Handle File Update
    if (isset($_FILES['file']) && $_FILES['file']['error'] == 0) {
        // Delete old file if exists
        if (file_exists($current_file)) {
            unlink($current_file);
        }

        $file_name = basename($_FILES['file']['name']);
        $file_tmp_name = $_FILES['file']['tmp_name'];
        $file_dir = 'uploads/files/';
        $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
        $file_path = $file_dir . uniqid() . '.' . $file_extension;

        if (in_array($file_extension, ['pdf', 'doc', 'docx', 'txt'])) {
            move_uploaded_file($file_tmp_name, $file_path);
        } else {
            echo "Invalid file type!";
            exit();
        }
    }

    // Update the database with the new data
    $stmt = $conn->prepare("UPDATE media_messages SET message = ?, image = ?, video = ?, file = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $message, $image_path, $video_path, $file_path, $id);

    if ($stmt->execute()) {
        echo "Data updated successfully!";
        // Redirect to e.php after a short delay
        header("Location: http://localhost/lgsg/e.php");
        exit(); // Always call exit() after header redirect to stop further script execution
    }
     else {
        echo "Error updating data: " . $conn->error;
    }

    $stmt->close();
}

// Handle Delete Media (Message + Files)
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Fetch current data from the database
    $stmt = $conn->prepare("SELECT image, video, file FROM media_messages WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($image, $video, $file);
    $stmt->fetch();
    $stmt->close();

    // Delete files from the server if they exist
    if (file_exists($image)) {
        unlink($image);
    }
    if (file_exists($video)) {
        unlink($video);
    }
    if (file_exists($file)) {
        unlink($file);
    }

    // Delete the record from the database
    $stmt = $conn->prepare("DELETE FROM media_messages WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        echo "Data deleted successfully!";
        header("Location: http://localhost/lgsg/e.php");
        exit();
    } else {
        echo "Error deleting data: " . $conn->error;
    }

    $stmt->close();
}

// Query Filters (search, show all, etc.)
$search_query = isset($_GET['search']) ? $_GET['search'] : '';
$query_condition = '';

if (!empty($search_query)) {
    $search_query = "%" . $conn->real_escape_string($search_query) . "%";
    $query_condition = "WHERE message LIKE ? OR image LIKE ? OR video LIKE ? OR file LIKE ?";
}

// Frontend (HTML part)

// Assuming the PHP section remains the same
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Media Upload</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color:rgb(112, 177, 243);
            color: #333;
            margin: 0;
            padding: 0;
        }

        .form-container {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin: 20px;
            padding: 20px;
            max-width: 800px;
            margin-left: auto;
            margin-right: auto;
        }

        .form-container h2 {
            color: #5e7f5e;
            font-size: 24px;
            text-align: center;
            margin-bottom: 20px;
        }

        input[type="file"], textarea {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        input[type="submit"] {
            background-color: #4CAF50;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            display: block;
            width: 100%;
            margin-top: 20px;
        }

        input[type="submit"]:hover {
            background-color: #45a049;
        }

        .media-list {
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin: 20px;
            padding: 20px;
        }

        .media-list h2 {
            color: #f39c12;
            text-align: center;
            margin-bottom: 20px;
        }


        .media-list img, .media-list video {
            max-width: 150px;
            margin: 5px;
            border-radius: 4px;
        }

        a {
            color: #3498db;
            text-decoration: none;
            margin-right: 10px;
        }

        a:hover {
            text-decoration: underline;
        }

        .editor-container {
            margin-bottom: 15px;
        }

        .editor-container .ql-toolbar {
            background-color: #f1f1f1;
        }

        .editor-container .ql-container {
            border: 1px solid #ccc;
            border-radius: 4px;
        }

        .editor-container .ql-editor {
            min-height: 150px;
        }
    </style>
    <!-- Quill Editor CSS -->
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <!-- Quill Editor JS -->
    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">
</head>
<body>

<?php
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];

    // Fetch the existing record
    $stmt = $conn->prepare("SELECT message, image, video, file FROM media_messages WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($message, $image, $video, $file);
    $stmt->fetch();
    $stmt->close();
?>

<!-- Edit Media Form -->
<div class="form-container" style="max-width: 800px; margin: 0 auto; padding: 20px; background-color: #e6f7ff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); font-family: 'Roboto', sans-serif;">
    <h2 style="text-align: center; color: #1e6fb8; font-size: 28px; font-weight: bold; margin-bottom: 20px;">Edit Media</h2>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?php echo $id; ?>">

        <!-- Quill Editor -->
        <div id="editor" class="editor-container" style="min-height: 250px; margin-bottom: 20px; border: 1px solid #b3d9ff; background-color: #ffffff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);"></div>
        <textarea name="message" style="display:none;"><?php echo htmlspecialchars($message); ?></textarea><br><br>

        <!-- Image Upload -->
        <div style="display: flex; align-items: center; margin-bottom: 20px;">
            <label for="image" style="font-size: 16px; color: #2a88d2; margin-right: 15px;">Image:</label>
            <input type="file" name="image" style="padding: 10px 12px; border-radius: 6px; border: 1px solid #b3d9ff; font-size: 14px; transition: all 0.3s ease;">
        </div>
        <?php if ($image) { ?>
            <img src="<?php echo $image; ?>" alt="Current Image" style="max-width: 100%; height: auto; border-radius: 10px; margin-bottom: 20px;"><br><br>
        <?php } ?>

        <!-- Video Upload -->
        <div style="display: flex; align-items: center; margin-bottom: 20px;">
            <label for="video" style="font-size: 16px; color: #2a88d2; margin-right: 15px;">Video:</label>
            <input type="file" name="video" style="padding: 10px 12px; border-radius: 6px; border: 1px solid #b3d9ff; font-size: 14px; transition: all 0.3s ease;">
        </div>
        <?php if ($video) { ?>
            <video controls style="max-width: 100%; height: auto; border-radius: 10px; margin-bottom: 20px;">
                <source src="<?php echo $video; ?>" type="video/mp4">
            </video><br><br>
        <?php } ?>

        <!-- File Upload -->
        <div style="display: flex; align-items: center; margin-bottom: 20px;">
            <label for="file" style="font-size: 16px; color: #2a88d2; margin-right: 15px;">File:</label>
            <input type="file" name="file" style="padding: 10px 12px; border-radius: 6px; border: 1px solid #b3d9ff; font-size: 14px; transition: all 0.3s ease;">
        </div>
        <?php if ($file) { ?>
            <a href="<?php echo $file; ?>" download style="color: #3399ff; text-decoration: none; font-size: 16px; font-weight: 600; transition: color 0.3s ease;">Current File</a><br><br>
        <?php } ?>

        <!-- Submit Button -->
        <button type="submit" name="update" style="background-color: transparent; color: #3399ff; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-size: 16px; display: flex; align-items: center; justify-content: center; gap: 8px; transition: background-color 0.3s ease, transform 0.2s; margin: 0 auto; display: block;">
    <i class="fas fa-save" style="font-size: 18px;"></i> 
</button>

    </form>
</div>

<script>
    var quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': '1' }, { 'header': '2' }, { 'font': [] }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                ['bold', 'italic', 'underline'],
                ['link'],
                [{ 'align': [] }],
                ['image', 'video']
            ]
        }
    });

    // Set the content of the editor with the current message
    quill.root.innerHTML = "<?php echo addslashes($message); ?>";

    // On form submission, update the hidden textarea with the Quill editor content
    document.querySelector('form').onsubmit = function() {
        var hiddenTextarea = document.querySelector('textarea[name="message"]');
        hiddenTextarea.value = quill.root.innerHTML;
    };
</script>

<?php
} else {
?>

<!-- Media Upload Form -->
<div class="form-container" style="max-width: 800px; margin: 0 auto; padding: 20px; background-color: #e6f7ff; border-radius: 12px; box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1); font-family: 'Roboto', sans-serif;">
    <h2 style="text-align: center; color: #1e6fb8; font-size: 28px; font-weight: bold; margin-bottom: 20px;">Upload Media</h2>
    <form method="POST" enctype="multipart/form-data">
        <!-- Quill Editor -->
        <div id="editor" class="editor-container" style="min-height: 250px; margin-bottom: 20px; border: 1px solid #b3d9ff; background-color: #ffffff; padding: 15px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);"></div>
        <textarea name="message" style="display:none;"></textarea><br><br>

        <!-- Image Upload -->
        <div style="display: flex; align-items: center; margin-bottom: 20px;">
            <label for="image" style="font-size: 16px; color: #2a88d2; margin-right: 15px;">Image:</label>
            <input type="file" name="image" style="padding: 10px 12px; border-radius: 6px; border: 1px solid #b3d9ff; font-size: 14px; transition: all 0.3s ease;">
        </div>

        <!-- Video Upload -->
        <div style="display: flex; align-items: center; margin-bottom: 20px;">
            <label for="video" style="font-size: 16px; color: #2a88d2; margin-right: 15px;">Video:</label>
            <input type="file" name="video" style="padding: 10px 12px; border-radius: 6px; border: 1px solid #b3d9ff; font-size: 14px; transition: all 0.3s ease;">
        </div>

        <!-- File Upload -->
        <div style="display: flex; align-items: center; margin-bottom: 20px;">
            <label for="file" style="font-size: 16px; color: #2a88d2; margin-right: 25px;">File:</label>
            <input type="file" name="file" style="padding: 10px 12px; border-radius: 6px; border: 1px solid #b3d9ff; font-size: 15px; transition: all 0.3s ease;">
        </div>

        <!-- Submit Button -->
        <div style="display: flex; justify-content: center; width: 100%; margin-top: 20px;">
    <button type="submit" name="submit" style="background-color:transparent; color:  #3399ff; border: none; padding: 12px 25px; border-radius: 8px; cursor: pointer; font-size: 16px; display: inline-flex; align-items: center; justify-content: center; gap: 8px; transition: background-color 0.3s ease, transform 0.2s;">
        <i class="fas fa-upload" style="font-size: 18px;"></i> 
    </button>
</div>

    </form>
</div>

<script>
    var quill = new Quill('#editor', {
        theme: 'snow',
        modules: {
            toolbar: [
                [{ 'header': '1' }, { 'header': '2' }, { 'font': [] }],
                [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                ['bold', 'italic', 'underline'],
                ['link'],
                [{ 'align': [] }],
                ['image', 'video']
            ]
        }
    });

    // On form submission, update the hidden textarea with the Quill editor content
    document.querySelector('form').onsubmit = function() {
        var hiddenTextarea = document.querySelector('textarea[name="message"]');
        hiddenTextarea.value = quill.root.innerHTML;
    };
</script>
<br>
<br>



<?php } ?>

<!-- Search Form -->
<div class="form-container" id="searchFormContainer" style="max-width: 500px; margin: 0 auto; padding: 20px; background-color: #f8f9fa; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);">
    <form method="GET" class="search-form" id="searchForm" style="display: flex; align-items: center;">
        <!-- Search input -->
        <input type="text" name="search" id="searchInput" placeholder="Search media" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>" style="flex: 1; padding: 10px; font-size: 16px; border: 1px solid #ddd; border-radius: 8px; margin-right: 10px;"/>

        <!-- Search button -->
        <button type="submit" class="search-btn" style="background-color: #007bff; border: none; padding: 10px 15px; border-radius: 8px; cursor: pointer; display: flex; align-items: center; justify-content: center;">
            <i class="fa fa-search" style="color: white; font-size: 18px;"></i>
        </button>

        <!-- Refresh Button with Icon -->
        <a href="http://localhost/lgsg/e.php" class="refresh-btn" style="background-color: #28a745; padding: 10px 15px; border-radius: 8px; margin-left: 10px; display: flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none;">
            <i class="fa fa-sync" style="color: white; font-size: 18px;"></i>
        </a>

  <!-- Message Icon with link -->
<a href="http://localhost/lgsg/see.php" class="message-btn" style="background-color: #007bff; padding: 10px 15px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin-left: 10px;">
    <i class="fa fa-comments" style="color: white; font-size: 18px;"></i>
</a>

    </form>
</div>

<!-- Font Awesome (You need to add the link to Font Awesome CDN in the <head> section of your HTML) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<!-- JavaScript to prevent page scroll on form submission -->
<script>
    // When the page loads, store the scroll position
    window.onload = function() {
        const previousScrollPosition = sessionStorage.getItem('scrollPosition');
        if (previousScrollPosition) {
            window.scrollTo(0, previousScrollPosition);
        }
    };

    // Before form submission, save the current scroll position
    document.getElementById('searchForm').addEventListener('submit', function () {
        sessionStorage.setItem('scrollPosition', window.scrollY);
    });
</script>




<br>
<br>

<!-- Display Existing Media -->
<div class="media-list" style="background-color: rgb(131, 112, 228); border-radius: 12px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1); padding: 30px; max-width: 900px; margin: auto; font-family: 'Arial', sans-serif;">
    <h2 style="color: #fff; font-size: 28px; text-align: center; margin-bottom: 30px; font-weight: 600; text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.2);">
        Blog
    </h2>
    
    <?php
    $stmt = $conn->prepare("SELECT id, message, image, video, file FROM media_messages $query_condition ORDER BY created_at DESC");
    if (!empty($search_query)) {
        $stmt->bind_param("ssss", $search_query, $search_query, $search_query, $search_query);
    }
    $stmt->execute();
    $stmt->bind_result($id, $message, $image, $video, $file);

    while ($stmt->fetch()) {
        echo "<div style='background-color: rgb(181, 150, 234); padding: 20px; margin-bottom: 30px; border-radius: 10px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.05); text-align: left-align;'>";  
        
        echo "<div style='font-size: 18px; line-height: 1.8; color: #444; margin-bottom: 20px;'>" . htmlspecialchars_decode($message) . "</div>";

        if ($image) {
            echo "<img src='$image' alt='Descriptive image text' style='max-width: 100%; height: 300px; border-radius: 8px; margin-top: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); display: block; margin-left: auto; margin-right: auto;'><br>";
        }
        
        
        if ($video) {
            echo "<video controls style='max-width: 80%; height: auto; border-radius: 8px; margin-top: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); display: block; margin-left: auto; margin-right: auto;'><source src='$video' type='video/mp4'></video><br>";  
        }
        
        if ($file) {
            echo "<div style='display: flex; justify-content: center; align-items: center; height: 100vh;'>
                    <iframe src='$file' style='width: 80%; height: 80%; border: none;' frameborder='0'></iframe>
                  </div><br>";
        }
        
        
        
        // Center the Edit and Delete buttons horizontally
        echo "<div style='margin-top: 20px; display: flex; justify-content: center; align-items: center;'>
                <a href='?delete=$id' style='background: none; text-decoration: none; color: #e74c3c; font-size: 18px; margin: 0 15px; transition: all 0.3s ease;' onclick='return confirm(\"Are you sure you want to delete?\")'>
                    <i class='fas fa-trash' style='font-size: 20px; vertical-align: middle;'></i> 
                </a>
                <a href='?edit=$id' style='background: none; text-decoration: none; color: #f39c12; font-size: 18px; transition: all 0.3s ease;'>
                    <i class='fas fa-edit' style='font-size: 20px; vertical-align: middle;'></i> 
                </a>
              </div>";

        echo "</div>";
    }  
    ?>
</div>


  
</div>

</body>
</html>

<?php
$conn->close();
?>
