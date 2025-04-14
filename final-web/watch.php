<?php
include('connect.php');  // Include the database connection

// SQL query to retrieve all messages
$sql = "SELECT * FROM messages ORDER BY created_at DESC";
$result = $conn->query($sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blogs</title>
    <style>
        /* Styles for message container */
        .messages-container {
            width: 80%;
            margin: 0 auto;
            padding: 20px;
            background-color: #f4f4f4;
            border-radius: 8px;
        }

        /* Header style */
        h2 {
            text-align: center;
            color: #333;
        }

        /* Style for each message */
        .messages-list {
            margin-top: 20px;
        }

        .message {
            background-color: #fff;
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .message-text {
            font-size: 16px;
            color: #333;
        }

        .message-time {
            font-size: 12px;
            color: #888;
        }

        /* Style for the "No messages" message */
        .no-messages {
            text-align: center;
            color: #888;
        }
    </style>
</head>
<body>
    <div class="messages-container">
        <h2>Blogs</h2>

        <?php if ($result->num_rows > 0): ?>
            <div class="messages-list">
                <?php while ($row = $result->fetch_assoc()): ?>
                    <div class="message" id="message-<?php echo $row['id']; ?>">
                        <p class="message-text" id="message-text-<?php echo $row['id']; ?>"><?php echo htmlspecialchars($row['message']); ?></p>
                        <small class="message-time"><?php echo $row['created_at']; ?></small>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <p class="no-messages">No messages found.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
