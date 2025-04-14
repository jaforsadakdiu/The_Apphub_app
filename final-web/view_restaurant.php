<?php
// Connection variables
$servername = "localhost";
$username = "root"; // your database username
$password = ""; // your database password
$dbname = "chatapp"; // your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Start session for user authentication
session_start();

// Handle Sign Up
if (isset($_POST['signup'])) {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $email = $_POST['email'];

    // Check if username already exists
    $checkQuery = "SELECT * FROM usersss WHERE username = '$username' LIMIT 1";
    $result = $conn->query($checkQuery);

    if ($result->num_rows > 0) {
        echo "Username already exists!";
    } else {
        // Insert user into database
        $sql = "INSERT INTO usersss (username, password, email) VALUES ('$username', '$password', '$email')";
        if ($conn->query($sql) === TRUE) {
            echo "Sign-up successful! You can now sign in.";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}

// Handle Sign In
if (isset($_POST['signin'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Fetch user from database
    $sql = "SELECT * FROM usersss WHERE username = '$username' LIMIT 1";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        // Verify password
        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            header("Location: ".$_SERVER['PHP_SELF']);
            exit;
        } else {
            echo "Incorrect password!";
        }
    } else {
        echo "No user found with that username!";
    }
}

// Handle Log Out
if (isset($_POST['logout'])) {
    session_unset();
    session_destroy();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}

// Handle Message Sending with File Upload
if (isset($_POST['send_message']) && isset($_SESSION['user_id'])) {
    $message = $_POST['message'];
    $receiver_username = $_POST['receiver_username'] ?? ''; // Use null coalescing to avoid undefined index error
    $sender_id = $_SESSION['user_id'];
    $file_name = null;

    if ($receiver_username == '') {
        echo "Receiver username is required!";
    } else {
        // Handle file upload if present
        if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
            $upload_dir = 'uploads/';

            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $file_name = uniqid() . '_' . basename($_FILES['media']['name']);
            $file_path = $upload_dir . $file_name;

            // Check if the file is a valid image, video, audio, Word document, or PDF
            $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
            $valid_extensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3', 'pdf', 'docx', 'doc'];

            if (in_array(strtolower($file_ext), $valid_extensions)) {
                if (move_uploaded_file($_FILES['media']['tmp_name'], $file_path)) {
                    echo "File uploaded successfully.";
                } else {
                    echo "Error uploading file.";
                }
            } else {
                echo "Invalid file type.";
            }
        }

        // Fetch receiver user ID from username
        $receiverQuery = "SELECT id FROM usersss WHERE username = '$receiver_username' LIMIT 1";
        $receiverResult = $conn->query($receiverQuery);

        if ($receiverResult->num_rows > 0) {
            $receiver = $receiverResult->fetch_assoc();
            $receiver_id = $receiver['id'];

            // Insert message into database with file path (if file uploaded)
            $insertMessage = "INSERT INTO chat_messages (sender_id, receiver_id, message, file_path) 
                              VALUES ('$sender_id', '$receiver_id', '$message', '$file_name')";

            if ($conn->query($insertMessage) === TRUE) {
                echo "Message sent!";
            } else {
                echo "Error: " . $insertMessage . "<br>" . $conn->error;
            }
        } else {
            echo "Receiver not found!";
        }
    }
}

// Handle Deleting Message
if (isset($_POST['delete_message']) && isset($_POST['message_id']) && isset($_SESSION['user_id'])) {
    $message_id = $_POST['message_id'];

    // Fetch the message details to get the file path before deletion
    $messageQuery = "SELECT file_path FROM chat_messages WHERE id = '$message_id' AND sender_id = " . $_SESSION['user_id'];
    $messageResult = $conn->query($messageQuery);

    if ($messageResult->num_rows > 0) {
        $message = $messageResult->fetch_assoc();
        $file_path = $message['file_path'];

        // If a file exists, delete it
        if ($file_path && file_exists("uploads/$file_path")) {
            unlink("uploads/$file_path");
        }

        // Delete the message
        $deleteQuery = "DELETE FROM chat_messages WHERE id = '$message_id' AND sender_id = " . $_SESSION['user_id'];
        if ($conn->query($deleteQuery) === TRUE) {
            echo "Message deleted!";
        } else {
            echo "Error: " . $conn->error;
        }
    }
}

// Handle Updating Message
// Handle Updating Message
if (isset($_POST['update_message']) && isset($_POST['message_id']) && isset($_POST['new_message']) && isset($_SESSION['user_id'])) {
    $message_id = $_POST['message_id'];
    $new_message = $_POST['new_message'];
    $file_name = null;

    // Handle file upload if present
    if (isset($_FILES['media']) && $_FILES['media']['error'] == 0) {
        $upload_dir = 'uploads/';

        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }

        $file_name = uniqid() . '_' . basename($_FILES['media']['name']);
        $file_path = $upload_dir . $file_name;

        // Check if the file is a valid image, video, audio, Word document, or PDF
        $file_ext = pathinfo($file_name, PATHINFO_EXTENSION);
        $valid_extensions = ['jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3', 'pdf', 'docx', 'doc'];

        if (in_array(strtolower($file_ext), $valid_extensions)) {
            if (move_uploaded_file($_FILES['media']['tmp_name'], $file_path)) {
                echo "File uploaded successfully.";
            } else {
                echo "Error uploading file.";
            }
        } else {
            echo "Invalid file type.";
        }
    }

    // Update the message with the new message and file (if any)
    $updateQuery = "UPDATE chat_messages SET message = '$new_message', file_path = '$file_name' WHERE id = '$message_id' AND sender_id = " . $_SESSION['user_id'];
    if ($conn->query($updateQuery) === TRUE) {
        echo "Message updated!";
    } else {
        echo "Error: " . $conn->error;
    }
}


