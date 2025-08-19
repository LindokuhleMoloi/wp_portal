<?php
// view_mentee.php
session_start();

// Redirect if not logged in as mentor
if (!isset($_SESSION['mentor_logged_in'])) {
    header("Location: mentor_login.php");
    exit();
}

// Check if mentee ID is provided
if (!isset($_GET['id'])) {
    header("Location: mentee_list.php");
    exit();
}

$mentee_id = $_GET['id'];

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "tarryn_workplaceportal";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get current mentor details
$mentor_id = $_SESSION['mentor_employee_id'];
$mentor_fullname = $_SESSION['mentor_fullname'];

// --- Fetch Mentee Details ---
$mentee = [];
$sql_mentee = "SELECT 
                    el.*, 
                    dl.name AS designation_name,
                    dpt.name AS department_name,
                    empl.name AS employer_name,
                    pm.fullname AS project_manager_name
                 FROM employee_list el
                 LEFT JOIN designation_list dl ON el.designation_id = dl.id
                 LEFT JOIN department_list dpt ON el.department_id = dpt.id
                 LEFT JOIN employer_list empl ON el.employer_id = empl.id
                 LEFT JOIN employee_list pm ON el.project_manager_id = pm.id
                 WHERE el.id = ? AND el.mentor_id = ?";
$stmt_mentee = $conn->prepare($sql_mentee);
if ($stmt_mentee) {
    $stmt_mentee->bind_param("ii", $mentee_id, $mentor_id);
    $stmt_mentee->execute();
    $result_mentee = $stmt_mentee->get_result();
    if ($result_mentee->num_rows > 0) {
        $mentee = $result_mentee->fetch_assoc();
    } else {
        // Mentee not found or doesn't belong to this mentor
        header("Location: mentee_list.php");
        exit();
    }
    $stmt_mentee->close();
}

// --- Fetch Mentee Leave History ---
$leave_history = [];
$sql_leaves = "SELECT 
                  la.*, 
                  lt.name AS leave_type,
                  DATEDIFF(la.end_date, la.start_date) + 1 AS days_taken
               FROM leave_applications la
               JOIN leave_types lt ON la.leave_type_id = lt.id
               WHERE la.employee_id = ?
               ORDER BY la.start_date DESC";
$stmt_leaves = $conn->prepare($sql_leaves);
if ($stmt_leaves) {
    $stmt_leaves->bind_param("i", $mentee_id);
    $stmt_leaves->execute();
    $result_leaves = $stmt_leaves->get_result();
    if ($result_leaves->num_rows > 0) {
        while($row = $result_leaves->fetch_assoc()) {
            $leave_history[] = $row;
        }
    }
    $stmt_leaves->close();
}

// --- Fetch Mentorship Notes ---
$mentorship_notes = [];
$sql_notes = "SELECT * FROM mentorship_notes 
              WHERE mentee_id = ? AND mentor_id = ?
              ORDER BY created_at DESC";
