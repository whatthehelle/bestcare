<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['patient_id'])) {
    header('Location: login.php');
    exit;
}

$patient_id = $_SESSION['patient_id'];

// handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $record_type    = trim($_POST['records']);
    $document_type  = trim($_POST['document']);
    $date_admission = trim($_POST['date']);
    $date_discharge = trim($_POST['discharge']);

    $errors = [];

    if (empty($record_type) || empty($document_type) || empty($date_admission)) {
        $errors[] = "Please fill in all required fields.";
    }

    if (empty($errors)) {
        $stmt = $mysql->prepare("INSERT INTO medical_records (patient_id, record_type, document_type, date_admission, date_discharge, status) VALUES (?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("issss", $patient_id, $record_type, $document_type, $date_admission, $date_discharge);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Medical document request submitted successfully!";
            header('Location: records.php');
            exit;
        } else {
            $errors[] = "Database error. Please try again.";
        }
        $stmt->close();
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: records.php');
        exit;
    }
}

// fetch last accepted appointment date
$apt_stmt = $mysql->prepare("
    SELECT apt_date 
    FROM appointments 
    WHERE patient_id = ? AND status = 'Accepted'
    ORDER BY apt_date DESC 
    LIMIT 1
");
$apt_stmt->bind_param("i", $patient_id);
$apt_stmt->execute();
$apt_stmt->bind_result($last_apt_date);
$apt_stmt->fetch();
$apt_stmt->close();

// fetch request history
$history_stmt = $mysql->prepare("
    SELECT record_id, record_type, document_type, date_admission, date_discharge, status, file_path
    FROM medical_records
    WHERE patient_id = ?
    ORDER BY record_id DESC
");
$history_stmt->bind_param("i", $patient_id);
$history_stmt->execute();
$history_result = $history_stmt->get_result();

// fetch approved documents with files
$docs_stmt = $mysql->prepare("
    SELECT record_id, record_type, document_type, date_admission, status, file_path
    FROM medical_records
    WHERE patient_id = ? AND status = 'Approved' AND file_path IS NOT NULL
    ORDER BY record_id DESC
");
$docs_stmt->bind_param("i", $patient_id);
$docs_stmt->execute();
$docs_result = $docs_stmt->get_result();
$docs_count  = $docs_result->num_rows;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="records.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@100..900&display=swap');
    </style>
    <title>BestCare</title>
</head>
<body>

<div class="sidebar">
    <div class="container">
        <section class="logo">
            <img src="img/logo.png" alt="bclogo" class="bclogo">
            <div class="brand-name">
                <h1>BestCare</h1>
                <h4>Online Appointment System (OAS)</h4>
            </div>
        </section>
    </div>
    <nav class="sidebar-nav">
        <ul>
            <li><a href="home.php">Home</a></li>
            <li><a href="profile.php">Profile</a></li>
            <li><a href="appointment.php">Request Appointment</a></li>
            <li><a href="records.php">Request Medical Documents</a></li>
            <li><a href="viewconsult.php">View Consultation Request</a></li>
            <li><a href="viewapt.php">View Appointments</a></li>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </nav>
</div>

<div class="main">
    <header class="topbar">
        <h1>Medical Documents</h1>
    </header>

    <div class="main-content">

        <!-- MY DOCUMENTS BUTTON -->
        <div class="page-actions">
            <button class="btn-mydocs" onclick="openDocsModal()">
                📄 My Documents
                <?php if ($docs_count > 0): ?>
                    <span class="docs-count"><?= $docs_count ?></span>
                <?php endif; ?>
            </button>
        </div>

        <!-- REQUEST FORM -->
        <div class="main-container">
            <h3 class="h3title">Request Medical Documents</h3>
            <h5 class="h5title">(Please fill in your information accurately.)</h5>

            <?php if (!empty($_SESSION['success'])): ?>
                <p class="success-msg"><?= htmlspecialchars($_SESSION['success']) ?></p>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (!empty($_SESSION['errors'])): ?>
                <?php foreach ($_SESSION['errors'] as $error): ?>
                    <p class="error-msg"><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
                <?php unset($_SESSION['errors']); ?>
            <?php endif; ?>

            <form method="post" action="records.php" id="records-form" class="update-profile">

                <div class="name">
                    <h5 class="formtitle">Request for Medical Documents</h5>

                    <?php if ($last_apt_date): ?>
                        <div class="info-box">
                            📅 Your last accepted appointment was on
                            <strong><?= date('F d, Y', strtotime($last_apt_date)) ?></strong>.
                            This has been auto-filled as your date of last admission/consultation.
                        </div>
                    <?php endif; ?>

                    <div class="name-fields">
                        <div class="field">
                            <label>Select type of record:</label>
                            <select name="records" id="records">
                                <option value="">-- Select --</option>
                                <option value="Outpatient Record">Outpatient Record</option>
                                <option value="Inpatient Record">Inpatient Record</option>
                                <option value="Emergency Record">Emergency Record</option>
                            </select>
                        </div>

                        <div class="field">
                            <label>Select What Document to Request:</label>
                            <select name="document" id="document">
                                <option value="">-- Select --</option>
                                <option value="Birth Certificate">Birth Certificate</option>
                                <option value="Clinical Abstract">Clinical Abstract</option>
                                <option value="Medical Certificate">Medical Certificate</option>
                            </select>
                        </div>
                    </div>

                    <div class="name-fields">
                        <div class="field">
                            <label>Date of Last Admission/Consultation:</label>
                            <input type="date" id="date" name="date"
                                value="<?= $last_apt_date ?? '' ?>">
                            <?php if ($last_apt_date): ?>
                                <small style="font-size:10px; color:#a33535; font-family:'Poppins',sans-serif; margin-top:4px;">
                                    Auto-filled from your last accepted appointment. You may change this if needed.
                                </small>
                            <?php endif; ?>
                        </div>

                        <div class="field">
                            <label>Date of Last Discharge: <span style="color:#aaa; font-weight:300;">(if applicable)</span></label>
                            <input type="date" id="discharge" name="discharge">
                        </div>
                    </div>

                </div>

                <input type="submit" value="Submit Request">
                <span id="resspan" style="color:red; font-size:11px; font-family:'Poppins',sans-serif; margin-left:10px;"></span>

            </form>
        </div>

        <!-- REQUEST HISTORY -->
        <div class="history-section">
            <h3>My Document Requests</h3>
            <h5>Track the status of your medical document requests.</h5>

            <table class="history-table">
                <thead>
                    <tr>
                        <th>Record Type</th>
                        <th>Document Type</th>
                        <th>Date of Admission</th>
                        <th>Date of Discharge</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($history_result->num_rows === 0): ?>
                        <tr>
                            <td colspan="5" class="no-records">No document requests yet.</td>
                        </tr>
                    <?php else: ?>
                        <?php while ($row = $history_result->fetch_assoc()): ?>
                            <tr>
                                <td><?= htmlspecialchars($row['record_type']) ?></td>
                                <td><?= htmlspecialchars($row['document_type']) ?></td>
                                <td><?= $row['date_admission'] ? date('M d, Y', strtotime($row['date_admission'])) : '—' ?></td>
                                <td><?= $row['date_discharge'] ? date('M d, Y', strtotime($row['date_discharge'])) : '—' ?></td>
                                <td>
                                    <?php
                                        $badge = match(strtolower($row['status'])) {
                                            'pending'  => 'badge-pending',
                                            'approved' => 'badge-approved',
                                            'rejected' => 'badge-rejected',
                                            default    => 'badge-pending'
                                        };
                                    ?>
                                    <span class="badge <?= $badge ?>"><?= htmlspecialchars($row['status']) ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</div>

<!-- MY DOCUMENTS MODAL -->
<div class="modal-overlay" id="docsModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3>📄 My Documents</h3>
            <button class="modal-close" onclick="closeDocsModal()">✕</button>
        </div>
        <div class="modal-body">
            <?php if ($docs_count === 0): ?>
                <p class="no-docs">No documents available yet. Please wait for your request to be approved.</p>
            <?php else: ?>
                <?php
                    // reset pointer since we already iterated
                    $docs_result->data_seek(0);
                    while ($doc = $docs_result->fetch_assoc()):
                ?>
                    <div class="doc-card">
                        <div class="doc-info">
                            <h4><?= htmlspecialchars($doc['document_type']) ?></h4>
                            <p><?= htmlspecialchars($doc['record_type']) ?> — <?= $doc['date_admission'] ? date('M d, Y', strtotime($doc['date_admission'])) : '—' ?></p>
                        </div>
                        <div class="doc-actions">
                            <a href="<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="btn-view">View</a>
                            <a href="<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="btn-print" onclick="printDoc('<?= htmlspecialchars($doc['file_path']) ?>')">Print</a>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function openDocsModal() {
        document.getElementById('docsModal').classList.add('active');
    }

    function closeDocsModal() {
        document.getElementById('docsModal').classList.remove('active');
    }

    // close modal when clicking outside
    document.getElementById('docsModal').addEventListener('click', function(e) {
        if (e.target === this) closeDocsModal();
    });

    function printDoc(filePath) {
        const win = window.open(filePath, '_blank');
        win.addEventListener('load', function() {
            win.print();
        });
    }

    // form validation
    document.getElementById('records-form').addEventListener('submit', function(event) {
        const records  = document.getElementById('records').value;
        const document = document.getElementById('document').value;
        const date     = document.getElementById('date').value;
        const resspan  = document.getElementById('resspan');

        resspan.style.color = 'red';

        if (!records || !document || !date) {
            event.preventDefault();
            resspan.innerHTML = 'Please fill in all required fields.';
            return;
        }
    });
</script>

</body>
</html>