// Fetch users for the dropdown
$usersQuery = "SELECT username FROM usersss WHERE id != '" . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0) . "'";
$usersResult = $conn->query($usersQuery);

// Fetch messages between current user and selected user
$messagesResult = null;
if (isset($_SESSION['user_id']) && isset($_GET['receiver_username'])) {
    $user_id = $_SESSION['user_id'];
    $receiver_username = $_GET['receiver_username'];

    // Fetch the receiver's user ID
    $receiverQuery = "SELECT id FROM usersss WHERE username = '$receiver_username' LIMIT 1";
    $receiverResult = $conn->query($receiverQuery);
    if ($receiverResult->num_rows > 0) {
        $receiver = $receiverResult->fetch_assoc();
        $receiver_id = $receiver['id'];

        // Fetch the messages between the logged-in user and the selected user
        $messagesQuery = "SELECT m.id, m.message, m.timestamp, u.username AS sender, r.username AS receiver, m.file_path
                          FROM chat_messages m
                          JOIN usersss u ON m.sender_id = u.id
                          JOIN usersss r ON m.receiver_id = r.id
                          WHERE (m.sender_id = '$user_id' AND m.receiver_id = '$receiver_id') 
                          OR (m.sender_id = '$receiver_id' AND m.receiver_id = '$user_id')
                          ORDER BY m.timestamp DESC";
        $messagesResult = $conn->query($messagesQuery);
    }
}


// Fetch users based on query
$search_query = '';
if (isset($_POST['search']) && !empty($_POST['search'])) {
    $search_query = $conn->real_escape_string($_POST['search']); // Prevent SQL injection
    $usersQuery = "SELECT username FROM usersss WHERE id != '" . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0) . "' AND username LIKE '%$search_query%'"; 
} else {
    // Fetch all users if no search query
    $usersQuery = "SELECT username FROM usersss WHERE id != '" . (isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0) . "'";
}

