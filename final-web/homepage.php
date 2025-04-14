<?php
session_start();
include("connect.php");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Homepage</title>
    <style>
        /* Basic styling */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        /* Centered container for greeting */
        .container {
            text-align: center;
            padding: 15%;
        }

        /* Styling for the greeting message */
        .greeting {
            font-size: 50px;
            font-weight: bold;
        }

        /* Styling for the logout button */
        .logout-btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            font-size: 18px;
            border-radius: 5px;
            margin-top: 20px;
            display: inline-block;
        }

        .logout-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
      <p class="greeting">
        Hello  
        <?php 
        if(isset($_SESSION['email'])){
            $email = $_SESSION['email'];
            $query = mysqli_query($conn, "SELECT users.* FROM `users` WHERE users.email='$email'");
            while($row = mysqli_fetch_array($query)){
                echo $row['firstName'].' '.$row['lastName'];
            }
        }
        ?>
        :)
      </p>
      
      <!-- Styled logout button -->
      <a href="watch.php" class="logout-btn">Read Blog</a>
    </div>
</body>
</html>
