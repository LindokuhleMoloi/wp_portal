<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Employee Dashboard</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link href="https://fonts.googleapis.com/css?family=Pacifico" rel="stylesheet">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
      transition: all .15s;
      outline: none;
    }

    html, body {
      height: 100%;
      font-family: 'Montserrat', sans-serif;
    }

    body {
      background: url('access.png') no-repeat center center;
      background-size: contain;
      background-color: #000;
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;
      text-align: center;
      padding: 20px;
    }

    h1 {
      font-size: 3rem;
      text-transform: uppercase;
      margin-bottom: 40px;
      color: #ffffff;
    }

    h1 span {
      font-weight: normal;
    }

    h1 strong {
      font-weight: 800;
    }

    .input-container {
      position: relative;
      background-color: #39a5dd;
      padding: 30px;
      border-radius: 12px;
      width: 100%;
      max-width: 700px;
      box-shadow: 0 0 10px rgba(0, 0, 0, 0.2);
      margin-bottom: 25px;
      overflow: hidden;
    }

    .input-container::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      height: 100%;
      width: 40%;
      background: linear-gradient(to right, rgba(0, 0, 0, 0.25), transparent);
      z-index: 1;
      border-radius: 12px 0 0 12px;
    }

    .input-container h2 {
      color: white;
      font-size: 1.3rem;
      margin-bottom: 15px;
      position: relative;
      z-index: 2;
    }

    input[type="text"] {
      width: 100%;
      padding: 14px;
      border: none;
      border-radius: 6px;
      background-color: white;
      font-size: 1rem;
      text-align: center;
      position: relative;
      z-index: 2;
    }

    /* Original Button Styles */
    .btn {
      background-color: #e84e17;
      color: white;
      border: none;
      padding: 16px 40px 16px 16px;
      width: 100%;
      max-width: 700px;
      font-size: 1rem;
      font-weight: 600;
      border-radius: 6px;
      margin-bottom: 15px;
      cursor: pointer;
      text-transform: uppercase;
      position: relative;
      transition: all 0.3s ease;
    }

    .btn:hover {
      background-color: #d64510;
    }

    /* Paper Plane Icon Styles */
    .btn svg {
      position: absolute;
      top: 50%;
      right: 15px;
      transform: translateY(-50%);
      height: 24px;
      width: auto;
      transition: transform .15s;
    }

    .btn svg path {
      fill: white;
    }

    .btn:hover svg {
      transform: translateY(-50%) rotate(10deg);
    }

    /* Clicked State */
    .btn.clicked {
      background-color: #6AAA3B;
      padding-right: 16px;
      cursor: default;
    }

    .btn.clicked svg {
      animation: flyaway 1.3s linear;
      top: -80px;
      right: -1000px;
    }

    @keyframes flyaway {
      0%   { transform: translateY(-50%) rotate(10deg);
            top: 50%;
            right: 15px;
            height: 24px; }
      5%   { transform: translateY(-50%) rotate(10deg);
            top: 50%;
            right: 0px;
            height: 24px; }
      20%  { transform: translateY(-50%) rotate(-20deg);
            top: 50%;
            right: -130px;
            height: 36px; }  
      40%  { transform: translateY(-50%) rotate(10deg);
            top: -40px;
            right: -280px;
            opacity: 1; }
      100% { transform: translateY(-50%) rotate(60deg);
            top: -200px;
            right: -1000px;
            height: 0;
            opacity: 0; }
    }

    @media (max-width: 768px) {
      h1 {
        font-size: 2.2rem;
      }

      .input-container, .btn {
        max-width: 100%;
      }
    }
  </style>
</head>

<body>

  <h1><span>Employee</span> <strong>Dashboard</strong></h1>

  <div class="input-container">
    <h2>Enter your employee code to view your Attendance</h2>
    <input type="text" id="employee_code" placeholder="e.g., 2023-0001" />
  </div>

  <button id="checkAttendanceBtn" class="btn">
    Check Attendance
    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" enable-background="new 0 0 512 512" xml:space="preserve">
      <path id="paper-plane-icon" d="M462,54.955L355.371,437.187l-135.92-128.842L353.388,167l-179.53,124.074L50,260.973L462,54.955z M202.992,332.528v124.517l58.738-67.927L202.992,332.528z"></path> 
    </svg>
  </button>
  
  <button class="btn" onclick="window.location.href='leave_login.php'">
    Leave Application
    <svg version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink" x="0px" y="0px" viewBox="0 0 512 512" enable-background="new 0 0 512 512" xml:space="preserve">
      <path id="paper-plane-icon" d="M462,54.955L355.371,437.187l-135.92-128.842L353.388,167l-179.53,124.074L50,260.973L462,54.955z M202.992,332.528v124.517l58.738-67.927L202.992,332.528z"></path> 
    </svg>
  </button>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script>
    $('#checkAttendanceBtn').click(function() {
      const code = document.getElementById("employee_code").value.trim();
      if (!code) {
        alert("Please enter your employee code.");
        return;
      }
      
      // Add clicked class and animate
      $(this).addClass('clicked');
      
      // After animation completes, redirect
      setTimeout(function() {
        window.location.href = `calendarview.php?employee_code=${encodeURIComponent(code)}`;
      }, 1300);
    });
  </script>
</body>
</html>