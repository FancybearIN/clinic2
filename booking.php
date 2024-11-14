<?php
// Database connection using mysqli (XAMPP)
$conn = mysqli_connect("localhost", "root", "", "clinic_db");

if ($conn === false) {
    die("ERROR: Could not connect. " . mysqli_connect_error());
}


if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patientName = $_POST['name']; // Get patient name directly from the form
    $age = $_POST['age'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $departmentId = $_POST['department'];
    $doctorId = $_POST['doctor'];
    $appointmentDate = $_POST['appointment_date'];
    $appointmentTime = $_POST['appointment_time'];
    $reason = $_POST['reason'];



    // Input validation (add more as needed)
    if (empty($patientName) || empty($departmentId) || empty($doctorId) || empty($appointmentDate) || empty($appointmentTime) || empty($email) || empty($phone) ) {
        $error = "All fields are required.";
    } else {


        // Check for existing appointments for the same patient details and time (optional)
         $checkSql = "SELECT * FROM appointment 
                     WHERE  appointmentdate = ? AND appointmenttime = ? AND email=? AND phone=?"; // Prevent double-booking for patients with the same name and other details
        $stmtCheck = mysqli_prepare($conn, $checkSql);
        mysqli_stmt_bind_param($stmtCheck, "ssss",$appointmentDate, $appointmentTime, $email, $phone);
        mysqli_stmt_execute($stmtCheck);
        $resultCheck = mysqli_stmt_get_result($stmtCheck);

        if (mysqli_num_rows($resultCheck) > 0) {
            $error = "An appointment with these details already exists for this date and time.";
        } else {


            // Insert patient details into the patient table if they don't exist, or update if they do.

            $patientSql = "INSERT INTO patient (patientname, age, email, phone, status) VALUES (?, ?, ?, ?, 'Active')
                     ON DUPLICATE KEY UPDATE age = VALUES(age)"; // Use INSERT ... ON DUPLICATE KEY UPDATE 
            $stmtPatient = mysqli_prepare($conn, $patientSql);
            mysqli_stmt_bind_param($stmtPatient, "siss", $patientName, $age, $email, $phone);

            if (mysqli_stmt_execute($stmtPatient)) {
                $patientId = mysqli_insert_id($conn); // Get the patient ID (if new) or it will be 0 if updated
                if($patientId == 0) {
                        //Get the existing patient ID if an update occurred
                         $getPatientIdSql = "SELECT patientid FROM patient WHERE email = ? AND phone = ?";
                         $stmtGetPatientId = mysqli_prepare($conn, $getPatientIdSql);
                          mysqli_stmt_bind_param($stmtGetPatientId, "ss", $email, $phone);
                          mysqli_stmt_execute($stmtGetPatientId);
                           $resultPatientId = mysqli_stmt_get_result($stmtGetPatientId);
                           $rowPatientId = mysqli_fetch_assoc($resultPatientId);
                           $patientId = $rowPatientId['patientid'];
                }

                $sql = "INSERT INTO appointment (patientid, departmentid, doctorid, appointmentdate, appointmenttime, app_reason, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "iiisss", $patientId, $departmentId, $doctorId, $appointmentDate, $appointmentTime, $reason);



                if (mysqli_stmt_execute($stmt)) {
                    $success = "Appointment request submitted successfully!";

                    header("Location: booking_confirmation.php"); 
                    exit();
                } else {
                    $error = "Error submitting appointment: " . mysqli_error($conn); 
                }

                mysqli_stmt_close($stmt); 
            }
              else {
                    $error = "Error inserting or updating patient: " . mysqli_error($conn);
              }
               mysqli_stmt_close($stmtPatient);


        }
            mysqli_stmt_close($stmtCheck);
    }

}


// Fetch departments and doctors (for dropdowns) - SAME AS BEFORE
$departments = $conn->query("SELECT * FROM department WHERE status='Active'")->fetch_all(MYSQLI_ASSOC); 
$doctors = $conn->query("SELECT * FROM doctor WHERE status='Active'")->fetch_all(MYSQLI_ASSOC); 

mysqli_close($conn);
?>

<!-- ... (HTML form - similar to the previous version but now includes patient name, email, and phone)  -->
<!DOCTYPE html>
<html>
<head>
<title>Book Appointment</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
<style>
  /* Add your custom styles here */
  body {
    font-family: sans-serif;
  }
  .container {
    margin-top: 50px;
  }
  .appointment-form {
    padding: 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
  }
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
