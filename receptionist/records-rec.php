<?php
session_start();
require_once '../database.php';

if (!isset($_SESSION['receptionist_id']) || $_SESSION['role'] !== 'receptionist') {
    header('Location: receptionist_login.php');
    exit;
}

// handle upload and approve
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $record_id = intval($_POST['record_id']);
    $action    = $_POST['action'];

    if ($action === 'approve' && isset($_FILES['document_file'])) {
        $file     = $_FILES['document_file'];
        $ext      = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed  = ['pdf'];

        if (!in_array($ext, $allowed)) {
            $_SESSION['errors'] = ["Only PDF files are allowed."];
            header('Location: records-rec.php');
            exit;
        }

        if ($file['size'] > 10 * 1024 * 1024) {
            $_SESSION['errors'] = ["File size must not exceed 10MB."];
            header('Location: records-rec.php');
            exit;
        }

        $filename  = 'record_' . $record_id . '_' . time() . '.pdf';
        $uploadDir = '../uploads/medical_records/';
        $uploadPath = $uploadDir . $filename;

        if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
            $filePath = 'uploads/medical_records/' . $filename;
            $stmt = $mysql->prepare("UPDATE medical_records SET status = 'Approved', file_path = ? WHERE record_id = ?");
            $stmt->bind_param("si", $filePath, $record_id);
            $stmt->execute();
            $stmt->close();
            $_SESSION['success'] = "Document uploaded and approved successfully!";
        } else {
            $_SESSION['errors'] = ["Failed to upload file. Check folder permissions."];
        }

    } elseif ($action === 'reject') {
        $stmt = $mysql->prepare("UPDATE medical_records SET status = 'Rejected' WHERE record_id = ?");
        $stmt->bind_param("i", $record_id);
        $stmt->execute();
        $stmt->close();
        $_SESSION['success'] = "Request rejected.";
    }

    header('Location: records-rec.php');
    exit;
}

// fetch all record requests
$result = $mysql->query("
    SELECT mr.record_id, mr.record_type, mr.document_type, mr.date_admission,
           mr.date_discharge, mr.status, mr.file_path,
           pr.firstname, pr.lastname
    FROM medical_records mr
    LEFT JOIN patientregistration pr ON mr.patient_id = pr.patient_id
    ORDER BY mr.record_id DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="profile-rec.css">
    <link rel="stylesheet" href="records-rec.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap');
    </style>
    <title>Receptionist — Medical Records</title>
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
    <div class="main-content">

        <h3 class="h3title">Medical Record Requests</h3>
        <h5 class="h5title">(Review and upload documents for patients.)</h5>

        <div class="main-container">
            <div class="update-profile records-panel">

                <?php if (!empty($_SESSION['success'])): ?>
                    <p class="success-msg"><?= htmlspecialchars($_SESSION['success']) ?></p>
                    <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <?php if (!empty($_SESSION['errors'])): ?>
                    <?php foreach ($_SESSION['errors'] as $e): ?>
                        <p class="error-msg"><?= htmlspecialchars($e) ?></p>
                    <?php endforeach; ?>
                    <?php unset($_SESSION['errors']); ?>
                <?php endif; ?>

                <table class="records-table">
                    <thead>
                        <tr>
                            <th>Patient</th>
                            <th>Record Type</th>
                            <th>Document Type</th>
                            <th>Date of Admission</th>
                            <th>Date of Discharge</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="7" class="no-records">No record requests yet.</td>
                            </tr>
                        <?php else: ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <tr>
                                    <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
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
                                    <td>
                                        <?php if ($row['status'] === 'Pending'): ?>
                                            <form method="post" action="records-rec.php" enctype="multipart/form-data" class="upload-form">
                                                <input type="hidden" name="record_id" value="<?= $row['record_id'] ?>">
                                                <input type="hidden" name="action" value="approve">
                                                <input type="file" name="document_file" accept=".pdf" required>
                                                <button type="submit" class="btn-approve">Upload & Approve</button>
                                            </form>
                                            <form method="post" action="records-rec.php" style="margin-top:4px;">
                                                <input type="hidden" name="record_id" value="<?= $row['record_id'] ?>">
                                                <input type="hidden" name="action" value="reject">
                                                <button type="submit" class="btn-reject">Reject</button>
                                            </form>
                                        <?php elseif ($row['status'] === 'Approved' && $row['file_path']): ?>
                                            <a href="../<?= htmlspecialchars($row['file_path']) ?>" target="_blank" class="btn-view">View PDF</a>
                                        <?php else: ?>
                                            <span style="color:#aaa; font-size:9px;">—</span>
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

</body>
</html>