<?php
session_start(); // Start the session

// Database connection using mysqli (XAMP)
$conn = mysqli_connect("localhost", "root", "", "clinic_db"); // Replace with your XAMPP credentials

if ($conn === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}


// Check if the request method is POST (form submission)
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = password_hash($_POST["password"], PASSWORD_DEFAULT);  // Hash the password
    $role = $_POST["role"];  // Get the selected role

    // Check if email already exists
    $checkEmail = "SELECT * FROM users WHERE email = ?";  // Use parameterized query
    $stmtCheck = mysqli_prepare($conn, $checkEmail);
    mysqli_stmt_bind_param($stmtCheck, "s", $email);
    mysqli_stmt_execute($stmtCheck);
    $resultCheck = mysqli_stmt_get_result($stmtCheck);

    if (mysqli_num_rows($resultCheck) > 0) {
        $error = "Email already exists.";
    } else {
        $sql = "INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)"; // Parameterized query
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $password, $role);

        if (mysqli_stmt_execute($stmt)) {
            $userId = mysqli_insert_id($conn);  // Get the last inserted ID

            $_SESSION['loggedin'] = true;
            $_SESSION['user_id'] = $userId;
            $_SESSION['role'] = $role;  // Store the role in the session

            // Redirect based on role
            if ($role == 'patient') {
                header("Location: patient_dashboard.php"); // Redirect to patient dashboard
                exit(); // Terminate the script
            } else if ($role == 'doctor') {
                header("Location: doctor_dashboard.php"); // Redirect to doctor dashboard
                exit();
            } else {
                // Handle other roles if needed or provide a default redirect.
                 header("Location: login.php"); // Redirect to login
                exit();
            }
        } else {
            $error = "Error creating account.";
        }
          mysqli_stmt_close($stmt);
    }
     mysqli_stmt_close($stmtCheck);
}

mysqli_close($conn); // Close connection
?>


<!DOCTYPE html>
<html>
<head>
 <title>Registration</title>
    <!-- Add your CSS and other head content here -->
</head>
<body>
 <div class="wrap">  
        <div class="fm-box Register">
            <h2>Registration</h2>
            <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                </form>

                <div id="roleModal" class="modal">
                  <div class="modal-content">
                        <span class="close" onclick="closeModal()">&times;</span>
                  </div>
              </div>
</div>

 <script>
        // ... (your JavaScript code)
    </script>
</body>
</html>
