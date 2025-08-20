<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee List</title>
    <style>
        .calendar {
            display: grid;
            grid-template-columns: repeat(7, 1fr);
            gap: 1px;
            max-width: 800px;
            margin: 0 auto;
        }
        .calendar div {
            padding: 10px;
            text-align: center;
            border: 1px solid #ddd;
            box-sizing: border-box;
            min-height: 60px;
        }
        .header {
            background-color: #f0f0f0;
            font-weight: bold;
        }
        .blue {
            background-color: #ADD8E6; /* Light Blue color */
            color: black;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Select an Employee</h1>
        <select id="employee-dropdown">
            <option value="">-- Select an Employee --</option>
            <?php
            // Database connection
            $conn = new mysqli("localhost", "root", "", "tarryn_workplaceportal");
            
            // Fetch employee list
            $employeesQuery = "SELECT id, fullname FROM employee_list";
            $employees = $conn->query($employeesQuery);
            
            // Populate dropdown with employees
            while ($employee = $employees->fetch_assoc()) {
                echo "<option value=\"" . $employee['id'] . "\">" . htmlspecialchars($employee['fullname']) . "</option>";
            }

            // Close the database connection
            $conn->close();
            ?>
        </select>
        <div id="calendar-container">
            <!-- Employee calendar will be displayed here -->
        </div>
    </div>

    <script>
        document.getElementById('employee-dropdown').addEventListener('change', function() {
            var employeeId = this.value;
            var container = document.getElementById('calendar-container');
            
            if (employeeId) {
                // Fetch calendar for the selected employee using AJAX
                var xhr = new XMLHttpRequest();
                xhr.open('GET', 'calendar.php?employee_id=' + employeeId, true);
                xhr.onload = function() {
                    if (xhr.status === 200) {
                        container.innerHTML = xhr.responseText;
                    } else {
                        container.innerHTML = 'Error fetching calendar.';
                    }
                };
                xhr.send();
            } else {
                container.innerHTML = '';
            }
        });
    </script>
</body>
</html>
