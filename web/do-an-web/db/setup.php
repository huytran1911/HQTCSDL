<?php
$servername = "localhost";
$username = "root";
$password = "";


$conn = mysqli_connect($servername, $username, $password);


if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}


$sql = file_get_contents('setup.sql');


if ($conn->multi_query($sql)) {
    echo "<h2>Database setup completed successfully!</h2>";
    echo "<p>The database has been created and initialized with sample data.</p>";
    echo "<p><a href='../index.php'>Go to homepage</a></p>";
} else {
    echo "Error: " . $conn->error;
}

$conn->close();
?> 