$stmt_notes = $conn->prepare($sql_notes);
if ($stmt_notes) {
    $stmt_notes->bind_param("ii", $mentee_id, $mentor_id);
    $stmt_notes->execute();
    $result_notes = $stmt_notes->get_result();
    if ($result_notes->num_rows > 0) {
        while($row = $result_notes->fetch_assoc()) {
            $mentorship_notes[] = $row;
        }
    }
    $stmt_notes->close();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Mentee Details</title>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
  <style>
    :root {
      --primary-color: #0e6574;
      --secondary-color: #e84e17;
      --accent-color: #2fa8e0;
      --dark-color: #0b3e4d;
      --light-color: #f8f9fa;
      --success-color: #28a745;
      --danger-color: #dc3545;
      --warning-color: #ffc107;
    }
    
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    html, body {
      height: 100%;
      font-family: 'Montserrat', sans-serif;
    }

    body {
      background: url('system-image.png') no-repeat center center fixed;
      background-size: cover;
      background-color: #000;
      display: flex;
      flex-direction: column;
      padding: 15px;
      position: relative;
    }

    .logo {
      position: absolute;
      top: 20px;
      right: 20px;
      width: 80px;
      height: 60px;
      display: flex;
      justify-content: center;
      align-items: center;
      gap: 6px;
    }

    .logo-letter {
      font-family: 'Montserrat', sans-serif;
      font-weight: 800;
      font-size: 2.8rem;
      color: white;
      transition: transform 0.3s ease;
    }

    .logo-letter.w {
      transform: translateY(-2px);
    }

    .logo-letter.p {
      transform: translateY(2px);
    }

    .logo:hover .logo-letter.w {
      transform: translateY(-4px);
    }

    .logo:hover .logo-letter.p {
      transform: translateY(4px);
    }

    .header-strip {
      background: linear-gradient(to right, var(--dark-color), var(--primary-color));
      padding: 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin: -15px -15px 30px -15px;
      width: calc(100% + 30px);
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
    }

    .header-title {
      color: white;
      font-size: 2.2rem;
      font-weight: 700;
      text-transform: uppercase;
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .header-title i {
      font-size: 1.8rem;
    }

    .welcome-message {
      color: white;
      font-size: 1.5rem;
      font-weight: 600;
      text-align: right;
    }

    .welcome-message span {
      color: var(--accent-color);
    }

    .header-buttons {
      display: flex;
      gap: 15px;
    }

    .header-btn {
      background-color: white;
      color: var(--dark-color);
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .header-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
    }

    .header-btn i {
      font-size: 1rem;
    }

    /* Main Content */
    .content-container {
      background-color: rgba(255, 255, 255, 0.95);
      border-radius: 12px;
      box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
      padding: 25px;
      margin-bottom: 30px;
    }

    /* Mentee Profile Header */
    .profile-header {
      display: flex;
      align-items: center;
      gap: 25px;
      margin-bottom: 30px;
      padding-bottom: 25px;
      border-bottom: 2px solid var(--light-color);
    }

    .profile-avatar {
      width: 120px;
      height: 120px;
      border-radius: 50%;
      background-color: var(--primary-color);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 3rem;
      font-weight: 700;
      flex-shrink: 0;
    }

    .profile-info h2 {
      font-size: 1.8rem;
      color: var(--dark-color);
      margin-bottom: 5px;
    }

    .profile-meta {
      display: flex;
      flex-wrap: wrap;
      gap: 15px;
      margin-top: 15px;
    }

    .meta-item {
      display: flex;
      align-items: center;
      gap: 8px;
      font-size: 0.9rem;
      color: #555;
    }

    .meta-item i {
      color: var(--accent-color);
    }

    .profile-status {
      display: inline-block;
      padding: 5px 12px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
      margin-left: 10px;
    }

    .status-active {
      background-color: rgba(40, 167, 69, 0.2);
      color: var(--success-color);
    }

    .status-inactive {
      background-color: rgba(220, 53, 69, 0.2);
      color: var(--danger-color);
    }

    /* Mentee Details Sections */
    .details-section {
      margin-bottom: 30px;
    }

    .section-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid var(--light-color);
    }

    .section-title {
      font-size: 1.4rem;
      font-weight: 700;
      color: var(--dark-color);
      display: flex;
      align-items: center;
      gap: 10px;
    }

    .section-title i {
      color: var(--primary-color);
    }

    .add-note-btn {
      background-color: var(--accent-color);
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 5px;
      font-size: 0.85rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.2s ease;
      display: flex;
      align-items: center;
      gap: 5px;
    }

    .add-note-btn:hover {
      background-color: #1e96c8;
      transform: translateY(-2px);
    }

    /* Details Grid */
    .details-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
      gap: 20px;
    }

    .detail-card {
      background-color: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
    }

    .detail-label {
      font-size: 0.8rem;
      color: #666;
      margin-bottom: 5px;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .detail-value {
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--dark-color);
    }

    /* Notes Section */
    .notes-container {
      display: flex;
      flex-direction: column;
      gap: 15px;
    }

    .note-card {
      background-color: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
      position: relative;
    }

    .note-header {
      display: flex;
      justify-content: space-between;
      margin-bottom: 10px;
    }

    .note-date {
      font-size: 0.8rem;
      color: #666;
    }

    .note-actions {
      display: flex;
      gap: 10px;
    }

    .note-action-btn {
      background: none;
      border: none;
      color: #666;
      cursor: pointer;
      font-size: 0.9rem;
      transition: color 0.2s ease;
    }

    .note-action-btn:hover {
      color: var(--accent-color);
    }

    .note-content {
      font-size: 0.95rem;
      line-height: 1.6;
      color: #333;
    }

    /* Leave History Table */
    .leave-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 15px;
    }

    .leave-table th {
      background-color: var(--light-color);
      color: var(--dark-color);
      padding: 12px;
      text-align: left;
      font-weight: 600;
      font-size: 0.9rem;
    }

    .leave-table td {
      padding: 12px;
      border-bottom: 1px solid #eee;
      font-size: 0.9rem;
    }

    .leave-table tr:last-child td {
      border-bottom: none;
    }

    .leave-status {
      display: inline-block;
      padding: 4px 10px;
      border-radius: 20px;
      font-size: 0.8rem;
      font-weight: 600;
    }

    .status-pending {
      background-color: rgba(255, 193, 7, 0.2);
      color: var(--warning-color);
    }

    .status-approved {
      background-color: rgba(40, 167, 69, 0.2);
      color: var(--success-color);
    }

    .status-rejected {
      background-color: rgba(220, 53, 69, 0.2);
      color: var(--danger-color);
    }

    .no-leaves, .no-notes {
      text-align: center;
      padding: 30px;
      color: #666;
      font-style: italic;
    }

    /* Back Button */
    .back-btn {
      display: inline-block;
      margin-top: 20px;
      background-color: var(--light-color);
      color: var(--dark-color);
      border: none;
      padding: 10px 20px;
      border-radius: 8px;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      text-decoration: none;
      display: flex;
      align-items: center;
      gap: 8px;
    }

    .back-btn:hover {
      background-color: #e9ecef;
      transform: translateY(-2px);
    }

    /* Modal Styles */
    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      z-index: 1000;
      justify-content: center;
      align-items: center;
    }

    .modal-content {
      background-color: white;
      border-radius: 10px;
      width: 90%;
      max-width: 600px;
      padding: 25px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      padding-bottom: 10px;
      border-bottom: 1px solid #eee;
    }

    .modal-title {
      font-size: 1.5rem;
      font-weight: 700;
      color: var(--dark-color);
    }

    .close-modal {
      background: none;
      border: none;
      font-size: 1.5rem;
      cursor: pointer;
      color: #666;
    }

    .note-form textarea {
      width: 100%;
      min-height: 150px;
      padding: 10px;
      border: 1px solid #ddd;
      border-radius: 5px;
      font-family: inherit;
      resize: vertical;
    }

    .form-actions {
      display: flex;
      justify-content: flex-end;
      gap: 10px;
      margin-top: 20px;
    }

    .cancel-btn {
      background-color: #f8f9fa;
      color: #333;
      border: none;
      padding: 8px 15px;
      border-radius: 5px;
      cursor: pointer;
    }

    .save-btn {
      background-color: var(--accent-color);
      color: white;
      border: none;
      padding: 8px 15px;
      border-radius: 5px;
      cursor: pointer;
    }

    /* Responsive Adjustments */
    @media (max-width: 768px) {
      .header-strip {
        flex-direction: column;
        gap: 15px;
        padding: 20px;
        text-align: center;
      }
      
      .welcome-message {
        text-align: center;
      }
      
      .header-buttons {
        justify-content: center;
      }
      
      .profile-header {
        flex-direction: column;
        text-align: center;
      }
      
      .profile-meta {
        justify-content: center;
      }
      
      .logo {
        top: 15px;
        right: 15px;
        width: 60px;
        height: 50px;
      }
      
      .logo-letter {
        font-size: 2.2rem;
      }
      
      .leave-table {
        display: block;
        overflow-x: auto;
      }
    }
  </style>
