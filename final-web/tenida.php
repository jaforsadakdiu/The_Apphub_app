<?php
// Start session
session_start();

// Database connection details
$host = 'localhost';
$dbname = 'user_posts_db';
$username = 'root';
$password = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}

// Handle user registration
if (isset($_POST['register'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Check if username exists
    $stmt = $pdo->prepare("SELECT * FROM members WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->rowCount() > 0) {
        echo "Username already taken.";
    } else {
        // Insert user into the 'members' table
        $stmt = $pdo->prepare("INSERT INTO members (username, password) VALUES (?, ?)");
        $stmt->execute([$username, $password]);

        echo "Registration successful. You can now log in.";
    }
}

// Handle user login
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $pdo->prepare("SELECT * FROM members WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        header('Location: ' . $_SERVER['PHP_SELF']);
        exit;
    } else {
        echo "Invalid credentials.";
    }
}

// Handle post submission (message, image, video, PDF, DOC)
if (isset($_POST['publish']) && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $content = $_POST['content'];  // This will contain the HTML from Quill
    $file = $_FILES['file'];

    $filePath = '';
    if ($file['error'] === 0) {
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $fileDestination = 'uploads/' . uniqid('', true) . '.' . $fileExtension;

        // Check for allowed file types
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'pdf', 'doc', 'docx'];
        if (in_array(strtolower($fileExtension), $allowedExtensions)) {
            move_uploaded_file($fileTmpName, $fileDestination);
            $filePath = $fileDestination;
        } else {
            echo "File type not allowed.";
            exit;
        }
    }

    // Insert post into the 'user_posts' table with formatted HTML content
    $stmt = $pdo->prepare("INSERT INTO user_posts (member_id, content, file_path) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $content, $filePath]);

    echo "Post published successfully.";
}

// Handle post update
if (isset($_POST['edit_post']) && isset($_SESSION['user_id'])) {
    $post_id = $_POST['post_id'];
    $content = $_POST['content'];
    $file = $_FILES['file'];

    $filePath = '';
    if ($file['error'] === 0) {
        $fileName = $file['name'];
        $fileTmpName = $file['tmp_name'];
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $fileDestination = 'uploads/' . uniqid('', true) . '.' . $fileExtension;

        // Check for allowed file types
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'pdf', 'doc', 'docx'];
        if (in_array(strtolower($fileExtension), $allowedExtensions)) {
            move_uploaded_file($fileTmpName, $fileDestination);
            $filePath = $fileDestination;
        } else {
            echo "File type not allowed.";
            exit;
        }
    }

    // Update post in the 'user_posts' table
    $stmt = $pdo->prepare("UPDATE user_posts SET content = ?, file_path = ? WHERE id = ? AND member_id = ?");
    $stmt->execute([$content, $filePath, $post_id, $_SESSION['user_id']]);

    echo "Post updated successfully.";
}

// Handle post deletion
if (isset($_GET['delete_post'])) {
    $post_id = $_GET['delete_post'];

    // Fetch the post from the database to ensure the current user owns it
    $stmt = $pdo->prepare("SELECT * FROM user_posts WHERE id = ?");
    $stmt->execute([$post_id]);
    $post = $stmt->fetch();

    if ($post && $post['member_id'] == $_SESSION['user_id']) {
        // Delete the file if it exists
        if ($post['file_path']) {
            unlink($post['file_path']);
        }

        // Delete the post from the database
        $stmt = $pdo->prepare("DELETE FROM user_posts WHERE id = ?");
        $stmt->execute([$post_id]);

        echo "Post deleted successfully.";
    } else {
        echo "You do not have permission to delete this post.";
    }
}

// Handle logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Query posts (search by keyword or filter)
$posts = [];
$searchQuery = '';
if (isset($_POST['search'])) {
    $searchQuery = $_POST['search_query'];
    // Search posts by content (this can be expanded for more filters)
    $stmt = $pdo->prepare("SELECT * FROM user_posts WHERE content LIKE ? ORDER BY created_at DESC");
    $stmt->execute(['%' . $searchQuery . '%']);
    $posts = $stmt->fetchAll();
} else {
    // Fetch user's posts
    if (isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
        // Order posts by created_at in descending order to get the most recent first
        $stmt = $pdo->prepare("SELECT * FROM user_posts WHERE member_id = ? ORDER BY created_at DESC");
        $stmt->execute([$user_id]);
        $posts = $stmt->fetchAll();
    }
}

