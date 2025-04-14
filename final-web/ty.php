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

// Initialize search query
$searchQuery = "";

// Check if search form is submitted
if (isset($_POST['search'])) {
    $searchQuery = $conn->real_escape_string($_POST['search']); // Sanitize search input
}

// Handle comment submission
if (isset($_POST['comment']) && isset($_POST['post_id']) && isset($_POST['user_name'])) {
    $comment = $conn->real_escape_string($_POST['comment']);
    $post_id = $_POST['post_id'];
    $user_name = $conn->real_escape_string($_POST['user_name']); // Get the user name
    $sql_comment = "INSERT INTO comments (post_id, comment, user_name) VALUES ('$post_id', '$comment', '$user_name')";
    $conn->query($sql_comment);
}

// Handle like submission
if (isset($_POST['like']) && isset($_POST['post_id_like'])) {
    $post_id_like = $_POST['post_id_like'];
    $user_ip = $_SERVER['REMOTE_ADDR']; // Use the user's IP to avoid multiple likes
    $sql_like = "INSERT INTO likes (post_id, user_ip) VALUES ('$post_id_like', '$user_ip')";
    $conn->query($sql_like);
}

// Modify SQL to include search query for all columns
$sql = "SELECT id, message, image, video, file, created_at FROM media_messages WHERE 
        message LIKE '%$searchQuery%' OR 
        image LIKE '%$searchQuery%' OR 
        video LIKE '%$searchQuery%' OR 
        file LIKE '%$searchQuery%' OR 
        created_at LIKE '%$searchQuery%' 
        ORDER BY created_at DESC";

$result = $conn->query($sql);

