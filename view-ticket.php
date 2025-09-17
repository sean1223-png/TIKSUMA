<?php
session_start();
if (!isset($_SESSION['username'])) {
    header("Location: index.php");
    exit;
}

error_reporting(E_ALL);
ini_set('display_errors', 1);

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Invalid ticket ID.");
}

include 'db_connect.php';

$username = $_SESSION['username'];
$ticket_id = intval($_GET['id']);

$stmt = $conn->prepare("
    SELECT t.ticket_id, t.subject, t.description, t.created_at, t.username,
           ts.status_name, pl.level_name AS priority
    FROM tickets t
    JOIN ticket_statuses ts ON t.status_id = ts.id
    JOIN priority_levels pl ON t.priority_id = pl.id
    WHERE t.ticket_id = ?
");
$stmt->bind_param("i", $ticket_id);
$stmt->execute();
$result = $stmt->get_result();
$ticket = $result->fetch_assoc();

$stmt->close();

// Access control: Allow Admin, IT Staff, or the ticket owner to view
if ($ticket) {
    $userType = $_SESSION['user_type'] ?? 'User'; // Default to 'User'
    $isOwner = $ticket['username'] === $_SESSION['username'];

    if (!in_array($userType, ['Admin', 'ITStaff']) && !$isOwner) {
        $ticket = null; // deny access
    }
}


// Fetch notes
$notes = [];
if ($ticket) {
    $notes_sql = "SELECT username, note, created_at FROM ticket_notes WHERE ticket_id = ? ORDER BY created_at DESC";
    $notes_stmt = $conn->prepare($notes_sql);
    $notes_stmt->bind_param("i", $ticket['ticket_id']);
    $notes_stmt->execute();
    $notes_result = $notes_stmt->get_result();
    $notes = $notes_result->fetch_all(MYSQLI_ASSOC);
    $notes_stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>TIKSUMA TICKET</title>
  <link rel="icon" href="./png/logo-favicon.ico" type="image/x-icon">
  <style>
      body {
      margin: 0;
      font-family: 'Segoe UI', sans-serif;
      background: linear-gradient(to bottom, #050609ff, #2e4964ff);      
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
    }

    .ticket-box {
      background: white;
      border-radius: 16px;
      padding: 40px;
      max-width: 100%;
      width:750px;
      box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
      position: relative;
    }
  

    .ticket-box h1 {
      text-align: center;
      color: #2d3e50;
      font-weight: bold;
      margin-bottom: 5px;
      font-size: 30px;
      font-weight: 800;
      letter-spacing: 1px;
    }

    .ticket-id {
      text-align: center;
      font-size: 14px;
      color: #555;
      margin-bottom: 20px;
    }

    .line {
      height: 1px;
      background-color: #ccc;
      margin: 10px 0 20px;
    }

    .field-label {
      font-weight: 600;
      margin-top: 15px;
      color: #444;
    }

    .field-value {
      margin: 4px 0 10px;
      color: #333;
    }
    .badge.low { background: green; }
    .badge.medium { background: orange; }
    .badge.high { background: red; }
    .badge.new { background: #007bff; }
    
    .badge {
      padding: 5px 12px;
      border-radius: 20px;
      color: white;
      font-size: 13px;
      gap: 5px;
      display: inline-block;
    }

    .print-btn {
      display: block;
      background: #4285f4;
      color: white;
      padding: 10px 30px;
      border: none;
      border-radius: 8px;
      font-size: 14px;
      margin: 30px auto 0;
      cursor: pointer;
      transition: 0.3s;
    }

    .print-btn:hover {
      background: #2c6cdf;
    }

    .close-btn {
      position: absolute;
      top: 20px;
      right: 25px;
      font-size: 18px;
      color: #555;
      cursor: pointer;
    }

    @media print {
      .close-btn, .print-btn {
        display: none;
      }

      body {
        background: white;
      }

      .ticket-box {
        box-shadow: none;
      }
    }
  </style>
</head>
<body>

<?php if ($ticket): ?>
  <div class="ticket-box">
    <div class="close-btn" onclick="window.history.back()">×</div>

    <h1>TIKSUMA</h1>
    <div class="ticket-id">Ticket ID: <?= str_pad($ticket['ticket_id'], 5, '0', STR_PAD_LEFT) ?></div>
    <hr>

    <div class="label">Issue Type:</div>
    <div class="value"><strong><?= htmlspecialchars($ticket['subject']) ?></strong></div>

    <div class="label">Description:</div>
    <div class="value"><?= nl2br(htmlspecialchars($ticket['description'])) ?></div>

    <div class="label">Priority:</div>
    <div class="value">
      <span class="badge <?= strtolower($ticket['priority']) ?>"><?= $ticket['priority'] ?></span>
    </div>

    <div class="label">Status:</div>
    <div class="value">
      <span class="badge new"><?= $ticket['status_name'] ?></span>
    </div>

    <div class="label">Date:</div>
    <div class="value"><?= date('m/d/Y', strtotime($ticket['created_at'])) ?></div>

    <!-- Ticket Notes Section -->
    <div class="label" style="margin-top: 20px;">Notes:</div>
    <div class="value" style="max-height: 200px; overflow-y: auto; background: #f1f1f1; padding: 10px; border-radius: 8px;">
      <?php if (!empty($notes)): ?>
        <ul style='list-style:none; padding-left:0; margin:0;'>
          <?php foreach ($notes as $note): ?>
            <li style='margin-bottom:10px; padding:8px; background:#fff; border-radius:5px; box-shadow: 0 1px 3px rgba(0,0,0,0.1);'>
              <strong><?= htmlspecialchars($note['username']) ?></strong> 
              <em style='color:#666; font-size:0.85em;'>(<?= htmlspecialchars($note['created_at']) ?>)</em><br>
              <?= nl2br(htmlspecialchars($note['note'])) ?>
            </li>
          <?php endforeach; ?>
        </ul>
      <?php else: ?>
        <p>No notes added yet.</p>
      <?php endif; ?>
    </div>

    <button class="print-btn" onclick="window.print()">Print</button>
  </div>
<?php else: ?>
  <div class="ticket-box">
    <h1>Ticket Not Found</h1>
    <p>The ticket does not exist or you do not have access.</p>
    <div style="text-align:center;">
      <button class="print-btn" onclick="window.location.href='it-tickets.php'">Back</button>
    </div>
  </div>
<?php endif; ?>

</body>
</html>
