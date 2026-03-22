<?php
session_start();
require_once '../database.php';

if (!isset($_SESSION['receptionist_id']) || $_SESSION['role'] !== 'receptionist') {
    header('Location: receptionist_login.php');
    exit;
}

// pagination setup
$per_page = 10;
$page     = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset   = ($page - 1) * $per_page;

// get total count
$count_result = $mysql->query("SELECT COUNT(*) FROM patientregistration");
$total_patients = $count_result->fetch_row()[0];
$total_pages  = ceil($total_patients / $per_page);

// fetch patients for current page
$result = $mysql->prepare("
    SELECT pr.patient_id, pr.firstname, pr.lastname,
           p.gender, p.birthdate, p.contact,
           TIMESTAMPDIFF(YEAR, p.birthdate, CURDATE()) AS age
    FROM patientregistration pr
    LEFT JOIN patients p ON pr.patient_id = p.patient_id
    ORDER BY pr.lastname ASC
    LIMIT ? OFFSET ?
");
$result->bind_param("ii", $per_page, $offset);
$result->execute();
$patients = $result->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="profile-rec.css">
    <link rel="stylesheet" href="patient-rec.css">
    <title>Receptionist — Patients</title>
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

        <h3 class="h3title">Patients</h3>
        <h5 class="h5title">(View and manage patient records.)</h5>

        <div class="main-container">
            <div class="update-profile patient-panel">

                <div class="patient-toolbar">
                    <h5 class="formtitle">Patient List</h5>
                </div>

                <div class="patient-filters name-fields">
                    <input class="patient-input" type="text" id="searchInput" placeholder="Search by name or ID..." onkeyup="filterTable()">
                </div>

                <div class="table-wrap">
                    <table class="patient-table" id="patientTable">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Age</th>
                                <th>Gender</th>
                                <th>Contact</th>
                            </tr>
                        </thead>
                        <tbody id="patientBody">
                            <?php if ($patients->num_rows === 0): ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; color:#aaa; font-size:10px;">No patients found.</td>
                                </tr>
                            <?php else: ?>
                                <?php while ($row = $patients->fetch_assoc()): ?>
                                    <tr>
                                        <td>P<?= str_pad($row['patient_id'], 3, '0', STR_PAD_LEFT) ?></td>
                                        <td><?= htmlspecialchars($row['firstname'] . ' ' . $row['lastname']) ?></td>
                                        <td><?= $row['age'] ?? '—' ?></td>
                                        <td><?= htmlspecialchars($row['gender'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($row['contact'] ?? '—') ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- PAGINATION -->
                <?php if ($total_pages > 1): ?>
                    <div class="pagination">

                        <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?>">← Prev</a>
                        <?php else: ?>
                            <span class="disabled">← Prev</span>
                        <?php endif; ?>

                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <a href="?page=<?= $i ?>" class="<?= $i === $page ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?= $page + 1 ?>">Next →</a>
                        <?php else: ?>
                            <span class="disabled">Next →</span>
                        <?php endif; ?>

                    </div>

                    <p class="pagination-info">
                        Showing <?= $offset + 1 ?>–<?= min($offset + $per_page, $total_patients) ?> of <?= $total_patients ?> patients
                    </p>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script>
    function filterTable() {
        const search = document.getElementById('searchInput').value.toLowerCase();
        const rows   = document.querySelectorAll('#patientBody tr');
        rows.forEach(row => {
            row.style.display = row.textContent.toLowerCase().includes(search) ? '' : 'none';
        });
    }
</script>

</body>
</html>