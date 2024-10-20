<?php
// Database connection (replace with your actual connection details)
$host = "localhost"; // usually localhost
$user = "root"; // default user
$password = ""; // default password
$database = "seating0"; // replace with your database name

$connection = new mysqli($host, $user, $password, $database);

// Check connection
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}

$room_id = 20; 

// Check if form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['room_id'])) {
    $room_id = intval($_POST['room_id']);
}

// Fetch available rooms for the dropdown
$room_query = "SELECT DISTINCT room_id FROM batch";
$room_result = $connection->query($room_query);

// Initialize an array to hold students
$students = [];

// Fetch batch data based on selected room_id
$batch_query = "SELECT * FROM batch WHERE room_id = $room_id";
$batch_result = $connection->query($batch_query);

// Fetch students based on batch data
if ($batch_result->num_rows > 0) {
    while ($row = $batch_result->fetch_assoc()) {
        $start_roll_no = $row['startno'];
        $end_roll_no = $row['endno'];
        $class = $row['class_id'];

        // Fetch students within the roll number range for this class
        $student_query = "SELECT * FROM students WHERE class = '$class' AND rollno BETWEEN '$start_roll_no' AND '$end_roll_no'";
        $student_result = $connection->query($student_query);

        if ($student_result->num_rows > 0) {
            while ($student = $student_result->fetch_assoc()) {
                $students[$class][] = $student; // Group students by class
            }
        }
    }
}

// Prepare seating plan
$rows = 6; // Number of rows
$cols = 6; // Total number of columns
$seating_plan = array_fill(0, $rows, array_fill(0, $cols, null)); // Initialize seating plan with null values

$class_keys = array_keys($students); // Get class keys

// Fill seating for the first class (Class 1)
if (isset($students[$class_keys[0]])) {
    $first_class_students = $students[$class_keys[0]];
    $index_first = 0;

    // Fill the first and third columns with students from the first class
    for ($c = 0; $c < $cols; $c += 2) { // Iterate over every first and third column
        for ($row = 0; $row < $rows; $row++) { // Iterate over rows
            if ($index_first < count($first_class_students)) {
                $seating_plan[$row][$c] = $first_class_students[$index_first]; // Assign student to current column
                $index_first++;
            }
        }
    }
}

// Fill seating for the second class (Class 2)
if (count($class_keys) > 1 && isset($students[$class_keys[1]])) {
    $second_class_students = $students[$class_keys[1]];
    $index_second = 0;

    // Fill the second and fourth columns with students from the second class
    for ($c = 1; $c < $cols; $c += 2) { // Iterate over every second and fourth column
        for ($row = 0; $row < $rows; $row++) { // Iterate over rows
            if ($index_second < count($second_class_students)) {
                $seating_plan[$row][$c] = $second_class_students[$index_second]; // Assign student to current column
                $index_second++;
            }
        }
    }
}

// Fill remaining spots in Class 2 columns with "Vacant" if no students are from Class 2
for ($row = 0; $row < $rows; $row++) {
    // Ensure second column is filled with 'Vacant' if no students are there
    if (empty($seating_plan[$row][1])) {
        $seating_plan[$row][1] = ['rollno' => 'Vacant', 'class' => 'Vacant'];
    }
    // Ensure fourth column is filled with 'Vacant' if no students are there
    if (empty($seating_plan[$row][3])) {
        $seating_plan[$row][3] = ['rollno' => 'Vacant', 'class' => 'Vacant'];
    }
    // Ensure sixth column is filled with 'Vacant' if no students are there
    if (empty($seating_plan[$row][5])) {
        $seating_plan[$row][5] = ['rollno' => 'Vacant', 'class' => 'Vacant'];
    }
}

// Close the database connection
$connection->close();

// Define class color mapping for each class
$class_colors = [
    '44' => '#48CFCB', // Red for Class 44
    '43' => '#7E8EF1', // Blue for Class 43
    'Class42' => '#00FF00', // Green for Class 42
    'Class41' => '#FFFF00', // Yellow for Class 41
    'Class40' => '#FF00FF', // Magenta for Class 40
    // Add more class mappings as necessary
];

echo '<pre>'; // Start output buffering for debug statements



// Debug: Output seating plan and assigned colors


echo '</pre>'; // End output buffering
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Seating Plan and Student List</title>
    <style>
        .seat {
            width: 80px; /* Width of each seat */
            height: 80px; /* Height of each seat */
            border: 1px solid #ccc; /* Border style */
            display: flex; /* Flexbox for center alignment */
            align-items: center; /* Vertically center content */
            justify-content: center; /* Horizontally center content */
            font-size: 14px; /* Font size */
            margin: 5px; /* Space between each seat */
        }

        .row {
            display: flex;
            justify-content: center; /* Center rows */
            margin-bottom: 10px; /* Space between rows */
        }
        .seat:nth-child(3),
        .seat:nth-child(5) {
         margin-left: 40px; /* Adjust as needed for spacing */
        }


        .student-list {
            margin-top: 20px;
        }

        .class-section {
            margin-bottom: 20px;
            padding: 10px;
            border: 1px solid #ccc;
        }
    </style>
</head>
<body>
    <h1>Seating Plan</h1>
    
    <!-- Room Selection Form -->
    <form method="POST">
        <label for="room_id">Select Room ID:</label>
        <select name="room_id" id="room_id">
            <?php if ($room_result->num_rows > 0): ?>
                <?php while ($row = $room_result->fetch_assoc()): ?>
                    <option value="<?= $row['room_id'] ?>" <?= $room_id == $row['room_id'] ? 'selected' : ''; ?>>
                        <?= $row['room_id'] ?>
                    </option>
                <?php endwhile; ?>
            <?php endif; ?>
        </select>
        <button type="submit">Submit</button>
    </form>

    <div>
        <?php for ($r = 0; $r < $rows; $r++): // Loop through rows ?>
            <div class="row">
                <?php for ($c = 0; $c < $cols; $c++): // Loop through columns ?>
                    <?php
                    // Get the current student if available
                    $current_student = $seating_plan[$r][$c];
                    $background_color = '#f9f9f9'; // Default color for vacant seats

                    // If there is a current student
                    if (!empty($current_student) && isset($current_student['class'])) {
                        $class = $current_student['class']; // Get the class of the current student
                        $background_color = $class_colors[$class] ?? '#f9f9f9'; // Use class color or default
                    }
                    ?>
                    <div class="seat" style="background-color: <?= $background_color; ?>">
                        <?= !empty($current_student) ? htmlspecialchars($current_student['rollno']) : 'Vacant'; ?>
                    </div>
                <?php endfor; ?>
            </div>
        <?php endfor; ?>
    </div>

    <div class="student-list">
        <?php if (!empty($students)): ?>
            <?php 
                foreach ($students as $class => $class_students): 
            ?>
                <div class="class-section">
                    <h3>Class ID: <?= htmlspecialchars($class) ?></h3>
                    <ul>
                        <?php foreach ($class_students as $student): ?>
                            <li>Roll No: <?= htmlspecialchars($student['rollno']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <p>No students found for this room.</p>
        <?php endif; ?>
    </div>
</body>
</html>