$usersResult = $conn->query($usersQuery);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat Application</title>
    <script>
        function showUpdateForm(messageId) {
            var formId = "update_form_" + messageId;
            document.getElementById(formId).style.display = 'block';
        }
    </script>
     <style>
        /* Styles for the chat application */
        body {
            font-family: Arial, sans-serif;
            background-color:rgb(215, 202, 233);
            margin: 0;
            padding: 0;
            color: #333;
        }

        .container {
            max-width: 900px;
            margin: 0 auto;
            padding: 20px;
        }

        h2, h3 {
            color: #333;
            text-align: center;
            font-size: 28px;
        }

        .form-container {
            background-color:rgb(200, 231, 238);
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            margin-bottom: 30px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .form-container:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        .form-container input, .form-container textarea, .form-container button {
            padding: 12px;
            margin: 12px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
            transition: all 0.3s ease;
        }

        .form-container input:focus, .form-container textarea:focus {
            border-color: #4CAF50;
        }

        .form-container button {
            background-color: #4CAF50;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .form-container button:hover {
            background-color: #45a049;
            transform: scale(1.05);
        }

        .message {
            color:rgb(2, 17, 26);
            padding: 18px;
            margin-bottom: 20px;
            background-color:rgb(0, 0, 0);
            border-radius: 12px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .message:hover {
            background-color:#0fa55a;
            transform: translateY(-5px);
        }

        .message strong {
            display: block;
            margin-bottom: 10px;
        }

        .message-footer {
            font-size: 0.9em;
            color: #777;
            text-align: right;
        }

        .media-container {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 10px;
        }

        .media-container img {
            max-width: 100%;
            max-height: 400px;
            
        }

        .media-container video {
            max-width: 100%;
            max-height: 400px;
            
            color: #e0a800;
            background-color: aqua;
        }

        .media-container audio {
            width: 100%;
        }

        .update-button, .delete-button {
            padding: 10px 20px;
            cursor: pointer;
            border-radius: 8px;
            border: none;
            transition: all 0.3s ease;
        }

        .update-button {
            background-color: #ffc107;
        }

        .update-button:hover {
            background-color: #e0a800;
            transform: scale(1.05);
        }

        .delete-button {
            background-color: #f44336;
        }

        .delete-button:hover {
            background-color: #e02e1b;
            transform: scale(1.05);
        }

        .button-container {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .icon-container label {
            cursor: pointer;
            font-size: 28px;
            transition: transform 0.3s ease, color 0.3s ease;
        }

        .icon-container label:hover {
            transform: scale(1.1);
            color: #007bff;
        }

        .update-form-container {
            padding: 20px;
           
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            margin-top: 20px;
            display: none;
            transition: all 0.3s ease;
            
            color: #e0a800;
            background-color: aqua;
        }

        .update-form-container input, .update-form-container textarea, .update-form-container button {
            padding: 12px;
            margin: 12px 0;
            border-radius: 8px;
            border: 1px solid #ccc;
            transition: all 0.3s ease;
        }

        .update-form-container button {
            background-color: #007bff;
            color: white;
        }

        .update-form-container button:hover {
            background-color: #0056b3;
            transform: scale(1.05);
        }

        .container ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .container li {
            padding: 15px 20px;
            border-bottom: 1px solid #ddd;
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color:rgb(117, 138, 199);
            transition: background-color 0.3s ease, transform 0.3s ease;
        }

        .container li:hover {
            background-color:rgb(19, 17, 178);
            transform: translateX(5px);
        }

        .container li a {
            color: #007bff;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
        }

        .container li span {
            font-size: 14px;
            color: #888;
        }

        .message-history {
            margin-top: 40px;
        }
    
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="container">
    <?php if (!isset($_SESSION['user_id'])): ?>
        <div class="form-container" id="signup-form" style="background-color: white; padding: 50px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); width: 100%; max-width: 500px; text-align: center; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
    <h2 style="margin-bottom: 20px; font-size: 24px; color: #333;">Sign Up</h2>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required style="width: 100%; padding: 12px; margin: 0; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
        <input type="password" name="password" placeholder="Password" required style="width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
        <input type="email" name="email" placeholder="Email" required style="width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
        <button type="submit" name="signup" style="width: 100%; padding: 12px; background-color: #4CAF50; color: white; cursor: pointer; border: none; font-size: 16px; border-radius: 4px;">Sign Up</button>
    </form>
    <p style="margin-top: 15px;">Already have an account? <a href="javascript:void(0);" onclick="toggleForms()" style="color: #4CAF50; text-decoration: none; font-weight: bold;">Sign In</a></p>
</div>


<div class="form-container" id="signin-form" style="background-color: white; padding: 50px; border-radius: 8px; box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1); width: 100%; max-width: 500px; text-align: center; position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);">
    <h2 style="margin-bottom: 20px; font-size: 24px; color: #333;">Sign In</h2>
    <form method="POST">
        <input type="text" name="username" placeholder="Username" required style="width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
        <input type="password" name="password" placeholder="Password" required style="width: 100%; padding: 12px; margin: 10px 0; border: 1px solid #ddd; border-radius: 4px; font-size: 16px;">
        <button type="submit" name="signin" style="width: 100%; padding: 12px; background-color: #4CAF50; color: white; cursor: pointer; border: none; font-size: 16px; border-radius: 4px;">Sign In</button>
    </form>
    <p style="margin-top: 15px;">Don't have an account? <a href="javascript:void(0);" onclick="toggleForms()" style="color: #4CAF50; text-decoration: none; font-weight: bold;">Sign Up</a></p>
</div>


<script>
    function toggleForms() {
        var signupForm = document.getElementById('signup-form');
        var signinForm = document.getElementById('signin-form');
        
        // Toggle the visibility of the forms
        if (signupForm.style.display === "none") {
            signupForm.style.display = "block";
            signinForm.style.display = "none";
        } else {
            signupForm.style.display = "none";
            signinForm.style.display = "block";
        }
    }
</script>

    <?php else: ?>

        <div class="form-container" style="position: relative;color:hsla(269, 82.70%, 29.40%, 0.98); padding: 5px; background-color:hsla(246, 62.40%, 67.60%, 0.98); border-radius: 40px; box-shadow: 2000 200px 300px rgba(255, 255, 255, 0.1);">
    <h2 style="color: white;">Welcome   <?php echo $_SESSION['username']; ?>!</h2>
    <form method="POST" style="position: absolute; top: 20px; right: 20px;">
        <button type="submit" name="logout" style="background-color: #f44336; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; transition: background-color 0.3s, transform 0.3s;">
            Log Out
        </button>
    </form>
</div>

<script>
    // Adding hover effect to the button using inline CSS (for hover state)
    const logoutButton = document.querySelector('button[name="logout"]');
    
    logoutButton.addEventListener('mouseover', function() {
        this.style.backgroundColor = '#e02e1b'; // Darker red on hover
        this.style.transform = 'scale(1.05)'; // Slightly scale the button on hover
    });

    logoutButton.addEventListener('mouseout', function() {
        this.style.backgroundColor = '#f44336'; // Original red
        this.style.transform = 'scale(1)'; // Reset the scaling effect
    });
</script>









<div class="form-container" style="background: linear-gradient(135deg, #6e7fef, #7c89fc); padding: 20px; border-radius: 8px; box-shadow: 0 4px 8px rgba(2, 8, 63, 0.1);">
    <h3 style="text-align: center; color: white; font-size: 24px;">Users List</h3>
    
   <!-- Search form -->
<form method="POST" action="" style="text-align: center; margin-bottom: 20px;">
    <input type="text" name="search" placeholder="Search users" value="<?php echo htmlspecialchars($search_query); ?>" id="searchInput" style="padding: 8px; border-radius: 5px; border: 1px solid #ccc;">
    <button type="submit" style="padding: 8px 12px; background-color: #4CAF50; color: white; border: none; border-radius: 5px; cursor: pointer; margin-left: 10px;">Search</button>

    <!-- Refresh icon -->
    <i class="fas fa-sync-alt" style="cursor: pointer; margin-left: 10px; font-size: 20px;" onclick="clearSearch()"></i>
</form>

<!-- JavaScript for clearing the input and triggering search -->
<script>
    function clearSearch() {
        // Clear the search input field
        document.getElementById('searchInput').value = '';

        // Trigger the search form submit
        document.querySelector('form').submit();
    }
</script>

    <!-- User list -->
    <ul style="list-style: none; padding: 0; margin: 0;">
        <?php if ($usersResult->num_rows > 0): ?>
            <?php while ($user = $usersResult->fetch_assoc()): ?>
                <li style="padding: 12px 20px; border-bottom: 1px solid #ddd; display: flex; justify-content: space-between; align-items: center; background-color:rgb(203, 221, 238); transition: background-color 0.3s, transform 0.3s; border-radius: 30px; margin-bottom: 10px;">
                    <a href="?receiver_username=<?php echo $user['username']; ?>" style="color:rgb(25, 0, 255); text-decoration: none; font-size: 18px; font-weight: bold;">
                        <?php echo htmlspecialchars($user['username']); ?>
                    </a>
                    <span style="font-size: 14px; color: #456;">Active</span>
                </li>
            <?php endwhile; ?>
        <?php else: ?>
            <li style="padding: 12px 20px; color: #888;">No users found</li>
        <?php endif; ?>
    </ul>
</div>


<script>
    // Adding hover effects to each list item
    const listItems = document.querySelectorAll('li');
    listItems.forEach(item => {
        item.addEventListener('mouseover', function() {
            this.style.backgroundColor = '#e0f7fa'; // Light blue background on hover
            this.style.transform = 'translateX(5px)'; // Slightly shift right on hover
        });
        item.addEventListener('mouseout', function() {
            this.style.backgroundColor = '#f9f9f9'; // Revert to original background
            this.style.transform = 'translateX(0)'; // Revert the shift effect
        });
    });
</script>


        <?php if (isset($_GET['receiver_username'])): ?>
            <div class="form-container">

            <div class="form-container" style="padding: 30px; background-color:rgb(165, 223, 165); border-radius: 12px; box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1); max-width: 600px; margin: 50px auto;">
    <h3 style="font-family: 'Arial', sans-serif; font-size: 24px; color: #333; text-align: center; margin-bottom: 20px;">Send a Message to <?php echo $_GET['receiver_username']; ?></h3>
    
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="receiver_username" value="<?php echo $_GET['receiver_username']; ?>">

        <!-- Message Textarea -->
        <textarea name="message" placeholder="Enter your message" required 
                  style="width: 100%; padding: 15px; border-radius: 8px; border: 1px solid #ccc; margin-bottom: 20px; font-size: 16px; color: #555; font-family: 'Arial', sans-serif; box-sizing: border-box; transition: all 0.3s ease;">
        </textarea>
        
        <div style="display: flex; justify-content: center; align-items: center; gap: 20px; margin-top: 20px;">
    <!-- File Upload with Icon -->
    <label for="file-upload" 
           style="cursor: pointer; font-size: 28px; color: #007bff; transition: color 0.3s ease, transform 0.3s; display: flex; align-items: center;">
        <i class="fas fa-upload"></i>
    </label>
    <input type="file" id="file-upload" name="media" accept="image/*,video/*,audio/*,.pdf,.doc,.docx" 
           style="display: none; transition: all 0.3s ease;">
    
    <!-- Send Message Button -->
    <button type="submit" name="send_message" 
            style="background: #007bff; color: white; padding: 12px 20px; border: none; border-radius: 50%; cursor: pointer; font-size: 28px; transition: background-color 0.3s, transform 0.3s; display: flex; align-items: center; justify-content: center;">
        <i class="fas fa-paper-plane"></i>
    </button>
</div>
        </div>
    </form>
</div>

<script>
    // Adding hover effect to the send message button using inline CSS (for hover state)
    const sendMessageButton = document.querySelector('button[name="send_message"]');
    const fileUploadLabel = document.querySelector('label[for="file-upload"]');

    sendMessageButton.addEventListener('mouseover', function() {
        this.style.backgroundColor = '#0056b3'; // Darker blue on hover
        this.style.transform = 'scale(1.1)'; // Slightly scale the button on hover
    });

    sendMessageButton.addEventListener('mouseout', function() {
        this.style.backgroundColor = '#007bff'; // Original blue
        this.style.transform = 'scale(1)'; // Reset the scaling effect
    });

    // Hover effect for file upload label
    fileUploadLabel.addEventListener('mouseover', function() {
        this.style.color = '#0056b3'; // Change color on hover
        this.style.transform = 'scale(1.1)'; // Slightly scale the icon on hover
    });

    fileUploadLabel.addEventListener('mouseout', function() {
        this.style.color = '#007bff'; // Reset to original color
        this.style.transform = 'scale(1)'; // Reset the scaling effect
    });
</script>


<h3 style="font-color: white;">Message History</h3>
<div class="messages">
    <?php if ($messagesResult && $messagesResult->num_rows > 0): ?>
        <?php while ($message = $messagesResult->fetch_assoc()): ?>
            <?php 
                // Check if the message is sent by the logged-in user or the receiver
                $messageBackground = ($message['sender'] == $_SESSION['user_id']) ? '#f9f4ff' : '#d0f0ff'; // Light purple for sender, soft blue for receiver
                $textColor = ($message['sender'] == $_SESSION['user_id']) ? '#5d3f8f' : '#4b8ca2'; // Dark purple for sender, dark blue for receiver
            ?>
            <div class="message" style="position: relative; padding: 10px; background-color:  rgba(235, 65, 232, 0.2); <?php echo $messageBackground; ?>; border-radius: 20px; box-shadow: 0 4px 8px rgba(192, 139, 232, 0.2); margin-bottom: 15px;">
                <div style="display: flex; justify-content: center; align-items: center; background-color: rgba(240, 237, 244, 0.2); margin: 40px;  border-radius:30px;">
                    <div style="text-align: left; padding: 10px; border-radius: 12px; box-shadow: 0 4px 8px rgba(241, 239, 241, 0.2); width: 80%; max-width: 600px;">
                        <strong style="font-size: 22px; color: <?php echo $textColor; ?>; font-weight: bold; margin-bottom: 5px ; color:rgb(24, 22, 22);"><?php echo $message['sender']; ?></strong>
                        <p style="font-size: 18px; color:rgb(24, 22, 22); ; line-height: 1.6;"><?php echo $message['message']; ?></p>
                    </div>
                </div>

                <?php if ($message['file_path']): ?>
                    <div class="media-container" style="margin-top: 10px; padding: 10px 0;">
                        <?php 
                        $file_ext = pathinfo($message['file_path'], PATHINFO_EXTENSION);
                        if (in_array(strtolower($file_ext), ['jpg', 'jpeg', 'png', 'gif'])): ?>
                            <img src="uploads/<?php echo $message['file_path']; ?>" alt="Image" style="max-width: 100%; border-radius: 12px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);">
                        <?php elseif (in_array(strtolower($file_ext), ['mp4'])): ?>
                            <video controls style="max-width: 100%; border-radius: 12px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);"><source src="uploads/<?php echo $message['file_path']; ?>" type="video/mp4"></video>
                        <?php elseif (in_array(strtolower($file_ext), ['mp3'])): ?>
                            <audio controls style="max-width: 100%;"><source src="uploads/<?php echo $message['file_path']; ?>" type="audio/mp3"></audio>
                        <?php elseif (strtolower($file_ext) == 'pdf'): ?>
                            <iframe src="uploads/<?php echo $message['file_path']; ?>" width="100%" height="400" style="border-radius: 12px;"></iframe>
                        <?php elseif (in_array(strtolower($file_ext), ['doc', 'docx'])): ?>
                            <a href="uploads/<?php echo $message['file_path']; ?>" target="_blank" style="font-size: 16px; color: #6b6b6b; text-decoration: none;">Download Word Document</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
     


                <!-- Centered Update and Delete Icons Below Content -->
                <div class="icon-container" style="text-align: center; margin-top: 20px;">
                    <!-- Update Icon (Edit Message) -->
                    <label for="update_form_<?php echo $message['id']; ?>" style="cursor: pointer; font-size: 28px; color: #007bff; transition: transform 0.3s, color 0.3s; margin-right: 20px;">
                        <i class="fas fa-edit" style="font-size: 28px; transition: color 0.3s ease;"></i>
                    </label>
                    
                    <!-- Delete Icon (Delete Message) -->
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
                        <label for="delete_message_<?php echo $message['id']; ?>" style="cursor: pointer; font-size: 28px; color: #dc3545; transition: transform 0.3s, color 0.3s;">
                            <i class="fas fa-trash-alt" style="font-size: 28px; transition: color 0.3s ease;"></i>
                        </label>
                        <input type="submit" id="delete_message_<?php echo $message['id']; ?>" name="delete_message" style="display: none;">
                    </form>
                </div>

                <div class="message-footer" style="margin-top: 10px; text-align: center;">
                    <small>Sent on: <?php echo $message['timestamp']; ?></small>
                </div>

                <!-- Hidden Update Form -->
<div id="update-form-<?php echo $message['id']; ?>" class="update-form-container" style="display: none; padding: 20px; background-color:rgb(154, 221, 234); border-radius: 10px; box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1); margin-top: 20px;">
    <form method="POST" enctype="multipart/form-data" id="update_form_<?php echo $message['id']; ?>" style="display: flex; flex-direction: column; align-items: flex-start;">
        <input type="hidden" name="message_id" value="<?php echo $message['id']; ?>">
        
        <!-- Update Message Textarea -->
        <textarea name="new_message" placeholder="Update your message" required 
                  style="width: 100%; padding: 15px; border-radius: 8px; border: 1px solid #ccc; margin-bottom: 20px; font-size: 16px; color: #555; font-family: 'Arial', sans-serif; box-sizing: border-box; transition: all 0.3s ease;">
        </textarea>

        <!-- Media Upload and Update Icons (Side by Side, Centered) -->
        <div style="display: flex; justify-content: center; gap: 20px; width: 100%; align-items: center;">
            <!-- Upload Icon (Replace "Choose file") -->
            <label for="file-upload_<?php echo $message['id']; ?>" style="cursor: pointer; font-size: 28px; color: #007bff; transition: transform 0.3s, color 0.3s;">
                <i class="fas fa-cloud-upload-alt" style="font-size: 28px; transition: color 0.3s ease;"></i>
            </label>
            <input type="file" id="file-upload_<?php echo $message['id']; ?>" name="media" accept="image/*,video/*,audio/*,.pdf,.doc,.docx" style="display: none;">
            
            <!-- Submit Button for Update -->
            <label for="update_message_submit" style="cursor: pointer; font-size: 28px; color: #007bff; transition: color 0.3s ease, transform 0.3s;">
                <i class="fas fa-sync-alt" style="font-size: 28px; transition: color 0.3s ease;"></i> 
            </label>
            <input type="submit" id="update_message_submit" name="update_message" style="display: none;">
        </div>
    </form>
</div>

            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <p>No messages yet.</p>
    <?php endif; ?>
</div>

<script>
    // Function to show the update form when the update icon is clicked
    document.querySelectorAll('label[for^="update_form_"]').forEach(label => {
        label.addEventListener('click', function(e) {
            const messageId = e.target.closest('label').getAttribute('for').split('_')[2]; // Extract message ID
            const updateForm = document.getElementById(`update-form-${messageId}`);
            updateForm.style.display = updateForm.style.display === "none" || updateForm.style.display === "" ? "block" : "none";
        });
    });

    // Hover effect for the update icon
    document.querySelectorAll('label[for^="update_form_"]').forEach(icon => {
        icon.addEventListener('mouseover', function() {
            this.querySelector('i').style.color = '#0056b3'; // Dark blue on hover
            this.querySelector('i').style.transform = 'scale(1.2)'; // Scale up the icon
        });
        icon.addEventListener('mouseout', function() {
            this.querySelector('i').style.color = '#007bff'; // Reset to original color
            this.querySelector('i').style.transform = 'scale(1)'; // Reset icon size
        });
    });

    // Hover effect for the delete icon
    document.querySelectorAll('label[for^="delete_message_"]').forEach(icon => {
        icon.addEventListener('mouseover', function() {
            this.querySelector('i').style.color = '#c82333'; // Red on hover
            this.querySelector('i').style.transform = 'scale(1.2)'; // Scale up the icon
        });
        icon.addEventListener('mouseout', function() {
            this.querySelector('i').style.color = '#dc3545'; // Reset to original color
            this.querySelector('i').style.transform = 'scale(1)'; // Reset icon size
        });
    });
</script>




        <?php else: ?>
            <p>Please select a user to chat with.</p>
        <?php endif; ?>
    <?php endif; ?> 

</body>
</html>


<?php $conn->close(); ?>
