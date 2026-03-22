<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['patient_id'])) {
    header('Location: login.php');
    exit;
}

$patient_id = $_SESSION['patient_id'];
$today = date('Y-m-d');

// fetch upcoming appointments (today and future)
$upcoming_stmt = $mysql->prepare("
    SELECT a.apt_id, a.apt_date, a.apt_time, a.department, a.details, a.is_followup, a.status,
           d.firstname, d.lastname, d.specialization
    FROM appointments a
    LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.patient_id = ? AND a.apt_date >= ?
    ORDER BY a.apt_date ASC, a.apt_time ASC
");
$upcoming_stmt->bind_param("is", $patient_id, $today);
$upcoming_stmt->execute();
$upcoming_result = $upcoming_stmt->get_result();

// fetch past appointments (before today)
$past_stmt = $mysql->prepare("
    SELECT a.apt_id, a.apt_date, a.apt_time, a.department, a.details, a.is_followup, a.status,
           d.firstname, d.lastname, d.specialization
    FROM appointments a
    LEFT JOIN doctors d ON a.doctor_id = d.doctor_id
    WHERE a.patient_id = ? AND a.apt_date < ?
    ORDER BY a.apt_date DESC, a.apt_time ASC
");
$past_stmt->bind_param("is", $patient_id, $today);
$past_stmt->execute();
$past_result = $past_stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="profile.css">
    <link rel="stylesheet" href="viewapt.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@100..900&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&family=Heebo:wght@100..900&family=Liter&family=Quicksand:wght@300..700&family=Spectral&display=swap');
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
        <h1>Appointments</h1>
    </header>

    <div class="main-content">
        <div class="main-container">

            <h3 class="h3title">View Appointments</h3>
            <h5 class="h5title">(Track your upcoming and past appointments.)</h5>

            <div class="view-body">

                <div class="tab-row">
                    <button class="tab-btn active" onclick="switchTab('upcoming', this)">Upcoming</button>
                    <button class="tab-btn" onclick="switchTab('past', this)">Past</button>
                </div>

                <!-- UPCOMING -->
                <div class="tab-panel" id="tab-upcoming">
                    <h5 class="formtitle">Upcoming Appointments</h5>
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Department</th>
                                <th>Doctor</th>
                                <th>Details</th>
                                <th>Follow-up</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($upcoming_result->num_rows === 0): ?>
                                <tr>
                                    <td colspan="7" class="no-records">No upcoming appointments.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($row = $upcoming_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($row['apt_date'])) ?></td>
                                        <td><?= htmlspecialchars($row['apt_time']) ?></td>
                                        <td><?= htmlspecialchars($row['department']) ?></td>
                                        <td>
                                            <?php if ($row['firstname']): ?>
                                                Dr. <?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?>
                                                <br>
                                                <small style="color:#a33535; font-size:9px;"><?= htmlspecialchars($row['specialization']) ?></small>
                                            <?php else: ?>
                                                <span style="color:#aaa;">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['details'] ?? '—') ?></td>
                                        <td><?= $row['is_followup'] ? 'Yes' : 'No' ?></td>
                                        <td>
                                            <?php
                                                $status = $row['status'];
                                                $badge  = match(strtolower($status)) {
                                                    'pending'   => 'badge-pending',
                                                    'accepted'  => 'badge-approved',
                                                    'completed' => 'badge-completed',
                                                    'rejected'  => 'badge-cancelled',
                                                    default     => 'badge-pending'
                                                };
                                            ?>
                                            <span class="badge <?= $badge ?>"><?= htmlspecialchars($status) ?></span>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAST -->
                <div class="tab-panel" id="tab-past" style="display:none;">
                    <h5 class="formtitle">Past Appointments</h5>
                    <table class="requests-table">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Time</th>
                                <th>Department</th>
                                <th>Doctor</th>
                                <th>Details</th>
                                <th>Follow-up</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($past_result->num_rows === 0): ?>
                                <tr>
                                    <td colspan="7" class="no-records">No past appointments.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($row = $past_result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= date('M d, Y', strtotime($row['apt_date'])) ?></td>
                                        <td><?= htmlspecialchars($row['apt_time']) ?></td>
                                        <td><?= htmlspecialchars($row['department']) ?></td>
                                        <td>
                                            <?php if ($row['firstname']): ?>
                                                Dr. <?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?>
                                                <br>
                                                <small style="color:#a33535; font-size:9px;"><?= htmlspecialchars($row['specialization']) ?></small>
                                            <?php else: ?>
                                                <span style="color:#aaa;">Not assigned</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['details'] ?? '—') ?></td>
                                        <td><?= $row['is_followup'] ? 'Yes' : 'No' ?></td>
                                        <td>
                                            <?php
                                                $status = $row['status'];
                                                $badge  = match(strtolower($status)) {
                                                    'pending'   => 'badge-pending',
                                                    'accepted'  => 'badge-approved',
                                                    'completed' => 'badge-completed',
                                                    'rejected'  => 'badge-cancelled',
                                                    default     => 'badge-pending'
                                                };
                                            ?>
                                            <span class="badge <?= $badge ?>"><?= htmlspecialchars($status) ?></span>
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
    function switchTab(tab, btn) {
        document.querySelectorAll('.tab-panel').forEach(function(p) {
            p.style.display = 'none';
        });
        document.querySelectorAll('.tab-btn').forEach(function(b) {
            b.classList.remove('active');
        });
        document.getElementById('tab-' + tab).style.display = 'block';
        btn.classList.add('active');
    }
</script>

</body>
</html>