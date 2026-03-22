<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['patient_id'])) {
    header('Location: login.php');
    exit;
}

$patient_id = $_SESSION['patient_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $doctor_id   = !empty($_POST['physician']) ? intval($_POST['physician']) : null;
    $department  = trim($_POST['clinic']);
    $apt_date    = trim($_POST['appointment_date']);
    $apt_time    = trim($_POST['time_slot']);
    $details     = trim($_POST['details']);
    $is_followup = isset($_POST['is_followup']) ? intval($_POST['is_followup']) : 0;

    $errors = [];

    if (empty($department) || empty($apt_date) || empty($apt_time)) {
        $errors[] = "Please fill in all required fields.";
    }

    // check if slot is taken (only if doctor selected)
    if ($doctor_id && empty($errors)) {
        $check = $mysql->prepare("SELECT apt_id FROM appointments WHERE doctor_id = ? AND apt_date = ? AND apt_time = ? AND status = 'Accepted'");
        $check->bind_param("iss", $doctor_id, $apt_date, $apt_time);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $errors[] = "That time slot is already taken for this doctor. Please choose another.";
        }
        $check->close();
    }

    if (empty($errors)) {
        $stmt = $mysql->prepare("INSERT INTO appointments (patient_id, doctor_id, department, apt_date, apt_time, details, is_followup, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'Pending')");
        $stmt->bind_param("iissssi", $patient_id, $doctor_id, $department, $apt_date, $apt_time, $details, $is_followup);

        if ($stmt->execute()) {
            $_SESSION['success'] = "Appointment request submitted successfully!";
            header('Location: appointment.php');
            exit;
        } else {
            $errors[] = "Database error. Please try again.";
        }
        $stmt->close();
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: appointment.php');
        exit;
    }
}

// fetch doctors for pagination
$per_page    = 5;
$page        = isset($_GET['page']) ? intval($_GET['page']) : 1;
$offset      = ($page - 1) * $per_page;
$filter_spec = isset($_GET['specialization']) ? trim($_GET['specialization']) : '';

if ($filter_spec) {
    $count_stmt = $mysql->prepare("SELECT COUNT(*) FROM doctors WHERE status = 'available' AND specialization = ?");
    $count_stmt->bind_param("s", $filter_spec);
} else {
    $count_stmt = $mysql->prepare("SELECT COUNT(*) FROM doctors WHERE status = 'available'");
}
$count_stmt->execute();
$count_stmt->bind_result($total_doctors);
$count_stmt->fetch();
$count_stmt->close();

$total_pages = ceil($total_doctors / $per_page);

if ($filter_spec) {
    $doc_stmt = $mysql->prepare("SELECT firstname, middlename, lastname, specialization, department, schedule_days, schedule_time FROM doctors WHERE status = 'available' AND specialization = ? LIMIT ? OFFSET ?");
    $doc_stmt->bind_param("sii", $filter_spec, $per_page, $offset);
} else {
    $doc_stmt = $mysql->prepare("SELECT firstname, middlename, lastname, specialization, department, schedule_days, schedule_time FROM doctors WHERE status = 'available' LIMIT ? OFFSET ?");
    $doc_stmt->bind_param("ii", $per_page, $offset);
}
$doc_stmt->execute();
$doc_result = $doc_stmt->get_result();

