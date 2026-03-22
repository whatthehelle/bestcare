<?php
session_start();
require_once '../database.php';

if (!isset($_SESSION['receptionist_id']) || $_SESSION['role'] !== 'receptionist') {
    header('Location: receptionist_login.php');
    exit;
}

// handle accept/reject lng sa receptionisr pag may request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $apt_id = intval($_POST['apt_id']);
    $action = $_POST['action'];

    if (in_array($action, ['Accepted', 'Rejected'])) {
        $stmt = $mysql->prepare("UPDATE appointments SET status = ? WHERE apt_id = ?");
        $stmt->bind_param("si", $action, $apt_id);
        $stmt->execute();
        $stmt->close();
    }

    header('Location: apt-rec.php');
    exit;
}

// fetch all appointments toh
$result = $mysql->query("
    SELECT a.apt_id, a.apt_date, a.apt_time, a.department, a.details, a.is_followup, a.status,
           p.firstname AS pat_firstname, p.lastname AS pat_lastname,
           d.firstname AS doc_firstname, d.lastname AS doc_lastname
    FROM appointments a
    LEFT JOIN patientregistration p ON a.patient_id = p.patient_id
    LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
    ORDER BY a.apt_date ASC, a.apt_time ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="profile-rec.css">
    <link rel="stylesheet" href="apt-rec.css">
    <title>Receptionist — Appointments</title>
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
            <li><a href="dashboard-rec.php">Dashboard</a></li>
            <li><a href="profile-rec.php">Profile</a></li>
            <li><a href="patient-rec.php">Patients</a></li>
            <li><a href="apt-rec.php">Appointments</a></li>
            <li><a href="records-rec.php">Medical Records</a></li>
            <li><a href="logout-rec.php">Logout</a></li>
        </ul>
    </nav>
</div>

<div class="main">
    <div class="main-content">

        <h3 class="h3title">Appointments</h3>
        <h5 class="h5title">(Manage and schedule patient appointments.)</h5>

        <div class="main-container">
            <div class="update-profile appt-panel">

                <div class="appt-toolbar">
                    <h5 class="formtitle">Appointment Requests</h5>
                </div>

                <div class="appt-filters name-fields">
                    <input class="appt-input" type="text" id="searchInput" placeholder="Search patient or doctor..." onkeyup="filterTable()">
                    <select class="appt-select" id="statusFilter" onchange="filterTable()">
                        <option value="All">All Status</option>
                        <option value="Pending">Pending</option>
                        <option value="Accepted">Accepted</option>
                        <option value="Rejected">Rejected</option>
                    </select>
                </div>

                <div class="table-wrap">
                    <table class="appt-table" id="apptTable">
                        <thead>
                            <tr>
                                <th>Patient</th>
                                <th>Doctor</th>
                                <th>Department</th>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Details</th>
                                <th>Follow-up</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="apptBody">
                            <?php if ($result->num_rows === 0): ?>
                                <tr>
                                    <td colspan="9" style="text-align:center; color:#aaa; font-size:10px;">No appointments found.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($row['pat_firstname'] . ' ' . $row['pat_lastname']) ?></td>
                                        <td>
                                            <?php if ($row['doc_firstname']): ?>
                                                Dr. <?= htmlspecialchars($row['doc_firstname'] . ' ' . $row['doc_lastname']) ?>
                                            <?php else: ?>
                                                <span style="color:#aaa;">Not specified</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['department']) ?></td>
                                        <td><?= date('M d, Y', strtotime($row['apt_date'])) ?></td>
                                        <td><?= htmlspecialchars($row['apt_time']) ?></td>
                                        <td><?= htmlspecialchars($row['details'] ?? '—') ?></td>
                                        <td><?= $row['is_followup'] ? 'Yes' : 'No' ?></td>
                                        <td>
                                            <?php
                                                $badge = match(strtolower($row['status'])) {
                                                    'pending'  => 'status-scheduled',
                                                    'accepted' => 'status-confirmed',
                                                    'rejected' => 'status-cancelled',
                                                    default    => 'status-scheduled'
                                                };
                                            ?>
                                            <span class="status-badge <?= $badge ?>"><?= htmlspecialchars($row['status']) ?></span>
                                        </td>
                                        <td class="action-cell">
                                            <?php if ($row['status'] === 'Pending'): ?>
                                                <form method="post" action="apt-rec.php" style="display:inline;">
                                                    <input type="hidden" name="apt_id" value="<?= $row['apt_id'] ?>">
                                                    <input type="hidden" name="action" value="Accepted">
                                                    <button type="submit" class="btn-arrived">Accept</button>
                                                </form>
                                                <form method="post" action="apt-rec.php" style="display:inline;">
                                                    <input type="hidden" name="apt_id" value="<?= $row['apt_id'] ?>">
                                                    <input type="hidden" name="action" value="Rejected">
                                                    <button type="submit" class="btn-cancel">Reject</button>
                                                </form>
                                            <?php else: ?>
                                                <span class="no-action">—</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

            </div>
        </div>
    </div>
</div>

<script>
    function filterTable() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const status = document.getElementById('statusFilter').value;
        const rows   = document.querySelectorAll('#apptBody tr');

        rows.forEach(row => {
            const text       = row.textContent.toLowerCase();
            const statusCell = row.querySelector('.status-badge');
            const rowStatus  = statusCell ? statusCell.textContent.trim() : '';

            const matchSearch = text.includes(search);
            const matchStatus = status === 'All' || rowStatus === status;

            row.style.display = matchSearch && matchStatus ? '' : 'none';
        });
    }
</script>

</body>
</html>