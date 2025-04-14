<?php
// Start the session to track user login status
session_start();

// Database connection
$servername = "localhost"; // Your server (typically localhost)
$username = "root";        // Your database username (typically root for local setups)
$password = "";            // Your database password (typically empty for local setups)
$dbname = "admin_login";   // Your database name

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
} 

// Handle login request
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get the posted username and password
    $inputUsername = $_POST['username'];
    $inputPassword = $_POST['password'];

    // Prepare the SQL query to fetch user from the database
    $sql = "SELECT * FROM userss WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $inputUsername);
    $stmt->execute();
    $result = $stmt->get_result();

    // Check if the query found any user
    if ($result->num_rows > 0) {
        // User exists, fetch the user data
        $user = $result->fetch_assoc();

        // Compare the plaintext password with the one in the database
        if ($inputPassword === $user['password']) {
            // Successful login, store user session
            $_SESSION['loggedin'] = true;
            $_SESSION['username'] = $user['username'];
            header("Location: http://localhost/lgsg/e.php"); // Redirect to the dashboard
            exit();
        } else {
            // Invalid password
            $error = "Invalid username or password.";
        }
    } 
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f9;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            width: 300px;
        }
        h2 {
            text-align: center;
            color: #333;
        }
        .form-group {
            margin-bottom: 15px;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 10px;
            border-radius: 4px;
            border: 1px solid #ccc;
            font-size: 16px;
        }
        input[type="submit"] {
            width: 100%;
            padding: 10px;
            border: none;
            background-color: #007bff;
            color: white;
            font-size: 16px;
            border-radius: 4px;
            cursor: pointer;
        }
        input[type="submit"]:hover {
            background-color: #0056b3;
        }
        .error {
            color: red;
            text-align: center;
            margin-top: 10px;
        }
        .db-success {
            color: green;
            text-align: center;
            margin-top: 10px;
        }
    </style>
</head>
<body>

    <div class="login-container">
        <h2>Admin Login</h2>
        <form action="admin.php" method="POST">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" id="password" name="password" required>
            </div>
            <input type="submit" value="Login">
        </form>

        <?php 
        if (isset($error)) { 
            echo "<p class='error'>$error</p>"; 
        }

        // Display the database connection success message if the connection is successful
        if (isset($dbMessage)) {
            echo "<p class='db-success'>$dbMessage</p>";
        }
        ?>
    </div>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?> 
