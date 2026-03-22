<?php
session_start();

if (isset($_SESSION['receptionist_id'])) {
    header('Location: profile-rec.php');
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once '../database.php';

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $errors[] = "All fields are required.";
    }

    if (empty($errors)) {
        $stmt = $mysql->prepare("SELECT receptionist_id, firstname, lastname, password FROM receptionists WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $stmt->bind_result($receptionist_id, $firstname, $lastname, $hashedPassword);
        $stmt->fetch();
        $stmt->close();

        if ($receptionist_id && password_verify($password, $hashedPassword)) {
            $_SESSION['receptionist_id'] = $receptionist_id;
            $_SESSION['receptionist_name'] = $firstname . ' ' . $lastname;
            $_SESSION['role'] = 'receptionist';
            header('Location: profile-rec.php');
            exit;
        } else {
            $errors[] = "Invalid username or password.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="login.css">
    <link rel="stylesheet" href="../login.css">

    <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:wght@100..900&display=swap');
    </style>
    <title>BestCare — Receptionist Login</title>
</head>
<body>

    <section class="header">
        <div class="container">
            <section class="logo">
                <img src="img/logo.png" alt="bclogo" class="bclogo">
                <div class="brand-name">
                    <h1>BestCare</h1>
                    <h4>Online Appointment System (OAS)</h4>
                </div>
            </section>
        </div>
    </section>

    <section class="login-page">
        <div class="login-form">
            <div class="container">
                <h3>Receptionist Login</h3>

                <?php if (!empty($errors)): ?>
                    <?php foreach ($errors as $error): ?>
                        <p style="color:red; font-size:12px;"><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                <?php endif; ?>

                <form method="post" action="receptionist_login.php" class="login-paper">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username">

                    <label for="password">Password:</label>
                    <input type="password" id="password" name="password">

                    <button type="submit" class="login-btn">Login</button>
                </form>
            </div>
        </div>
    </section>

</body>
</html>