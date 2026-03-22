<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    require_once 'database.php';

    $username = trim($_POST['username']);
    $password = $_POST['password'];

    $errors = [];

    if (empty($username) || empty($password)) {
        $errors[] = "All fields are required.";
    }

    if (!empty($errors)) {
        $_SESSION['errors'] = $errors;
        header('Location: login.php');
        exit;
    }

    $stmt = $mysql->prepare("SELECT patient_id, password FROM patientregistration WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 0) {
        $_SESSION['errors'] = ["Invalid username or password."];
        header('Location: login.php');
        exit;
    }

    $stmt->bind_result($patient_id, $hashedPassword);
    $stmt->fetch();

    if (!password_verify($password, $hashedPassword)) {
        $_SESSION['errors'] = ["Invalid username or password."];
        header('Location: login.php');
        exit;
    }

    $stmt->close();
    $mysql->close();

    $_SESSION['patient_id']   = $patient_id;
    $_SESSION['username'] = $username;

    header('Location: profile.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="login.css">

   <style>
        @import url('https://fonts.googleapis.com/css2?family=Roboto:ital,wght@0,100..900;1,100..900&display=swap');
        @import url('https://fonts.googleapis.com/css2?family=Figtree:ital,wght@0,300..900;1,300..900&family=Heebo:wght@100..900&family=Liter&family=Quicksand:wght@300..700&family=Spectral&display=swap');    
    </style> 

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BestCare</title>
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
                <h3>Login</h3>

                <?php if (!empty($_SESSION['errors'])): ?>
                    <div class="form-errors">
                        <?php foreach ($_SESSION['errors'] as $error): ?>
                        
                        <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
                        
                        <?php endforeach; ?>
                    </div>
                <?php unset($_SESSION['errors']); ?>
                <?php endif; ?>

                <?php if (!empty($_SESSION['success'])): ?>
                    <div class="form-success">
                        <p><?php echo htmlspecialchars($_SESSION['success']); ?></p>
                    </div>
                <?php unset($_SESSION['success']); ?>
                <?php endif; ?>

                <form method="post" id="login-paper" class="login-paper">

                    <label for="usernametext">Username:</label>
                    <input type="text" id="username" name="username">
                    
                    <label for="passwordtext">Password:</label>
                    <input type="password" id="password" name="password">

                    <button type="submit" class="login-btn">Login</button>

                    <a href="register.php">Don't have an account? Register here</a>

                </form>
            </div>
        </div>
    </section>


</body>
</html>