</head>
<body>
  <div class="logo">
    <div class="logo-letter w">W</div>
    <div class="logo-letter p">P</div>
  </div>

  <div class="header-strip">
    <h1 class="header-title">
      <i class="fas fa-user-graduate"></i> MENTEE DETAILS
    </h1>
    <div class="welcome-message">
      Welcome, <span><?php echo htmlspecialchars($mentor_fullname); ?></span>
    </div>
    <div class="header-buttons">
      <a href="mentor_profile.php" class="header-btn">
        <i class="fas fa-user-cog"></i> Profile
      </a>
      <a href="mentor_logout.php" class="header-btn">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </div>

  <div class="content-container">
    <!-- Mentee Profile Header -->
    <div class="profile-header">
      <div class="profile-avatar">
        <?php echo strtoupper(substr($mentee['fullname'], 0, 1)); ?>
      </div>
      <div class="profile-info">
        <h2>
          <?php echo htmlspecialchars($mentee['fullname']); ?>
          <span class="profile-status <?php echo ($mentee['status'] == 1) ? 'status-active' : 'status-inactive'; ?>">
            <?php echo ($mentee['status'] == 1) ? 'Active' : 'Inactive'; ?>
          </span>
        </h2>
        <p style="color: var(--primary-color); font-weight: 600;">
          <?php echo htmlspecialchars($mentee['designation_name']); ?>
          <?php if (!empty($mentee['department_name'])): ?>
            • <?php echo htmlspecialchars($mentee['department_name']); ?>
          <?php endif; ?>
        </p>
        <div class="profile-meta">
          <div class="meta-item">
            <i class="fas fa-id-card"></i>
            <span><?php echo htmlspecialchars($mentee['employee_code']); ?></span>
          </div>
          <div class="meta-item">
            <i class="fas fa-building"></i>
            <span><?php echo htmlspecialchars($mentee['employer_name']); ?></span>
          </div>
          <div class="meta-item">
            <i class="fas fa-user-tie"></i>
            <span><?php echo htmlspecialchars($mentee['project_manager_name'] ?? 'Not assigned'); ?></span>
          </div>
          <div class="meta-item">
            <i class="fas fa-phone"></i>
            <span><?php echo htmlspecialchars($mentee['contact']); ?></span>
          </div>
          <div class="meta-item">
            <i class="fas fa-envelope"></i>
            <span><?php echo htmlspecialchars($mentee['email']); ?></span>
          </div>
        </div>
      </div>
    </div>

    <!-- Employment Details Section -->
    <div class="details-section">
      <div class="section-header">
        <h3 class="section-title">
          <i class="fas fa-briefcase"></i> Employment Details
        </h3>
      </div>
      <div class="details-grid">
        <div class="detail-card">
          <div class="detail-label">Contract Start Date</div>
          <div class="detail-value">
            <?php echo !empty($mentee['contract_start_date']) ? date('M j, Y', strtotime($mentee['contract_start_date'])) : 'Not specified'; ?>
          </div>
        </div>
        <div class="detail-card">
          <div class="detail-label">Contract End Date</div>
          <div class="detail-value">
            <?php echo !empty($mentee['contract_end_date']) ? date('M j, Y', strtotime($mentee['contract_end_date'])) : 'Ongoing'; ?>
          </div>
        </div>
        <div class="detail-card">
          <div class="detail-label">Date of Birth</div>
          <div class="detail-value">
            <?php echo !empty($mentee['date_of_birth']) ? date('M j, Y', strtotime($mentee['date_of_birth'])) : 'Not specified'; ?>
          </div>
        </div>
        <div class="detail-card">
          <div class="detail-label">Gender</div>
          <div class="detail-value">
            <?php echo !empty($mentee['gender']) ? htmlspecialchars($mentee['gender']) : 'Not specified'; ?>
          </div>
        </div>
      </div>
    </div>

    <!-- Mentorship Notes Section -->
    <div class="details-section">
      <div class="section-header">
        <h3 class="section-title">
          <i class="fas fa-clipboard"></i> Mentorship Notes
        </h3>
        <button class="add-note-btn" id="add-note-btn">
          <i class="fas fa-plus"></i> Add Note
        </button>
      </div>
      
      <?php if (!empty($mentorship_notes)): ?>
        <div class="notes-container">
          <?php foreach ($mentorship_notes as $note): ?>
            <div class="note-card">
              <div class="note-header">
                <div class="note-date">
                  <?php echo date('M j, Y \a\t g:i a', strtotime($note['created_at'])); ?>
                </div>
                <div class="note-actions">
                  <button class="note-action-btn edit-note" data-id="<?php echo $note['id']; ?>">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="note-action-btn delete-note" data-id="<?php echo $note['id']; ?>">
                    <i class="fas fa-trash"></i>
                  </button>
                </div>
              </div>
              <div class="note-content">
                <?php echo nl2br(htmlspecialchars($note['content'])); ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <div class="no-notes">
          <i class="fas fa-clipboard" style="font-size: 2rem; color: var(--accent-color); margin-bottom: 10px;"></i>
          <p>No mentorship notes found for this mentee</p>
        </div>
      <?php endif; ?>
    </div>

    <!-- Leave History Section -->
    <div class="details-section">
      <div class="section-header">
        <h3 class="section-title">
          <i class="fas fa-calendar-alt"></i> Leave History
        </h3>
      </div>
      
      <?php if (!empty($leave_history)): ?>
        <table class="leave-table">
          <thead>
            <tr>
              <th>Leave Type</th>
              <th>Dates</th>
              <th>Days</th>
              <th>Reason</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($leave_history as $leave): ?>
              <tr>
                <td><?php echo htmlspecialchars($leave['leave_type']); ?></td>
                <td>
                  <?php echo date('M j', strtotime($leave['start_date'])); ?> - 
                  <?php echo date('M j, Y', strtotime($leave['end_date'])); ?>
                </td>
                <td><?php echo $leave['days_taken']; ?></td>
                <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                <td>
                  <span class="leave-status status-<?php echo strtolower($leave['status']); ?>">
                    <?php echo htmlspecialchars($leave['status']); ?>
                  </span>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php else: ?>
        <div class="no-leaves">
          <i class="fas fa-calendar-check" style="font-size: 2rem; color: var(--accent-color); margin-bottom: 10px;"></i>
          <p>No leave history found for this mentee</p>
        </div>
      <?php endif; ?>
    </div>

    <a href="mentee_list.php" class="back-btn">
      <i class="fas fa-arrow-left"></i> Back to Mentee List
    </a>
  </div>

  <!-- Add/Edit Note Modal -->
  <div class="modal" id="note-modal">
    <div class="modal-content">
      <div class="modal-header">
        <h3 class="modal-title" id="modal-title">Add Mentorship Note</h3>
        <button class="close-modal">&times;</button>
      </div>
      <form id="note-form">
        <input type="hidden" id="note-id" name="note_id">
        <input type="hidden" name="mentee_id" value="<?php echo $mentee_id; ?>">
        <input type="hidden" name="mentor_id" value="<?php echo $mentor_id; ?>">
        <textarea name="note_content" id="note-content" placeholder="Enter your mentorship notes here..."></textarea>
        <div class="form-actions">
          <button type="button" class="cancel-btn close-modal">Cancel</button>
          <button type="submit" class="save-btn">Save Note</button>
        </div>
      </form>
    </div>
  </div>

  <script>
    // Simple animation on page load
    document.addEventListener('DOMContentLoaded', function() {
      const cards = document.querySelectorAll('.detail-card');
      cards.forEach((card, index) => {
        setTimeout(() => {
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        }, 100 * index);
      });
      
      const noteCards = document.querySelectorAll('.note-card');
      noteCards.forEach((card, index) => {
        setTimeout(() => {
          card.style.opacity = '1';
          card.style.transform = 'translateY(0)';
        }, 100 * (index + cards.length));
      });
      
      const leaveRows = document.querySelectorAll('.leave-table tbody tr');
      leaveRows.forEach((row, index) => {
        setTimeout(() => {
          row.style.opacity = '1';
          row.style.transform = 'translateY(0)';
        }, 100 * (index + cards.length + noteCards.length));
      });
    });

    // Note Modal Handling
    const noteModal = document.getElementById('note-modal');
    const addNoteBtn = document.getElementById('add-note-btn');
    const closeModalBtns = document.querySelectorAll('.close-modal');
    const modalTitle = document.getElementById('modal-title');
    const noteForm = document.getElementById('note-form');
    const noteIdField = document.getElementById('note-id');
    const noteContentField = document.getElementById('note-content');

    // Show modal for adding new note
    addNoteBtn.addEventListener('click', function() {
      modalTitle.textContent = 'Add Mentorship Note';
      noteIdField.value = '';
      noteContentField.value = '';
      noteModal.style.display = 'flex';
    });

    // Show modal for editing note
    document.querySelectorAll('.edit-note').forEach(btn => {
      btn.addEventListener('click', function() {
        const noteId = this.getAttribute('data-id');
        const noteCard = this.closest('.note-card');
        const noteContent = noteCard.querySelector('.note-content').textContent;
        
        modalTitle.textContent = 'Edit Mentorship Note';
        noteIdField.value = noteId;
        noteContentField.value = noteContent.trim();
        noteModal.style.display = 'flex';
      });
    });

    // Close modal
    closeModalBtns.forEach(btn => {
      btn.addEventListener('click', function() {
        noteModal.style.display = 'none';
      });
    });

    // Close modal when clicking outside
    window.addEventListener('click', function(event) {
      if (event.target === noteModal) {
        noteModal.style.display = 'none';
      }
    });

    // Handle note form submission
    noteForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      
      fetch('save_mentorship_note.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          location.reload();
        } else {
          alert('Error: ' + (data.message || 'Failed to save note'));
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred while saving the note');
      });
    });

    // Handle note deletion
    document.querySelectorAll('.delete-note').forEach(btn => {
      btn.addEventListener('click', function() {
        if (confirm('Are you sure you want to delete this note?')) {
          const noteId = this.getAttribute('data-id');
          
          fetch('delete_mentorship_note.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `note_id=${noteId}`
          })
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              location.reload();
            } else {
              alert('Error: ' + (data.message || 'Failed to delete note'));
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while deleting the note');
          });
        }
      });
    });
  </script>
</body>
</html>