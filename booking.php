<?php
session_start();

// Database connection using mysqli (XAMPP)
$conn = mysqli_connect("localhost", "root", "", "clinic_db");

if ($conn === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}

// Check if the patient is logged in
if (!isset($_SESSION['loggedin']) || $_SESSION['role'] !== 'patient') {
    header("Location: login.php"); // Redirect to login if not a logged-in patient
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patientId = $_SESSION['user_id'];
    $departmentId = $_POST['department'];
    $doctorId = $_POST['doctor'];
    $appointmentDate = $_POST['appointment_date'];
    $appointmentTime = $_POST['appointment_time'];
    $reason = $_POST['reason'];

    // Input validation (add more as needed)
    if (empty($departmentId) || empty($doctorId) || empty($appointmentDate) || empty($appointmentTime)) {
        $error = "All fields are required.";
    } else {
        // Check for existing appointments (optional - depends on your logic)
        $checkSql = "SELECT * FROM appointment 
                     WHERE patientid = ? AND appointmentdate = ? AND appointmenttime = ? AND (status = 'Pending' OR status = 'Approved')"; // Prevent double-booking
        $stmtCheck = mysqli_prepare($conn, $checkSql);
        mysqli_stmt_bind_param($stmtCheck, "iss", $patientId, $appointmentDate, $appointmentTime);
        mysqli_stmt_execute($stmtCheck);
        $resultCheck = mysqli_stmt_get_result($stmtCheck);

        if (mysqli_num_rows($resultCheck) > 0) {
            $error = "You already have an appointment scheduled for this date and time.";
        } else {
            $sql = "INSERT INTO appointment (patientid, departmentid, doctorid, appointmentdate, appointmenttime, app_reason, status) 
                    VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "iiisss", $patientId, $departmentId, $doctorId, $appointmentDate, $appointmentTime, $reason);

            if (mysqli_stmt_execute($stmt)) {
                $success = "Appointment request submitted successfully!";
                // Clear form fields after successful submission (optional)

                // Redirect to a confirmation or thank you page
                header("Location: booking_confirmation.php"); // Create this page
                exit();
            } else {
                $error = "Error submitting appointment: " . mysqli_error($conn); // More specific error message
            }
            mysqli_stmt_close($stmt); // Close the prepared statement
        }
        mysqli_stmt_close($stmtCheck);
    }
}

// Fetch departments and doctors (for dropdowns)
$departments = $conn->query("SELECT * FROM department WHERE status='Active'")->fetch_all(MYSQLI_ASSOC); // Use fetch_all for mysqli
$doctors = $conn->query("SELECT * FROM doctor WHERE status='Active'")->fetch_all(MYSQLI_ASSOC);

mysqli_close($conn); // Close the connection
?>
<!-- ... (HTML form - similar to previous example, but modified for patient use) ... -->
<!DOCTYPE html>
<html>
<head>
<title>Book Appointment</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<style>
  /* ... (Your CSS styles) ... */
</style>

</head>
<body>
<div class="container">
  <div class="appointment-form">
    <h2>Book Appointment</h2>

    <?php if (isset($error)): ?>
      <p class="text-danger"><?php echo $error; ?></p>
    <?php endif; ?>
    <?php if (isset($success)): ?>
      <p class="text-success"><?php echo $success; ?></p>
    <?php endif; ?>

    <form method="post">
    <div class="form-group">
        <label for="department">Department:</label>
        <select name="department" id="department" class="form-control" required>
          <option value="">Select Department</option>
          <?php foreach ($departments as $department): ?>
            <option value="<?php echo $department['departmentid']; ?>"><?php echo $department['departmentname']; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="doctor">Doctor:</label>
        <select name="doctor" id="doctor" class="form-control" required>
          <option value="">Select Doctor</option>
          <?php foreach ($doctors as $doctor): ?>
            <option value="<?php echo $doctor['doctorid']; ?>"><?php echo $doctor['doctorname']; ?></option>
          <?php endforeach; ?>
        </select>
      </div>

      <div class="form-group">
        <label for="appointment_date">Date:</label>
        <input type="date" name="appointment_date" id="appointment_date" class="form-control" required>
      </div>
      <div class="form-group">
        <label for="appointment_time">Time:</label>
        <input type="time" name="appointment_time" id="appointment_time" class="form-control" required>
      </div>
      <div class="form-group">
        <label for="reason">Reason:</label>
        <textarea name="reason" id="reason" class="form-control"></textarea>
      </div>

      <button type="submit" class="btn btn-primary">Book Appointment</button>
    </form>
  </div>
</div>
</body>
</html>

