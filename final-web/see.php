<?php
// Step 1: Database connection settings
$servername = "localhost";  // Database server (usually localhost)
$username = "root";         // Database username (default for XAMPP is 'root')
$password = "";             // Database password (default for XAMPP is empty)
$dbname = "biodata_db";     // Name of the database

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check if the connection is successful
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Step 2: Retrieve all messages from the database
$sql = "SELECT id, name, email, message FROM messagess";
$result = $conn->query($sql);

// Step 3: Display the data in a table if there are any records
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Messages</title>
    <style>
        /* Basic Styling for the page */
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        header {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 10px;
        }

        main {
            padding: 20px;
            max-width: 900px;
            margin: 0 auto;
            background-color: white;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ccc;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        tr:nth-child(even) {
            background-color: #f9f9f9;
        }

        footer {
            text-align: center;
            margin-top: 20px;
            padding: 10px;
            background-color: #333;
            color: white;
        }
    </style>
</head>
<body>
    <header>
        <h1>View Messages</h1>
    </header>

    <main>
        <h2>View Messages</h2>

        <?php
        if ($result->num_rows > 0) {
            // Display the data in a table
            echo "<table>";
            echo "<tr><th>ID</th><th>Name</th><th>Email</th><th>Message</th></tr>";

            // Fetch and display each row
            while ($row = $result->fetch_assoc()) {
                echo "<tr>
                        <td>" . $row["id"] . "</td>
                        <td>" . $row["name"] . "</td>
                        <td>" . $row["email"] . "</td>
                        <td>" . nl2br($row["message"]) . "</td>
                      </tr>";
            }

            echo "</table>";
        } else {
            // If no records are found
            echo "<p>No messages found.</p>";
        }

        // Step 4: Close the database connection
        $conn->close();
        ?>
    </main>

    <footer>
        <p>&copy; 2025</p>
    </footer>
</body>
</html>