$spec_result = $mysql->query("SELECT DISTINCT specialization FROM doctors WHERE status = 'available' ORDER BY specialization");
$specializations = [];
while ($row = $spec_result->fetch_assoc()) {
    $specializations[] = $row['specialization'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="profile.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700;800;900&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@100..900&display=swap');

        .doctors-section {
            margin-left: 21px;
            margin-bottom: 24px;
            width: 75vw;
            background-color: #ffffff;
            padding: 20px;
            box-sizing: border-box;
        }

        .doctors-section h3 {
            margin: 0 0 4px 0;
            font-family: 'Poppins', sans-serif;
            font-size: 14px;
        }

        .doctors-section h5 {
            margin: 0 0 16px 0;
            font-weight: 300;
            color: #a0a0a0;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
        }

        .filter-row {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
        }

        .filter-row label {
            font-size: 11px;
            font-family: 'Poppins', sans-serif;
            font-weight: 500;
            color: #555;
        }

        .filter-row select {
            padding: 8px 12px;
            font-size: 11px;
            font-family: 'Poppins', sans-serif;
            border: 1px solid #d0d7de;
            border-radius: 6px;
            outline: none;
            background-color: #f9fcff;
            color: #2e2e2e;
        }

        .filter-row select:focus {
            border-color: #a33535;
            box-shadow: 0 0 0 3px rgba(163,53,53,0.1);
        }

        .doctors-table {
            width: 100%;
            border-collapse: collapse;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
        }

        .doctors-table th {
            background-color: #a33535;
            color: #ffffff;
            padding: 10px 14px;
            text-align: left;
            font-weight: 600;
        }

        .doctors-table td {
            padding: 10px 14px;
            border-bottom: 1px solid #f0f0f0;
            color: #2e2e2e;
            vertical-align: top;
        }

        .doctors-table tr:hover td {
            background-color: #fff5f5;
        }

        .spec-badge {
            display: inline-block;
            padding: 3px 8px;
            background-color: #fff5f5;
            color: #a33535;
            border: 1px solid #a33535;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 600;
        }

        .pagination {
            display: flex;
            gap: 6px;
            margin-top: 16px;
            align-items: center;
            font-family: 'Poppins', sans-serif;
            font-size: 11px;
        }

        .pagination a, .pagination span {
            padding: 6px 12px;
            border: 1px solid #d0d7de;
            border-radius: 6px;
            text-decoration: none;
            color: #2e2e2e;
            background-color: #f9fcff;
        }

        .pagination a:hover {
            background-color: #fff5f5;
            border-color: #a33535;
            color: #a33535;
        }

        .pagination .active {
            background-color: #a33535;
            color: #ffffff;
            border-color: #a33535;
        }

        .pagination .disabled {
            color: #aaa;
            cursor: not-allowed;
        }

        .time-slots-wrapper {
            display: flex;
            flex-wrap: wrap;
            gap: 6px;
            margin-top: 6px;
        }

        .time-slot {
            padding: 6px 12px;
            border: 1px solid #d0d7de;
            border-radius: 6px;
            font-size: 10px;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            background-color: #f9fcff;
            transition: all 0.2s;
            user-select: none;
        }

        .time-slot:hover:not(.taken):not(.break) {
            border-color: #a33535;
            background-color: #fff5f5;
        }

        .time-slot.selected {
            background-color: #a33535;
            color: #ffffff;
            border-color: #a33535;
        }

        .time-slot.taken {
            background-color: #e0e0e0;
            color: #aaa;
            cursor: not-allowed;
            border-color: #ccc;
            text-decoration: line-through;
        }

        .time-slot.break {
            background-color: #fff8e1;
            color: #bbb;
            cursor: not-allowed;
            border-color: #ffe082;
        }

        .success-msg { color: green; font-size: 12px; font-family: 'Poppins', sans-serif; padding: 8px 20px; }
        .error-msg   { color: red;   font-size: 12px; font-family: 'Poppins', sans-serif; padding: 4px 20px; }
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
        <h1>Appointment</h1>
    </header>

    <div class="main-content">

        <!-- APPOINTMENT FORM -->
        <div class="main-container">
            <div class="title-row">
                <div>
                    <h3 class="h3title">Request Appointment</h3>
                    <h5 class="h5title">(Please fill in your information accurately.)</h5>
                </div>
            </div>

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

            <form method="post" action="appointment.php" id="appointment-form" class="update-profile">
                <input type="hidden" name="time_slot" id="hidden_time_slot">

                <div class="name">
                    <h5 class="formtitle">Required Fields</h5>

                    <div class="name-fields">
                        <div class="field">
                            <label>Which department are you visiting?</label>
                            <select name="clinic" id="clinic">
                                <option value="">-- Choose a Department --</option>
                                <option value="Cardiology">Cardiology</option>
                                <option value="General & Family Medicine">General & Family Medicine</option>
                                <option value="Neurology">Neurology</option>
                                <option value="Nephrology">Nephrology</option>
                                <option value="Obstetrics & Gynecology">Obstetrics & Gynecology</option>
                                <option value="Ophthalmology/Optometry">Ophthalmology/Optometry</option>
                                <option value="Pediatrics">Pediatrics</option>
                                <option value="Urology">Urology</option>
                            </select>
                        </div>

                        <div class="field">
                            <label>Preferred Physician:</label>
                            <select name="physician" id="physician">
                                <option value="">-- Select Department First --</option>
                            </select>
                        </div>
                    </div>

                    <div class="name-fields">
                        <div class="field">
                            <label>Select your visit date:</label>
                            <input type="date" id="appointment_date" name="appointment_date">
                        </div>

                        <div class="field">
                            <label>Preferred Time Slot:</label>
                            <div class="time-slots-wrapper" id="time-slots-wrapper">
                                <p style="font-size:11px; color:#888; font-family:'Poppins',sans-serif;">
                                    Select a doctor and date first.
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="name-fields">
                        <div class="field">
                            <label>Chief Complaint / Details:</label>
                            <input type="text" name="details" placeholder="Describe your concern...">
                        </div>

                        <div class="field">
                            <label>Is this a follow-up check-up?</label>
                            <div style="display:flex; gap:16px; margin-top:6px;">
                                <label style="font-weight:400; font-size:11px;">
                                    <input type="radio" name="is_followup" value="1"> Yes
                                </label>
                                <label style="font-weight:400; font-size:11px;">
                                    <input type="radio" name="is_followup" value="0" checked> No
                                </label>
                            </div>
                        </div>
                    </div>

                </div>

                <input type="submit" value="Submit Appointment">
                <span id="resspan" style="color:red; font-size:11px; font-family:'Poppins',sans-serif; margin-left:10px;"></span>

            </form>
        </div>

        <!-- DOCTORS SECTION -->
        <div class="doctors-section">
            <h3>Available Doctors</h3>
            <h5>Browse our available physicians for reference.</h5>

            <form method="get" action="appointment.php" class="filter-row">
                <label>Filter by Specialization:</label>
                <select name="specialization" onchange="this.form.submit()">
                    <option value="">-- All Specializations --</option>
                    <?php foreach ($specializations as $spec): ?>
                        <option value="<?= htmlspecialchars($spec) ?>" <?= $filter_spec === $spec ? 'selected' : '' ?>>
                            <?= htmlspecialchars($spec) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($filter_spec): ?>
                    <a href="appointment.php" style="font-size:11px; color:#a33535; font-family:'Poppins',sans-serif;">Clear filter</a>
                <?php endif; ?>
            </form>

            <table class="doctors-table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Specialization</th>
                        <th>Department</th>
                        <th>Schedule Days</th>
                        <th>Schedule Time</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($doc_result->num_rows === 0): ?>
                        <tr>
                            <td colspan="5" style="text-align:center; color:#888;">No doctors found.</td>
                        </tr>
                    <?php else: ?>
                        <?php while ($doc = $doc_result->fetch_assoc()): ?>
                            <tr>
                                <td>Dr. <?= htmlspecialchars($doc['firstname'] . ' ' . ($doc['middlename'] ? $doc['middlename'] . ' ' : '') . $doc['lastname']) ?></td>
                                <td><span class="spec-badge"><?= htmlspecialchars($doc['specialization']) ?></span></td>
                                <td><?= htmlspecialchars($doc['department']) ?></td>
                                <td><?= htmlspecialchars($doc['schedule_days']) ?></td>
                                <td><?= htmlspecialchars($doc['schedule_time']) ?></td>
                            </tr>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>&specialization=<?= urlencode($filter_spec) ?>">← Prev</a>
                    <?php else: ?>
                        <span class="disabled">← Prev</span>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="?page=<?= $i ?>&specialization=<?= urlencode($filter_spec) ?>"
                           class="<?= $i === $page ? 'active' : '' ?>">
                            <?= $i ?>
                        </a>
                    <?php endfor; ?>

                    <?php if ($page < $total_pages): ?>
                        <a href="?page=<?= $page + 1 ?>&specialization=<?= urlencode($filter_spec) ?>">Next →</a>
                    <?php else: ?>
                        <span class="disabled">Next →</span>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>

    </div>
</div>

<script>
    const timeSlots = [
        { label: '8:00 - 9:00 AM',            value: '8:00 - 9:00' },
        { label: '9:00 - 10:00 AM',           value: '9:00 - 10:00' },
        { label: '10:00 - 11:00 AM',          value: '10:00 - 11:00' },
        { label: '11:00 - 12:00 PM',          value: '11:00 - 12:00' },
        { label: '12:00 - 1:00 PM (Lunch)',   value: '12:00 - 1:00', break: true },
        { label: '1:00 - 2:00 PM',            value: '1:00 - 2:00' },
        { label: '2:00 - 3:00 PM',            value: '2:00 - 3:00' },
        { label: '3:00 - 4:00 PM',            value: '3:00 - 4:00' },
    ];

    // department change → load doctors
    document.getElementById('clinic').addEventListener('change', function() {
        const dept = this.value;
        const physicianSelect = document.getElementById('physician');
        physicianSelect.innerHTML = '<option value="">-- Loading... --</option>';

        // reset time slots
        document.getElementById('time-slots-wrapper').innerHTML =
            '<p style="font-size:11px;color:#888;font-family:Poppins,sans-serif;">Select a doctor and date first.</p>';
        document.getElementById('hidden_time_slot').value = '';

        if (!dept) {
            physicianSelect.innerHTML = '<option value="">-- Select Department First --</option>';
            return;
        }

        fetch(`get_doctors.php?department=${encodeURIComponent(dept)}`)
            .then(r => r.json())
            .then(doctors => {
                physicianSelect.innerHTML = '<option value="">-- Select a Doctor --</option>';
                if (doctors.length === 0) {
                    physicianSelect.innerHTML = '<option value="">No doctors available</option>';
                    return;
                }
                doctors.forEach(d => {
                    const opt = document.createElement('option');
                    opt.value = d.doctor_id;
                    opt.textContent = `Dr. ${d.firstname} ${d.middlename ? d.middlename + ' ' : ''}${d.lastname}`;
                    physicianSelect.appendChild(opt);
                });
            });
    });

    // load time slots when doctor or date changes
    function loadTimeSlots() {
        const doctorId = document.getElementById('physician').value;
        const date     = document.getElementById('appointment_date').value;
        const wrapper  = document.getElementById('time-slots-wrapper');

        if (!doctorId || !date) {
            wrapper.innerHTML = '<p style="font-size:11px;color:#888;font-family:Poppins,sans-serif;">Select a doctor and date first.</p>';
            return;
        }

        fetch(`get_taken_slots.php?doctor_id=${doctorId}&apt_date=${date}`)
            .then(r => r.json())
            .then(takenSlots => {
                wrapper.innerHTML = '';
                timeSlots.forEach(slot => {
                    const div = document.createElement('div');
                    div.className = 'time-slot';

                    if (slot.break) {
                        div.classList.add('break');
                        div.textContent = slot.label;
                    } else if (takenSlots.includes(slot.value)) {
                        div.classList.add('taken');
                        div.textContent = slot.label + ' (Taken)';
                    } else {
                        div.textContent = slot.label;
                        div.addEventListener('click', function() {
                            document.querySelectorAll('.time-slot').forEach(s => s.classList.remove('selected'));
                            this.classList.add('selected');
                            document.getElementById('hidden_time_slot').value = slot.value;
                        });
                    }
                    wrapper.appendChild(div);
                });
            });
    }

    document.getElementById('physician').addEventListener('change', loadTimeSlots);
    document.getElementById('appointment_date').addEventListener('change', loadTimeSlots);

    // form validation
    document.getElementById('appointment-form').addEventListener('submit', function(event) {
        const clinic   = document.getElementById('clinic').value;
        const date     = document.getElementById('appointment_date').value;
        const slot     = document.getElementById('hidden_time_slot').value;
        const resspan  = document.getElementById('resspan');

        if (!clinic || !date || !slot) {
            event.preventDefault();
            resspan.innerHTML = 'Please fill in all required fields including a time slot.';
            return;
        }
    });

    // set min date to today
    window.addEventListener('load', function() {
        const today = new Date().toISOString().split('T')[0];
        document.getElementById('appointment_date').min = today;
    });
</script>

</body>
</html>