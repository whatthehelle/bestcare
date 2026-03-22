<?php
session_start();
require_once 'database.php';

if (!isset($_SESSION['patient_id'])) {
    header('Location: login.php');
    exit;
}

$patient_id = $_SESSION['patient_id'];

// fetch from patientregistration
$stmt = $mysql->prepare("SELECT firstname, middlename, lastname, birthday, username FROM patientregistration WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stmt->bind_result($reg_firstname, $reg_middlename, $reg_lastname, $reg_birthday, $reg_username);
$stmt->fetch();
$stmt->close();

// fetch from patients
$stmt = $mysql->prepare("SELECT firstname, middlename, lastname, birthdate, gender, civil_status, religion, street, barangay, province, city, contact, email, em_name, em_relation, em_contact FROM patients WHERE patient_id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$stmt->bind_result($firstname, $middlename, $lastname, $birthdate, $gender, $civil_status, $religion, $street, $barangay, $province, $city, $contact, $email, $em_name, $em_relation, $em_contact);
$stmt->fetch();
$stmt->close();

// use registration info as fallback if patients table is empty
$firstname  = $firstname  ?? $reg_firstname;
$middlename = $middlename ?? $reg_middlename;
$lastname   = $lastname   ?? $reg_lastname;
$birthdate  = $birthdate  ?? $reg_birthday;

// handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname    = trim($_POST['firstname']);
    $middlename   = trim($_POST['middlename']);
    $lastname     = trim($_POST['lastname']);
    $birthdate    = trim($_POST['birthdate']);
    $gender       = trim($_POST['gender']);
    $civil_status = trim($_POST['civil_status']);
    $religion     = trim($_POST['religion']);
    $street       = trim($_POST['street']);
    $barangay     = trim($_POST['barangay']);
    $province     = trim($_POST['province']);
    $city         = trim($_POST['city']);
    $contact      = trim($_POST['contact']);
    $email        = trim($_POST['email']);
    $em_name      = trim($_POST['em_name']);
    $em_relation  = trim($_POST['em_relation']);
    $em_contact   = trim($_POST['em_contact']);

    // check if row already exists
    $check = $mysql->prepare("SELECT id FROM patients WHERE patient_id = ?");
    $check->bind_param("i", $patient_id);
    $check->execute();
    $check->store_result();

    if ($check->num_rows > 0) {
        $stmt = $mysql->prepare("UPDATE patients SET firstname=?, middlename=?, lastname=?, birthdate=?, gender=?, civil_status=?, religion=?, street=?, barangay=?, province=?, city=?, contact=?, email=?, em_name=?, em_relation=?, em_contact=? WHERE patient_id=?");
        $stmt->bind_param("ssssssssssssssssi", $firstname, $middlename, $lastname, $birthdate, $gender, $civil_status, $religion, $street, $barangay, $province, $city, $contact, $email, $em_name, $em_relation, $em_contact, $patient_id);
    } else {
        $stmt = $mysql->prepare("INSERT INTO patients (patient_id, firstname, middlename, lastname, birthdate, gender, civil_status, religion, street, barangay, province, city, contact, email, em_name, em_relation, em_contact) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("issssssssssssssss", $patient_id, $firstname, $middlename, $lastname, $birthdate, $gender, $civil_status, $religion, $street, $barangay, $province, $city, $contact, $email, $em_name, $em_relation, $em_contact);
    }

    $check->close();
    $stmt->execute();
    $stmt->close();

    $_SESSION['success'] = "Profile updated successfully!";
    header('Location: profile.php');
    exit;
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
        @import url('https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&family=Heebo:wght@100..900&family=Liter&family=Quicksand:wght@300..700&family=Spectral&display=swap');

        .display-value {
            font-size: 11px;
            font-family: 'Poppins', sans-serif;
            color: #2e2e2e;
            padding: 10px 12px;
            background-color: #f9fcff;
            border: 1px solid #d0d7de;
            border-radius: 6px;
            min-height: 38px;
            box-sizing: border-box;
        }

        .edit-field  { display: none; }
        .view-field  { display: block; }

        .edit-mode .edit-field { display: block; }
        .edit-mode .view-field { display: none; }

        .title-row {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 20px 20px 0px;
        }

        .title-row div { margin: 0; }

        .btn-edit {
            display: flex;
            align-items: center;
            gap: 6px;
            padding: 8px 16px;
            font-size: 11px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            background-color: #a33535;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-cancel {
            padding: 8px 16px;
            font-size: 11px;
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
            background-color: #888;
            color: #ffffff;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        .btn-edit:hover   { background-color: #8b2020; }
        .btn-cancel:hover { background-color: #666; }

        /* show/hide button groups */
        .view-buttons { display: flex; margin-top: 16px; }
        .save-buttons { display: none; margin-top: 16px; gap: 8px; }

        .edit-mode .view-buttons { display: none; }
        .edit-mode .save-buttons { display: flex; }

        .success-msg {
            color: green;
            font-size: 12px;
            font-family: 'Poppins', sans-serif;
            padding: 8px 20px;
        }
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
        <h1>My Profile</h1>
    </header>

    <div class="main-content">
        <div class="main-container">

            <div class="title-row">
                <div>
                    <h3 class="h3title">Personal Details</h3>
                    <h5 class="h5title">(Please fill in your information accurately.)</h5>
                </div>
            </div>

            <?php if (!empty($_SESSION['success'])): ?>
                <p class="success-msg"><?= htmlspecialchars($_SESSION['success']) ?></p>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <form method="post" id="profile-form" class="update-profile">

                <!-- BASIC INFO - always read only -->
                <div class="name">
                    <h5 class="formtitle">Basic Information</h5>
                    <div class="name-fields">
                        <div class="field">
                            <label>First Name:</label>
                            <div class="display-value"><?= htmlspecialchars($firstname ?? '—') ?></div>
                            <input type="hidden" name="firstname" value="<?= htmlspecialchars($firstname ?? '') ?>">
                        </div>
                        <div class="field">
                            <label>Middle Name:</label>
                            <div class="display-value"><?= htmlspecialchars($middlename ?? '—') ?></div>
                            <input type="hidden" name="middlename" value="<?= htmlspecialchars($middlename ?? '') ?>">
                        </div>
                        <div class="field">
                            <label>Last Name:</label>
                            <div class="display-value"><?= htmlspecialchars($lastname ?? '—') ?></div>
                            <input type="hidden" name="lastname" value="<?= htmlspecialchars($lastname ?? '') ?>">
                        </div>
                    </div>
                    <div class="name-fields">
                        <div class="field">
                            <label>Birthday:</label>
                            <div class="display-value"><?= htmlspecialchars($birthdate ?? '—') ?></div>
                            <input type="hidden" name="birthdate" value="<?= htmlspecialchars($birthdate ?? '') ?>">
                        </div>
                        <div class="field">
                            <label>Username:</label>
                            <div class="display-value"><?= htmlspecialchars($reg_username ?? '—') ?></div>
                        </div>
                    </div>
                </div>

                <!-- ADDITIONAL INFO - editable -->
                <div class="name">
                    <h5 class="formtitle">Additional Information</h5>
                    <div class="name-fields">

                        <div class="field">
                            <label>Gender:</label>
                            <div class="display-value view-field"><?= htmlspecialchars($gender ?? '—') ?></div>
                            <select name="gender" class="edit-field">
                                <option value="">-- Select --</option>
                                <option value="Female"  <?= ($gender ?? '') === 'Female'  ? 'selected' : '' ?>>Female</option>
                                <option value="Male"    <?= ($gender ?? '') === 'Male'    ? 'selected' : '' ?>>Male</option>
                            </select>
                        </div>

                        <div class="field">
                            <label>Civil Status:</label>
                            <div class="display-value view-field"><?= htmlspecialchars($civil_status ?? '—') ?></div>
                            <select name="civil_status" class="edit-field">
                                <option value="">-- Select --</option>
                                <option value="Single"    <?= ($civil_status ?? '') === 'Single'    ? 'selected' : '' ?>>Single</option>
                                <option value="Married"   <?= ($civil_status ?? '') === 'Married'   ? 'selected' : '' ?>>Married</option>
                                <option value="Separated" <?= ($civil_status ?? '') === 'Separated' ? 'selected' : '' ?>>Separated</option>
                                <option value="Widowed"   <?= ($civil_status ?? '') === 'Widowed'   ? 'selected' : '' ?>>Widowed</option>
                            </select>
                        </div>

                        <div class="field">
                            <label>Religion:</label>
                            <div class="display-value view-field"><?= htmlspecialchars($religion ?? '—') ?></div>
                            <input type="text" name="religion" value="<?= htmlspecialchars($religion ?? '') ?>" class="edit-field">
                        </div>

                    </div>
                </div>

                <!-- RESIDENTIAL INFO -->
                <div class="name">
                    <h5 class="formtitle">Residential Information</h5>
                    
                    <div class="field">
                        <label>Province:</label>
                        <div class="display-value view-field"><?= htmlspecialchars($province ?? '—') ?></div>
                        <select name="province" id="province" class="edit-field">
                            <option value="">-- Select Province --</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>City/Municipality:</label>
                        <div class="display-value view-field"><?= htmlspecialchars($city ?? '—') ?></div>
                        <select name="city" id="city" class="edit-field">
                            <option value="">-- Select City --</option>
                        </select>
                    </div>

                    <div class="field">
                        <label>Barangay:</label>
                        <div class="display-value view-field"><?= htmlspecialchars($barangay ?? '—') ?></div>
                        <select name="barangay" id="barangay" class="edit-field">
                            <option value="">-- Select --</option>
                        </select>
                    </div>
                    
                    <div class="field">
                        <label>Street Address:</label>
                        <div class="display-value view-field"><?= htmlspecialchars($street ?? '—') ?></div>
                        <input type="text" name="street" value="<?= htmlspecialchars($street ?? '') ?>" class="edit-field">
                    </div>
                    
                </div>

                <!-- CONTACT INFO -->
                <div class="name">
                    <h5 class="formtitle">Contact Information</h5>
                    <div class="field">
                        <label>Contact Number:</label>
                        <div class="display-value view-field"><?= htmlspecialchars($contact ?? '—') ?></div>
                        <input type="text" name="contact" value="<?= htmlspecialchars($contact ?? '') ?>" class="edit-field">
                    </div>
                    <div class="field">
                        <label>Email Address:</label>
                        <div class="display-value view-field"><?= htmlspecialchars($email ?? '—') ?></div>
                        <input type="text" name="email" value="<?= htmlspecialchars($email ?? '') ?>" class="edit-field">
                    </div>
                </div>

                <!-- EMERGENCY CONTACT -->
                <div class="name">
                    <h5 class="formtitle">Person to Contact in Case of Emergency</h5>
                    <div class="field">
                        <label>Full Name:</label>
                        <div class="display-value view-field"><?= htmlspecialchars($em_name ?? '—') ?></div>
                        <input type="text" name="em_name" value="<?= htmlspecialchars($em_name ?? '') ?>" class="edit-field">
                    </div>
                    <div class="field">
                        <label>Relationship:</label>
                        <div class="display-value view-field"><?= htmlspecialchars($em_relation ?? '—') ?></div>
                        <input type="text" name="em_relation" value="<?= htmlspecialchars($em_relation ?? '') ?>" class="edit-field">
                    </div>
                    <div class="field">
                        <label>Contact Number:</label>
                        <div class="display-value view-field"><?= htmlspecialchars($em_contact ?? '—') ?></div>
                        <input type="text" name="em_contact" value="<?= htmlspecialchars($em_contact ?? '') ?>" class="edit-field">
                    </div>
                </div>

                <!-- BUTTONS -->
                <div class="view-buttons">
                    <button type="button" class="btn-edit" onclick="enableEdit()">Edit Profile</button>
                </div>
                <div class="save-buttons">
                    <button type="submit" class="btn-edit">Save Changes</button>
                    <button type="button" class="btn-cancel" onclick="cancelEdit()">Cancel</button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
    function enableEdit() {
        document.getElementById('profile-form').classList.add('edit-mode');
    }

    function cancelEdit() {
        document.getElementById('profile-form').classList.remove('edit-mode');
    }


    // load JSON files
   let provinces = [], cities = [], barangays = [];

    async function loadAddressData() {
        const [provRes, cityRes, brgyRes] = await Promise.all([
            fetch('json/province.json'),
            fetch('json/city.json'),
            fetch('json/barangay.json')
        ]);

        provinces  = await provRes.json();
        cities     = await cityRes.json();
        barangays  = await brgyRes.json();

        populateProvinces();
    }

    function populateProvinces() {
        const provSelect = document.getElementById('province');
        provinces.forEach(p => {
            const opt = document.createElement('option');
            opt.value = p.province_code;
            opt.textContent = p.province_name;
            if (p.province_name === "<?= addslashes($province ?? '') ?>") {
                opt.selected = true;
            }
            provSelect.appendChild(opt);
        });

        if (provSelect.value) {
            populateCities(provSelect.value, "<?= addslashes($city ?? '') ?>");
        }
    }

    function populateCities(provinceCode, savedCity = '') {
        const citySelect = document.getElementById('city');
        citySelect.innerHTML = '<option value="">-- Select City/Municipality --</option>';
        document.getElementById('barangay').innerHTML = '<option value="">-- Select Barangay --</option>';

        const filtered = cities
            .filter(c => c.province_code === provinceCode)
            .sort((a, b) => a.city_name.localeCompare(b.city_name));

        filtered.forEach(c => {
            const opt = document.createElement('option');
            opt.value = c.city_code;
            opt.textContent = c.city_name;
            if (c.city_name === savedCity) {
                opt.selected = true;
            }
            citySelect.appendChild(opt);
        });

        if (citySelect.value) {
            populateBarangays(citySelect.value, "<?= addslashes($barangay ?? '') ?>");
        }
    }

    function populateBarangays(cityCode, savedBarangay = '') {
        const brgySelect = document.getElementById('barangay');
        brgySelect.innerHTML = '<option value="">-- Select Barangay --</option>';

        const filtered = barangays.filter(b => b.city_code === cityCode);

        filtered.forEach(b => {
            const opt = document.createElement('option');
            opt.value = b.brgy_name;      // changed from barangay_name to brgy_name
            opt.textContent = b.brgy_name; // changed from barangay_name to brgy_name
            if (b.brgy_name === savedBarangay) {
                opt.selected = true;
            }
            brgySelect.appendChild(opt);
        });
    }

    document.getElementById('province').addEventListener('change', function() {
        populateCities(this.value);
    });

    document.getElementById('city').addEventListener('change', function() {
        populateBarangays(this.value);
    });

    loadAddressData();

    function enableEdit() {
        document.getElementById('profile-form').classList.add('edit-mode');
    }

    function cancelEdit() {
        document.getElementById('profile-form').classList.remove('edit-mode');
    }

</script>

</body>
</html>