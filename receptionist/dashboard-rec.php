<?php
session_start();
require_once '../database.php';

if (!isset($_SESSION['receptionist_id']) || $_SESSION['role'] !== 'receptionist') {
    header('Location: receptionist_login.php');
    exit;
}

$today = date('Y-m-d');

// pangcount toh ng info card 
$total_patients = $mysql->query("SELECT COUNT(*) FROM patientregistration")->fetch_row()[0];
$total_today    = $mysql->query("SELECT COUNT(*) FROM appointments WHERE apt_date = '$today'")->fetch_row()[0];
$total_pending  = $mysql->query("SELECT COUNT(*) FROM appointments WHERE status = 'Pending'")->fetch_row()[0];
$total_accepted = $mysql->query("SELECT COUNT(*) FROM appointments WHERE status = 'Accepted'")->fetch_row()[0];
$total_rejected = $mysql->query("SELECT COUNT(*) FROM appointments WHERE status = 'Rejected'")->fetch_row()[0];

// today's appointments lng
$today_result = $mysql->query("
    SELECT a.apt_id, a.apt_date, a.apt_time, a.department, a.status,
           p.firstname AS pat_firstname, p.lastname AS pat_lastname,
           d.firstname AS doc_firstname, d.lastname AS doc_lastname
    FROM appointments a
    LEFT JOIN patientregistration p ON a.patient_id = p.patient_id
    LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.apt_date = '$today'
    ORDER BY a.apt_time ASC
");

// pending appointments toh
$pending_result = $mysql->query("
    SELECT a.apt_id, a.apt_date, a.apt_time, a.department, a.status,
           p.firstname AS pat_firstname, p.lastname AS pat_lastname,
           d.firstname AS doc_firstname, d.lastname AS doc_lastname
    FROM appointments a
    LEFT JOIN patientregistration p ON a.patient_id = p.patient_id
    LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.status = 'Pending'
    ORDER BY a.apt_date ASC, a.apt_time ASC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="profile-rec.css">
    <link rel="stylesheet" href="dashboard-rec.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap');

        
    </style>
    <title>Receptionist — Dashboard</title>
</head>
<body>

<div class="sidebar">
    <div class="container">
        <section class="logo">
            <img src="../img/logo.png" alt="bclogo" class="bclogo">
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

    <div class="topbar">
        <div>
            <h1>Dashboard</h1>
            <p>Welcome back, <?= htmlspecialchars($_SESSION['receptionist_name']) ?>! Here's today's overview.</p>
        </div>
    </div>

    <div class="dashboard-content">

        <div class="cards-row">

            <div class="info-card card-today">
                <span class="card-label">Appointments Today</span>
                <span class="card-count"><?= $total_today ?></span>
                <span class="card-sub"><?= date('F d, Y') ?></span>
            </div>

            <div class="info-card card-pending">
                <span class="card-label">Pending</span>
                <span class="card-count"><?= $total_pending ?></span>
                <span class="card-sub">Awaiting approval</span>
            </div>

            <div class="info-card card-accepted">
                <span class="card-label">Accepted</span>
                <span class="card-count"><?= $total_accepted ?></span>
                <span class="card-sub">Approved appointments</span>
            </div>

            <div class="info-card card-rejected">
                <span class="card-label">Rejected</span>
                <span class="card-count"><?= $total_rejected ?></span>
                <span class="card-sub">Declined appointments</span>
            </div>

            <div class="info-card card-patients">
                <span class="card-label">Total Patients</span>
                <span class="card-count"><?= $total_patients ?></span>
                <span class="card-sub">Registered patients</span>
            </div>

        </div>

        <div class="dashboard-table-wrap">
            <p class="section-title">Today's Appointments</p>
            <p class="section-sub">All appointments scheduled for today — <?= date('F d, Y') ?></p>

            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Department</th>
                        <th>Time</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($today_result->num_rows === 0): ?>
                        <tr><td colspan="5" class="no-records">No appointments today.</td></tr>
                    <?php else: ?>
                        <?php while ($row = $today_result->fetch_assoc()): ?>
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
                                <td><?= htmlspecialchars($row['apt_time']) ?></td>
                                <td>
                                    <?php
                                        $badge = match(strtolower($row['status'])) {
                                            'pending'  => 'badge-pending',
                                            'accepted' => 'badge-accepted',
                                            'rejected' => 'badge-rejected',
                                            default    => 'badge-pending'
                                        };
                                    ?>
                                    <span class="status-badge <?= $badge ?>"><?= htmlspecialchars($row['status']) ?></span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="apt-rec.php" class="view-all-link">View all appointments →</a>
        </div>

        <div class="dashboard-table-wrap">
            <p class="section-title">Pending Appointments</p>
            <p class="section-sub">Appointments waiting for your approval</p>

            <table class="dash-table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Doctor</th>
                        <th>Department</th>
                        <th>Date</th>
                        <th>Time</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($pending_result->num_rows === 0): ?>
                        <tr><td colspan="6" class="no-records">No pending appointments.</td></tr>
                    <?php else: ?>
                        <?php while ($row = $pending_result->fetch_assoc()): ?>
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
                                <td style="display:flex; gap:6px;">
                                    <form method="post" action="apt-rec.php">
                                        <input type="hidden" name="apt_id" value="<?= $row['apt_id'] ?>">
                                        <input type="hidden" name="action" value="Accepted">
                                        <button type="submit" style="background:#22c55e; color:#fff; border:none; padding:3px 10px; border-radius:4px; font-size:9px; cursor:pointer; font-family:'Poppins',sans-serif;">Accept</button>
                                    </form>
                                    <form method="post" action="apt-rec.php">
                                        <input type="hidden" name="apt_id" value="<?= $row['apt_id'] ?>">
                                        <input type="hidden" name="action" value="Rejected">
                                        <button type="submit" style="background:#ef4444; color:#fff; border:none; padding:3px 10px; border-radius:4px; font-size:9px; cursor:pointer; font-family:'Poppins',sans-serif;">Reject</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            <a href="apt-rec.php" class="view-all-link">View all appointments →</a>
        </div>

    </div>
</div>

</body>
</html>