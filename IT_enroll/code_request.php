<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Request Employee Code</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Arial, sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
            background: linear-gradient(135deg, #ADD8E6, #98FB98);
            background-size: cover;
            background-position: center;
        }

        .container {
            background-color: #ffffff;
            padding: 35px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            width: 400px;
        }

        h1 {
            font-size: 1.8rem;
            color: #333;
            text-align: center;
            margin-bottom: 20px;
        }

        label {
            font-size: 1rem;
            color: #666;
        }

        input {
            width: 100%;
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 5px;
            border: 1px solid #ccc;
            background-color: #f9f9f9;
        }

        button {
            width: 100%;
            padding: 12px;
            background-color: #333;
            color: #ffffff;
            border: none;
            border-radius: 5px;
            font-size: 1.2rem;
            margin-top: 10px; /* Add some space above the button */
        }

        button:hover {
            background-color: #444;
        }

        .back-button {
            background-color: #007BFF; /* Blue color for the back button */
        }

        .back-button:hover {
            background-color: #0056b3; /* Darker blue on hover */
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Enter your Employee Code</h1>
        <form id="code-request-form" method="GET" action="dashboard.php">
            <label for="employee_code">Employee Code:</label>
            <input type="text" id="employee_code" name="employee_code" placeholder="Enter your employee code" required>
            <button type="submit">Check</button>
        </form>
        <button class="back-button" onclick="window.location.href='helpdesk_front.php'">Back</button>
    </div>
</body>
</html>