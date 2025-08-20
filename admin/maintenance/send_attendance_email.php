<?php
// Database connection
$conn = new mysqli("localhost", "root", "", "tarryn_workplaceportal");

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle PDF generation request
if (isset($_POST['generate_pdf'])) {
    require __DIR__ . '/dompdf/autoload.inc.php';
    use Dompdf\Dompdf;
    
    try {
        $dompdf = new Dompdf();
        $dompdf->loadHtml($_POST['html_content']);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        $dompdf->stream("attendance_".$_POST['employee_id']."_".$_POST['month']."_".$_POST['year'].".pdf");
        exit;
    } catch (Exception $e) {
        die("PDF Error: " . $e->getMessage());
    }
}

// [Rest of your existing PHP code for filters, data fetching, etc.]
// Get selected month, year, employee filter, and department filter
$selectedMonth = isset($_POST['month']) ? intval($_POST['month']) : date('n');
$selectedYear = isset($_POST['year']) ? intval($_POST['year']) : date('Y');
$selectedDepartment = isset($_POST['department']) ? intval($_POST['department']) : 0;
$selectedEmployeeId = isset($_POST['employee_id']) ? intval($_POST['employee_id']) : 0;
$selectedEmployeeCode = isset($_POST['employee_code']) ? $_POST['employee_code'] : '';

// Validate month and year
$selectedMonth = max(1, min(12, $selectedMonth));
$selectedYear = max(2000, min(2099, $selectedYear));

// Calculate start and end dates of the month
$monthStart = new DateTime("$selectedYear-$selectedMonth-01");
$monthEnd = clone $monthStart;
$monthEnd->modify('last day of this month');

// Public holidays array
$publicHolidays = [
    // [Keep your existing public holidays array]
    // ...
];

// Fetch unique departments for dropdown
$departmentsQuery = "SELECT id, name FROM department_list WHERE status = 1";
$departmentsResult = $conn->query($departmentsQuery);
$departments = [];
while ($department = $departmentsResult->fetch_assoc()) {
    $departments[] = $department;
}

// Fetch employees with their departments
$employeesQuery = "
    SELECT e.id, e.fullname, e.employee_code, e.department_id, d.name AS department_name
    FROM employee_list e
    LEFT JOIN department_list d ON e.department_id = d.id
    WHERE e.status = 1
    ORDER BY e.fullname ASC
";

$employeesResult = $conn->query($employeesQuery);
$employees = [];
while ($employee = $employeesResult->fetch_assoc()) {
    $employees[] = $employee;
}

// Prepare data for each employee's calendar
$employeeCalendars = [];

foreach ($employees as $employee) {
    // [Keep your existing calendar generation logic]
    // ...
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Attendance Overview</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* [Keep all your existing styles] */
        
        /* Add PDF button style */
        .pdf-btn {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
            transition: all 0.3s;
        }
        .pdf-btn:hover {
            background: #c0392b;
            transform: translateY(-2px);
        }
        .pdf-btn i {
            margin-right: 5px;
        }
    </style>
</head>
<body>
    <div class="container">
        <img src="reports/Progression-Butterfly.png" alt="Company Logo" class="logo"> 
        <h1>Monthly Attendance Overview</h1>
        
        <form method="post" class="search-box">
            <!-- [Keep your existing filter form] -->
        </form>

        <?php foreach ($employeeCalendars as $fullname => $data): ?>
            <div class="employee-calendar" id="calendar-<?= $data['employee_id'] ?>">
                <h3 style="font-size: 1.3em; margin-bottom: 10px; color: #7f8c8d;">
                    <?= $fullname ?> <span style="color: #7f8c8d; font-size: 0.9em;">(<?= $data['employee_code'] ?>)</span>
                    <div style="font-size: 0.8em; color: #7f8c8d;"><?= $data['department_name'] ?></div>
                </h3>
                
                <div class="print-summary" style="margin-bottom: 15px;">
                    <div style="display: flex; gap: 15px; font-size: 0.9em;">
                        <div><strong style="color: #7f8c8d;">Month:</strong> <?= date('F Y', mktime(0, 0, 0, $selectedMonth, 1)) ?></div>
                    </div>
                </div>
                
                <div class="calendar">
                    <!-- [Keep your existing calendar grid] -->
                </div>
                
                <!-- Add PDF button form -->
                <form method="post" class="pdf-form">
                    <input type="hidden" name="generate_pdf" value="1">
                    <input type="hidden" name="employee_id" value="<?= $data['employee_id'] ?>">
                    <input type="hidden" name="month" value="<?= $selectedMonth ?>">
                    <input type="hidden" name="year" value="<?= $selectedYear ?>">
                    <input type="hidden" name="html_content" class="html-content">
                    <button type="button" class="pdf-btn" onclick="generatePDF(this)">
                        <i class="fas fa-file-pdf"></i> Print to PDF
                    </button>
                </form>
            </div>
        <?php endforeach; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function generatePDF(button) {
        const form = button.closest('.pdf-form');
        const calendarId = form.closest('.employee-calendar').id;
        const calendarElement = document.getElementById(calendarId);
        
        // Clone the calendar to avoid modifying the original
        const calendarClone = calendarElement.cloneNode(true);
        
        // Remove the PDF button from the clone
        const pdfButton = calendarClone.querySelector('.pdf-form');
        if (pdfButton) pdfButton.remove();
        
        // Add print-specific styles
        const printStyles = `
            <style>
                @page { margin: 20px; }
                body { font-size: 12px; padding: 10px; }
                .calendar div { padding: 6px; font-size: 0.8em; }
                .pdf-btn { display: none !important; }
                .search-box { display: none; }
            </style>
        `;
        
        // Create the HTML content for PDF
        const htmlContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Attendance Report - <?= $fullname ?></title>
                ${printStyles}
            </head>
            <body>
                ${calendarClone.innerHTML}
            </body>
            </html>
        `;
        
        // Set the HTML content in the form
        form.querySelector('.html-content').value = htmlContent;
        
        // Show loading state
        button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Generating...';
        button.disabled = true;
        
        // Submit the form
        form.submit();
        
        // Reset button after a delay (in case form submission fails)
        setTimeout(() => {
            button.innerHTML = '<i class="fas fa-file-pdf"></i> Print to PDF';
            button.disabled = false;
        }, 3000);
    }
    </script>
</body>
</html>