?>




<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login / Sign Up</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color:rgb(173, 206, 234);
            margin: 0;
            padding: 0;
            transition: background-color 0.3s ease;
        }
        .container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            max-width: 1200px;
        }
        header {
            text-align: center;
            margin: 20px 0;
        }
        h2 {
            color: #333;
            transition: color 0.3s ease;
        }
        form {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-bottom: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        form:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.15);
        }
        input[type="text"], input[type="password"], textarea, input[type="file"] {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
            transition: border-color 0.3s ease;
        }
        input[type="text"]:focus, input[type="password"]:focus, textarea:focus {
            border-color: #4CAF50;
        }
        button {
            background-color: #4CAF50;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        button:hover {
            background-color: #45a049;
        }
        a {
            color: #4CAF50;
            text-decoration: none;
            margin: 5px;
            transition: color 0.3s ease;
        }
        a:hover {
            color: #45a049;
            text-decoration: underline;
        }
        .logout-btn {
            position: absolute;
            top: 20px;
            right: 20px;
            background-color: #f44336;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        .logout-btn:hover {
            background-color: #e53935;
        }
        .post {
            background-color: #fff;
            padding: 15px;
            margin-bottom: 15px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .post:hover {
            transform: scale(1.02);
            box-shadow: 0 6px 15px rgba(0, 0, 0, 0.1);
        }
        .post img, .post video {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin-top: 10px;
        }
        .file-link {
            display: block;
            margin-top: 10px;
            color: #007BFF;
            transition: color 0.3s ease;
        }
        .file-link:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        .post-actions {
            margin-top: 10px;
        }
        .post-actions a {
            margin-right: 15px;
            transition: color 0.3s ease;
        }
        .post-actions a:hover {
            color: #4CAF50;
        }
        .no-posts {
            text-align: center;
            color: #777;
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">


    <!-- Include Quill.js -->
<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<script src="https://cdn.quilljs.com/1.3.6/quill.min.js">


 

</script>


</head>
<body>



<div class="container" style="width: 80%; margin: 0 auto; padding: 20px;"> 

    <?php if (!isset($_SESSION['user_id'])): ?> 
        <!-- Login Form --> 
        <header> 
        <!-- <h2 style="text-align: center;">Welcome</h2> -->
        </header> 

        <div class="form-container" style=" text-align: center; display: flex; align-items: center; gap: 20px; width: 95%; height:90vh;">
        <!-- Login Form -->
        <div id="login-form" style="flex: 1; max-width: 300px; margin: 0 auto; display: block;">
        <form method="post" style="display: flex; flex-direction: column; justify-content: center; align-items: center; width: 100%; max-width: 400px; margin: 0 auto; padding: 20px;">
    <h3 style="text-align: center;">Login</h3>
    <input type="text" name="username" placeholder="Username" required style="width: 100%; padding: 10px; margin: 5px 0;">
    <input type="password" name="password" placeholder="Password" required style="width: 100%; padding: 10px; margin: 5px 0;">
    <button type="submit" name="login" style="width: 100%; padding: 10px; margin-top: 10px;">Login</button>
</form>

            <div style="text-align: center; margin-top: 10px;">
                <p>Don't have an account? <a href="javascript:void(0);" onclick="showRegisterForm()">Create one</a></p>
            </div>
        </div>

        <!-- Register Form -->
        <div id="register-form" style="flex: 1; max-width: 325px; width: 95%;margin: 0 auto; display: none; top: 0;">
            <form method="post">
                <h3 style="text-align: center;">Sign Up</h3>
                <input type="text" name="username" placeholder="Username" required style="width: 100%; padding: 10px; margin: 5px 0;">
                <input type="password" name="password" placeholder="Password" required style="width: 100%; padding: 10px; margin: 5px 0;">
                <button type="submit" name="register" style="width: 100%; padding: 10px; margin-top: 10px;">Register</button>
            </form>
            <div style="text-align: center; margin-top: 10px;">
                <p>Already have an account? <a href="javascript:void(0);" onclick="showLoginForm()">Log in</a></p>
            </div>
        </div>
    </div>

    <script>
        function showRegisterForm() {
            document.getElementById('login-form').style.display = 'none';
            document.getElementById('register-form').style.display = 'block';
        }

        function showLoginForm() {
            document.getElementById('login-form').style.display = 'block';
            document.getElementById('register-form').style.display = 'none';
        }
    </script>

    <?php else: ?> 
        <!-- Logged-in user's dashboard --> 
        <header> 
            <h2 style="text-align: center;">Welcome <?php echo $_SESSION['username']; ?></h2> 
        </header> 

    <!-- Logout Icon using Font Awesome Door-Open Icon -->
<div style="position: absolute; top: 20px; right: 20px; margin: 0;">
    <a href="?logout" 
        style="font-size: 30px; color: #007bff; text-decoration: none; display: inline-block; padding: 10px; border-radius: 50%; background-color: #f1f1f1; box-shadow: 0px 4px 6px rgba(0,0,0,0.1); cursor: pointer; transition: transform 0.3s ease, color 0.3s ease, background-color 0.3s ease, box-shadow 0.3s ease;"
        onmouseover="this.style.transform='scale(1.1)'; this.style.color='#0056b3'; this.style.backgroundColor='#e9ecef'; this.style.boxShadow='0px 6px 12px rgba(0,0,0,0.2)';" 
        onmouseout="this.style.transform='scale(1)'; this.style.color='#007bff'; this.style.backgroundColor='#f1f1f1'; this.style.boxShadow='0px 4px 6px rgba(0,0,0,0.1)';">
        <i class="fas fa-door-open" aria-hidden="true"></i>
    </a>
</div>

<!-- Include Font Awesome (if not already included in your project) -->
<script src="https://kit.fontawesome.com/a076d05399.js"></script>




        

<br>
<br>

        <!-- Publish Post Form --> 
        <section> 
  <h3 style="text-align: center; font-family: Arial, sans-serif; border-radius: 20px; width: 60%; margin: 0 auto; font-weight :bold;">
    To Do list
  </h3> 
  <br>
  <br>

  <form method="post" enctype="multipart/form-data" style="border: 1px solid #ccc; padding: 10px; margin: 10px auto; text-align: center; font-family: Arial, sans-serif; border-radius: 20px; width: 70%; font-size: 14px;">
    <!-- Quill Editor Container -->
    <div id="editor" style="width: 100%; height: 150px; border-radius: 20px; border: 1px solid #ddd; font-size: 16px;"></div><br>
    
    <!-- Hidden input field to hold the HTML content from the Quill editor -->
    <input type="hidden" name="content" id="content">

    <div style="display: flex; justify-content: center; gap: 20px;">
      <!-- Custom file input with icon -->
      <label for="file-upload" style="cursor: pointer; padding: 10px; font-size: 24px; color: #007bff;">
        <i class="fa fa-upload" aria-hidden="true"></i>
      </label>
      <input type="file" id="file-upload" name="file" accept=".jpg,.jpeg,.png,.gif,.mp4,.pdf,.doc,.docx" style="display: none;">
      
      <!-- Icon-only submission button -->
      <button type="submit" name="publish" style="background: none; border: none; cursor: pointer; padding: 0;">
        <i class="fa fa-paper-plane" aria-hidden="true" style="font-size: 30px; color: #007bff;"></i>
      </button> 
    </div>
  </form> 
</section>


<script>
  var quill = new Quill('#editor', {
    theme: 'snow',
    modules: {
      toolbar: [
        [{ 'header': '1' }, { 'header': '2' }, { 'font': [] }],
        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
        ['bold', 'italic', 'underline'],
        [{ 'align': [] }],
        ['link'],
        ['image']
      ]
    }
  });

  // Ensure the content from the editor is saved to the hidden input field before form submission
  document.querySelector('form').onsubmit = function() {
    var content = quill.root.innerHTML;  // Get the HTML content from Quill
    document.getElementById('content').value = content;  // Set the hidden input value to the HTML content
  };
</script>
<br>
<br>

<!-- Search form -->
<form method="post" action="" style="display: flex;margin-left:230px; justify-content: center; align-items: center; gap: 20px; width:700px; padding: 10px 10px; border-radius: 10px; background: linear-gradient(145deg, #6e7e9c, #3a4c6b); box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1); transition: all 0.3s ease;">
    <label for="search_query" style="font-size: 18px; font-weight: 600; color: #fff; text-shadow: 1px 1px 5px rgba(0, 0, 0, 0.2);"><center>Search</center></label>
    <input type="text" name="search_query" id="search_query" value="<?php echo htmlspecialchars($searchQuery); ?>" style="padding: 12px 18px; border-radius: 8px; border: 1px solid #ccc; font-size: 18px; width: 300px; outline: none; transition: all 0.3s ease;">
    
    <!-- Search icon button -->
    <button type="submit" name="search" style="border: none; background: #4CAF50; padding: 12px 20px; cursor: pointer; transition: all 0.3s ease; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);">
        <i class="fas fa-search" style="color: #fff; font-size: 24px;"></i>
    </button>
    
    <!-- Refresh icon button -->
    <button type="button" onclick="window.location.href = 'http://localhost/messagin/tenida.php';" style="border: none; background: #FF6347; padding: 12px 20px; cursor: pointer; transition: all 0.3s ease; border-radius: 8px; box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);">
        <i class="fas fa-sync-alt" style="color: #fff; font-size: 24px;"></i>
    </button>
</form>

<!-- Inline CSS for hover effects -->
<style>
    /* Form Hover Effect */
    form:hover {
        transform: scale(1.02); /* Slight zoom effect on form */
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.15); /* Deeper shadow on hover */
    }

    button:hover {
        transform: scale(1.1); /* Slight zoom effect on buttons */
        box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2); /* Increase shadow */
    }

    /* Search Button Hover */
    .fas.fa-search:hover {
        color: #fff; /* Keep icon white on hover */
        transform: rotate(360deg); /* Subtle icon rotation effect */
    }

    /* Refresh Button Hover */
    .fas.fa-sync-alt:hover {
        color: #fff; /* Keep icon white on hover */
        transform: rotate(360deg); /* Refresh icon spin */
    }

    /* Input Hover and Focus Effect */
    #search_query:hover, #search_query:focus {
        border-color: #4CAF50; /* Green border when focused or hovered */
        box-shadow: 0 0 8px rgba(76, 175, 80, 0.6); /* Green glow effect */
    }