// Capture the post_id from the URL if it's present (from the shared link)
$sharedPostId = isset($_GET['post_id']) ? $_GET['post_id'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>All Media Messages</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            background-color: rgb(107, 130, 236);
            color: #333;
        }
        h1 {
            text-align: center;
            margin-top: 40px;
            font-size: 36px;
            color: #007bff;
        }
        .media-item {
            background-color: rgb(185, 172, 232);
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
        }
        /* Highlight shared post */
        .shared-post {
            background-color: rgba(255, 223, 186, 0.7);
            border: 2px solid #f39c12;
        }
        .media-actions {
            margin-top: 20px;
            text-align: center;
        }
        .media-actions a {
            background: none;
            text-decoration: none;
            font-size: 18px;
            margin: 0 15px;
            padding: 10px;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .media-actions a:hover {
            opacity: 0.8;
        }
        .comment-form {
            text-align: center;
            margin-top: 15px;
        }
        .comment-form input[type="text"] {
            padding: 8px 15px;
            font-size: 16px;
            border: 1px solid #ddd;
            border-radius: 4px;
            width: 250px;
            margin-right: 10px;
        }
        .comment-form button {
            padding: 8px 15px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }
        .comment-form button:hover {
            background-color: #0056b3;
        }
        .comment-list {
            margin-top: 15px;
            list-style-type: none;
            padding-left: 0;
            display: none; /* Initially hidden */
        }
        .comment-list li {
            background-color: #e6f7ff; /* Light blue background */
            padding: 15px; /* Adds space inside the comment box */
            margin-bottom: 15px; /* Space between comments */
            border-radius: 8px; /* Rounded corners */
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); /* Subtle shadow around the comment box */
            transition: background-color 0.3s ease; /* Smooth transition for hover effect */
            margin-left: 300px; /* Indent the comment box */
            margin-right: 300px; /* Indent the comment box */
            text-align: left; /* Align text to the left */
        }

        .comment-list li:hover {
            background-color: #f0f8ff; /* Lighter blue when hovered */
        }

        .comment-author {
            font-size: 18px; /* Author's name font size */
            color: #007bff; /* Blue color for author's name */
            font-weight: bold; /* Make author's name bold */
        }

        .comment-text {
            font-size: 16px; /* Comment text font size */
            color: #555; /* Darker text color for better readability */
            margin-top: 8px; /* Adds space between author and comment text */
        }

        .comment-date {
            font-size: 14px; /* Smaller font size for the date */
            color: #aaa; /* Lighter gray color for the date */
            margin-top: 10px; /* Space between comment text and date */
            text-align: right; /* Align the date to the right */
        }

        .no-comments {
            font-size: 16px;
            color: #888; /* Light gray color */
            text-align: center; /* Center-align the message */
            padding: 20px; /* Adds padding around the "No comments" message */
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
    <h1 style="color: #ddd;">Welcome</h1>

    <!-- Search Form -->
    <form method="POST" style="text-align: center; margin-top: 30px; font-family: 'Arial', sans-serif;">
        <div style="display: flex; justify-content: center; align-items: center; gap: 10px;">
            <input type="text" name="search" placeholder="Search media messages..." value="<?php echo htmlspecialchars($searchQuery); ?>"
                style="padding: 10px 20px; font-size: 16px; width: 350px; border: 2px solid #ccc; border-radius: 25px; outline: none; 
                transition: all 0.3s ease; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">

            <!-- Search Icon Button -->
            <button type="submit" style="background-color: #007bff; border: none; padding: 10px 15px; border-radius: 50%; 
                cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.3s ease; display: flex; justify-content: center; align-items: center;">
                <i class="fa fa-search" style="font-size: 22px; color: white;"></i>
            </button>
            
            <!-- Clear Button -->
            <button type="button" id="clearSearch" style="background-color: #dc3545; border: none; padding: 10px 15px; border-radius: 50%; 
                cursor: pointer; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: all 0.3s ease; display: flex; justify-content: center; align-items: center;">
                <i class="fa fa-refresh" style="font-size: 22px; color: white;"></i>
            </button>

  <!-- Message Icon with link -->
  <a href="http://localhost/lgsg/connections.php" class="message-btn" style="background-color: #007bff; padding: 10px 15px; border-radius: 8px; display: flex; align-items: center; justify-content: center; cursor: pointer; text-decoration: none; margin-left: 10px;">
    <i class="fa fa-comments" style="color: white; font-size: 18px;"></i>
</a>

        </div>
    </form>

    <script>
        document.getElementById('clearSearch').addEventListener('click', function() {
            document.querySelector('[name="search"]').value = ''; // Clear the input field
            document.querySelector('form').submit(); // Optionally, submit the form to refresh the page
        });
    </script>

    <!-- Display Results -->
    <div class="media-list" style="margin: 20px auto; max-width: 1000px;">
        <h2 style="font-size: 36px; text-align: center; color: #333;">Blog</h2>

        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $id = $row['id'];
                $message = $row['message'];
                $image = $row['image'];
                $video = $row['video'];
                $file = $row['file'];

                // Get like count for the post
                $sql_like_count = "SELECT COUNT(*) AS like_count FROM likes WHERE post_id = '$id'";
                $result_like_count = $conn->query($sql_like_count);
                $like_count = 0;
                if ($result_like_count->num_rows > 0) {
                    $like_count = $result_like_count->fetch_assoc()['like_count'];
                }

                // Get comment count for the post
                $sql_comment_count = "SELECT COUNT(*) AS comment_count FROM comments WHERE post_id = '$id'";
                $result_comment_count = $conn->query($sql_comment_count);
                $comment_count = 0;
                if ($result_comment_count->num_rows > 0) {
                    $comment_count = $result_comment_count->fetch_assoc()['comment_count'];
                }

                // Add the 'shared-post' class if the post is the one being shared
                $isSharedPost = ($id == $sharedPostId) ? 'shared-post' : '';

                echo "<div class='media-item $isSharedPost'>";
                echo "<p style='font-size: 18px; line-height: 1.6; color: #444;'>". htmlspecialchars_decode($message) . "</p>";

                // Image: Set a consistent size and styling
                if ($image) {
                    echo "<img src='$image' alt='Media Image' style='max-width: 100%; height: 400px; object-fit: cover; border-radius: 8px; margin-top: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); display: block; margin: 10px auto;'>";
                }

                // Video: Set a consistent size for the video player
                if ($video) {
                    echo "<video controls style='max-width: 100%; height: 400px; border-radius: 8px; margin-top: 10px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1); display: block; margin: 10px auto;'>
                            <source src='$video' type='video/mp4'>
                          </video>";
                }

                // PDF: Display PDFs inside an iframe with a fixed size
                if ($file) {
                    echo "<iframe src='$file' style='width: 100%; height: 600px; border: none; margin-top: 10px; border-radius: 8px;'></iframe>";
                }

                // Display Like/Comment Buttons with Like count and Comment count
                echo "<div class='media-actions'>";
                echo "<form method='POST' style='display:inline;'>
                        <button type='submit' name='like' value='1' style='background-color: transparent; border: none; cursor: pointer; font-size: 24px; color: blue; text-align: left;'>
                            <i class='fa fa-thumbs-up'></i> Like ($like_count)
                        </button>
                        <input type='hidden' name='post_id_like' value='$id'>
                      </form>";


                             // Display Share Button
                echo "<a href='http://localhost/lgsg/ty.php?post_id=$id' target='_blank' style='color: #007bff; font-size: 18px;'>
                <i class='fa fa-share-alt' style='font-size: 24px;'></i> 
               </a>";

                // Comment Form: Initially hidden
                echo "<form method='POST' class='comment-form' style='display: block; width: 60%; margin: 0 auto; text-align: center;'>
                <input type='hidden' name='post_id' value='$id'>
                <!-- User name and comment input fields initially hidden -->
                <input type='text' name='user_name' placeholder='Your Name' required class='comment-user-name' style='width: 100%; padding: 10px; font-size: 16px; margin: 10px 0; display:none;'>
                <input type='text' name='comment' placeholder='Add a comment' required class='comment-text' style='width: 100%; padding: 10px; font-size: 16px; margin: 10px 0; display:none;'>
                
                <!-- Comment icon button, visible initially -->
                <button type='button' class='comment-icon' style='background-color: transparent; border: none; cursor: pointer; padding: 10px 20px; font-size: 16px;'>
                    <i class='fa fa-comment' style='font-size: 24px; color:rgb(241, 13, 13);'></i> Comment ($comment_count)
                </button>

                <!-- Submit button, hidden initially -->
                <button type='submit' style='display:none;' class='submit-comment'>Submit</button>
            </form>";


         

                // Display comments list: Initially hidden
                echo "<ul class='comment-list'>";
                $sql_comments = "SELECT * FROM comments WHERE post_id = '$id' ORDER BY created_at DESC";
                $result_comments = $conn->query($sql_comments);
                if ($result_comments->num_rows > 0) {
                    while ($comment = $result_comments->fetch_assoc()) {
                        echo "<li><strong>" . htmlspecialchars($comment['user_name']) . "</strong>: " . htmlspecialchars($comment['comment']) . "</li>";
                    }
                }

                echo "</ul>";
                echo "</div></div>";
            }
        } else {
            echo "<p>No media messages found.</p>";
        }
        ?>

    </div>


    <script>
        // Get all the comment icons
        const commentIcons = document.querySelectorAll('.comment-icon');

        commentIcons.forEach(icon => {
            icon.addEventListener('click', function() {
                const commentForm = this.closest('form'); // Get the parent form of the icon
                const userNameField = commentForm.querySelector('.comment-user-name');
                const commentField = commentForm.querySelector('.comment-text');
                const submitButton = commentForm.querySelector('.submit-comment');
                const commentList = this.closest('.media-item').querySelector('.comment-list'); // Get the comment list for this post
                
                // Toggle visibility of the comment input fields and comment list
                const isVisible = userNameField.style.display === 'block' && commentField.style.display === 'block';
                
                if (isVisible) {
                    // If input fields and comments are visible, hide them
                    userNameField.style.display = 'none';
                    commentField.style.display = 'none';
                    submitButton.style.display = 'none';
                    commentList.style.display = 'none'; // Hide the comment list as well
                } else {
                    // If they are not visible, show them
                    userNameField.style.display = 'block';
                    commentField.style.display = 'block';
                    submitButton.style.display = 'inline-block'; // Show submit button
                    commentList.style.display = 'block'; // Show the existing comments list
                }
            });
        });
    </script>


</body>
</html>

<?php
$conn->close();
?>