</style>


<section> 
  <h3 style="text-align: center; margin: 0; display: flex; justify-content: center; align-items: center; font-family: 'Arial', sans-serif; color: #333; text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.2); font-size: 24px;">Schedule</h3>

  <?php if (count($posts) > 0): ?> 
    <?php foreach ($posts as $post): ?> 
      <div class="post" style="border: 1px solid rgb(16, 52, 229); padding: 20px; margin: 20px auto; text-align: center; width: 80%; display: flex; flex-direction: column; align-items: center; border-radius: 12px; box-shadow: 0 8px 15px rgba(0, 0, 0, 0.15); background: rgb(153, 153, 217);">
        
        <!-- Display formatted content with HTML -->
        <div style="text-align: left; width: 100%; padding: 10px; background-color: rgb(255, 255, 255); border-radius: 10px; box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1); margin-bottom: 20px; font-size: 18px; line-height: 1.6; display: flex; color: #555; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; word-wrap: break-word;">

          <?php echo $post['content']; ?>
        </div>

        <?php if ($post['file_path']): ?> 
          <?php 
          $fileExtension = pathinfo($post['file_path'], PATHINFO_EXTENSION);
          // Display image files
          if (in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif'])): ?>
            <img src="<?php echo $post['file_path']; ?>" alt="Post Image" style="width: 80%; max-width: 500px; height: auto; margin: 10px 0; border-radius: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);">
          <?php 
          // Display video files
          elseif (in_array(strtolower($fileExtension), ['mp4'])): ?>
            <video controls style="max-width: 80%; margin: 10px 0; border-radius: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);">
              <source src="<?php echo $post['file_path']; ?>" type="video/mp4">
              Your browser does not support the video tag.
            </video>
          <?php 
          // Display PDF files
          elseif (in_array(strtolower($fileExtension), ['pdf'])): ?>
            <iframe src="<?php echo $post['file_path']; ?>" width="80%" height="500px" style="margin: 10px 0; border-radius: 10px; box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);"></iframe>
          <?php 
          // Display DOC files (with download link)
          elseif (in_array(strtolower($fileExtension), ['doc', 'docx'])): ?>
            <a href="<?php echo $post['file_path']; ?>" class="file-link" download style="display: inline-block; margin: 10px 0; font-size: 16px; color: #fff; background-color: #9b59b6; padding: 10px 20px; border-radius: 5px; text-decoration: none; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);">
              Download DOC file
            </a>
          <?php endif; ?>
        <?php endif; ?>   
    

        <!-- Post ID and delete link -->
        <div>
           <!-- <strong>Post ID:</strong> <?php echo $post['id']; ?><br>
          -->
        </div>
        
        <!-- Edit and Delete buttons -->
        <div class="post-actions" style="margin-top: 10px; width: 100%; text-align: center;">
          <!-- Edit Button -->
          <a href="javascript:void(0);" onclick="toggleEditForm(<?php echo $post['id']; ?>);" 
            style="color: rgb(65, 105, 225); display: inline-block; padding: 5px 10px; border-radius: 5px; transition: transform 0.3s ease, color 0.3s ease, background-color 0.3s ease;" 
            onmouseover="this.style.transform='scale(1.1)'; this.style.color='white'; this.style.backgroundColor='rgb(65, 105, 225)';" 
            onmouseout="this.style.transform='scale(1)'; this.style.color='rgb(65, 105, 225)'; this.style.backgroundColor='transparent';">
            <i class="fa fa-edit" aria-hidden="true"></i>  
          </a>

          <!-- Delete Button -->
          <a href="?delete_post=<?php echo $post['id']; ?>" 
            onclick="return confirm('Are you sure you want to delete this post?');" 
            style="color: red; display: inline-block; padding: 5px 10px; border-radius: 5px; transition: transform 0.3s ease, color 0.3s ease, background-color 0.3s ease;" 
            onmouseover="this.style.transform='scale(1.1)'; this.style.color='white'; this.style.backgroundColor='red';" 
            onmouseout="this.style.transform='scale(1)'; this.style.color='red'; this.style.backgroundColor='transparent';">
            <i class="fa fa-trash" aria-hidden="true"></i>  
          </a>
        </div>

        <!-- Edit Post Form (only visible when toggled) -->
        <div id="editForm-<?php echo $post['id']; ?>" style="display: none; margin-top: 10px; width: 100%; text-align: center;">
            <section>
                <h3>Edit Task</h3>
                <form method="post" enctype="multipart/form-data" style="text-align: center;">
                    <!-- Quill Editor for Content -->
                    <div id="editor-<?php echo $post['id']; ?>" style="width: 100%; height: 150px; border-radius: 20px; border: 1px solid #ddd; font-size: 16px;"></div><br>

                    <!-- Hidden input field to hold the HTML content from the Quill editor -->
                    <input type="hidden" name="content" id="content-<?php echo $post['id']; ?>">

                    <!-- File upload -->
                    <input type="file" name="file" accept=".jpg,.jpeg,.png,.gif,.mp4,.pdf,.doc,.docx" style="margin: 10px 0;"><br><br>

                    <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                    <button type="submit" name="edit_post" style="padding: 10px 20px; font-size: 16px;">
                        <i class="fa fa-refresh" aria-hidden="true"></i> Update Post
                    </button>
                </form>
            </section>
        </div>

        <script>
            // Initialize Quill editor for the specific post
            var quill_<?php echo $post['id']; ?> = new Quill('#editor-<?php echo $post['id']; ?>', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        [{ 'header': '1' }, { 'header': '2' }, { 'font': [] }],
                        [{ 'list': 'ordered' }, { 'list': 'bullet' }],
                        ['bold', 'italic', 'underline'],
                        [{ 'align': [] }],
                        ['link'],
                        ['image']
                    ]
                }
            });

            // Set the current content of the post into the Quill editor
            quill_<?php echo $post['id']; ?>.root.innerHTML = <?php echo json_encode($post['content']); ?>;

            // Ensure the content from the editor is saved to the hidden input field before form submission
            document.querySelector('#editForm-<?php echo $post['id']; ?>').onsubmit = function() {
                var content = quill_<?php echo $post['id']; ?>.root.innerHTML;  // Get the HTML content from Quill
                document.getElementById('content-<?php echo $post['id']; ?>').value = content;  // Set the hidden input value to the HTML content
            };
        </script>
      </div>
    <?php endforeach; ?>
  <?php else: ?>
    <p class="no-posts" style="text-align: center;">No posts to show.</p>
  <?php endif; ?>
</section>

<!-- JavaScript to Toggle the Edit Form -->
<script>
function toggleEditForm(postId) {
    var form = document.getElementById('editForm-' + postId);
    var buttons = document.querySelectorAll('.post-actions');
    if (form.style.display === 'none' || form.style.display === '') {
        form.style.display = 'block';
    } else {
        form.style.display = 'none';
    }
}
</script>
    
        <?php endif; ?> 
</div>

</body>
</html>

